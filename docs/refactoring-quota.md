# Quota refunds and upgrade

Every successful counted debit now creates an immutable `FeatureUsageReceipt` (operation UUID, original application day, resulting used count) and a `feature_usage_receipts` row in the same transaction as the daily counter increment. The receipt is internal: public `FeatureAccessDecision::toMeta()` is unchanged, and scheduled report serialization hides `quota_receipt_id`.

`FeatureAccessServiceInterface::refund(User, ?string $receiptId)` accepts only an original receipt handle. There is no route/resource-only refund API. The counter resolves the original daily row from the ledger, verifies its owner, locks the daily row then the receipt, and marks the operation released in the same transaction as the decrement. Repeated refunds, including a receipt restored in another process, are no-ops. A zero counter remains zero and the receipt still becomes final, so a later debit cannot be accidentally refunded by replaying it. Counted debits retain the existing unique daily key, row locking and three transaction attempts. Initialization uses an upsert that changes only `updated_at` on an existing daily row; it never resets usage.

HTTP middleware and parser-start create/dispatch failures carry their successful decision's receipt through the existing refund branches. Staff bypass, inspection, denied requests and non-counting operations produce no receipt and therefore no refundable debit. Refund also retains the original current-staff bypass check: a user promoted after a counted debit does not receive a refund while bypass applies, and the receipt remains pending. The five scheduled report generators save the receipt ID once and reuse it across retries. Their existing same-application-day refund eligibility rule is preserved; a terminal retry on a later day still does not qualify for a refund. This differs deliberately from a synchronous request that crosses midnight, which refunds its original debit.

## Deployment

1. Pause scheduler dispatch, drain in-flight HTTP requests and stop queue workers. Old in-flight code cannot safely straddle the internal refund-contract upgrade.
2. Deploy the code and run `php artisan migrate`. The additive migration `2026_10_10_022106_create_feature_usage_receipts_table.php` creates the receipt ledger and adds nullable receipt IDs to the five existing report tables. It does not reset daily usage or modify old migrations.
3. The migration visits only charged pending/processing reports, selecting small metadata columns in stable batches of 100. It attaches a receipt to the existing original-day counter using `quota_charged_at` and current application quota configuration. Current staff accounts, missing counters and zero counters are skipped. Existing queued report/job payloads continue to use their original IDs and need no reserialization.
4. Restart queue workers and scheduler. Keep the same application timezone and quota-key mapping during this upgrade.

Legacy records do not contain per-operation identity or historical role/configuration snapshots; the backfill cannot reconstruct changes in those values before deployment. Review exceptional legacy records when those settings changed. The migration's `up()` is additive; rolling its `down()` back removes receipt history and report references, so use a forward fix after new code has charged quota. Do not delete unreleased receipts; no retention cleanup is introduced here.

## Verification and backend limits

`FeatureUsageReceiptTest` reproduces response, exception and parser dispatch failures across midnight in Europe/Moscow with an independent new-day debit; it also covers serialized repeat refunds, owner isolation, zero-floor/finalization and no-debit decisions. Existing access and report tests retain limit denial, regular refunds, retry idempotency and same-day report eligibility. `FeatureUsageReceiptMigrationTest` exercises all five legacy report tables and staff/missing-counter handling.

The normal suite uses in-memory SQLite. Its sequential tests do not prove `FOR UPDATE` or real transaction contention. `FeatureUsageConcurrencyTest` provides opt-in MySQL/MariaDB tests with six bounded worker processes and a start barrier: simultaneous counted debits, concurrent duplicate refunds mixed with new debits, and simultaneous YouTube schedule creation at a maximum of two schedules (two created, four `schedule_limit` results). It checks InnoDB and refuses any database name other than `free_search_quota_concurrency_test`; it never reads application `.env` credentials. Provide an empty dedicated database and a database user restricted to it:

```powershell
$env:QUOTA_CONCURRENCY_DB_DATABASE = 'free_search_quota_concurrency_test'
$env:QUOTA_CONCURRENCY_DB_HOST = '127.0.0.1'
$env:QUOTA_CONCURRENCY_DB_PORT = '3306'
$env:QUOTA_CONCURRENCY_DB_USERNAME = 'quota_test'
$env:QUOTA_CONCURRENCY_DB_PASSWORD = '<dedicated test password>'
php artisan test --filter=FeatureUsageConcurrencyTest
```

The test applies ordinary migrations to that database, creates only synthetic records, and deletes its own user afterward. Without this explicit configuration it reports a skip. On 2026-10-10 both tests ran against an isolated MySQL 8.4.8 instance on loopback port 33387, with a newly created empty data directory and dedicated test database/user: **2 passed, 50 assertions, 2.05 s**. Application `.env` and production data were not used. The instance was shut down afterward.

The first real run exposed MySQL deadlock 1213 in concurrent daily-row initialization: duplicate `INSERT IGNORE` operations held shared unique-key locks before all workers requested `FOR UPDATE`. The corrected upsert takes an exclusive duplicate-row lock immediately, avoiding that lock upgrade without adding owner locks, changing tariff rules or increasing blanket retry counts. The same six-process test then passed; schedule-cap contention passed in both runs. This validates the tested local InnoDB races, not production-load capacity, MariaDB differences or multi-host operation.

## Upgrade from the original schema

`MigrationUpgradeTest` is a separate opt-in integration scenario. It requires an empty dedicated database named exactly `free_search_refactor_upgrade_test`, with explicitly supplied credentials. It refuses a nonempty database or cached application configuration and never uses `migrate:fresh`, rollback, application `.env`, production credentials or an existing application database. Its bootstrap supplies a synthetic application key and isolated database/cache/queue configuration; no `.env` generation or key setup is needed.

The committed [baseline fixture](../tests/Fixtures/refactor-upgrade-baseline.README.md) comes from commit `415d288ed13f7b8dee9b96dc98c798e3cee16116`. The test first verifies SHA-256 hashes and applies that commit's **33 original migrations** with ordinary `migrate --path`. It then seeds seven cases in each of the five report tables: charged pending and processing reports, an uncharged pending report, charged staff and completed reports, and charged reports with missing/zero counters. It also stores legacy database queue payloads, parser metadata, and the original JSON checkpoint with saved UTF-8 data.

An ordinary full `migrate` then applies only the two additive upgrade migrations. Assertions verify unchanged legacy fields, counters, schedules, queue payload bytes and checkpoint bytes, exactly ten backfilled receipts, and the legacy source counter starting at zero. Each old report job is deserialized and its real `handle()` method invoked against mocked source services: transient retry and duplicate delivery preserve the debit, while repeated terminal failures refund each original operation once. The old parser job deserializes with `queuedAt = null`; its partial checkpoint continues, stale versions are refused, and source reservations persist from zero to one to two across a new service instance. A second full migration is a no-op for migration history and receipts.

```powershell
$env:MIGRATION_UPGRADE_DB_DATABASE = 'free_search_refactor_upgrade_test'
$env:MIGRATION_UPGRADE_DB_HOST = '127.0.0.1'
$env:MIGRATION_UPGRADE_DB_PORT = '3306'
$env:MIGRATION_UPGRADE_DB_USERNAME = '<restricted test user>'
$env:MIGRATION_UPGRADE_DB_PASSWORD = '<synthetic test password>'
php vendor/bin/phpunit --filter=MigrationUpgradeTest
```

Use direct PHPUnit to avoid the preliminary Laravel bootstrap performed by `artisan test`. Without explicit database configuration the test skips before booting Laravel. The fixture generator is for deliberate fixture regeneration only; CI reads the committed file without Git history.

On 2026-10-10 this transition ran on a newly created isolated MySQL 8.4.8/InnoDB database at loopback port 33387: **1 passed, 183 assertions, 3.332 s**, PHP 8.3.30/PHPUnit 12.5.33, with no risky-test warnings. The first integration attempt stopped because the synthetic Site Intel `.test` domain was correctly rejected by existing target validation; the fixture was changed to `example.org` with a mocked DNS resolver, the dedicated database was recreated, and the complete scenario passed. All source services were mocked and no outbound request occurred. This checks the tested schema/data/job transition; it does not launch a real queue worker or prove compatibility with unrecorded historical configuration/role changes.
