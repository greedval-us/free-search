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
