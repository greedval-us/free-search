# Monitoring implementation

Started 2026-10-06 on `product-result-fix-06-10-2026`, base `ad129fb`. The working tree was clean. No AGENTS.md was found in the checkout or its parent directories. The supplied task is a specification; repository instructions, actual code and tests determine integration details. Discovery uses codebase-memory-mcp and verifies source on disk.

## Reuse map and decisions

| Existing component | Reuse / extension | New persistence |
| --- | --- | --- |
| Telegram Tracking gateway, public-source validation, reader/matcher | Explicit all-publications mode; reuse public access and session cooldown, independent collector jobs | Project source + independent bounded collection/checkpoint |
| YouTube gateway/resolver | Add channel uploads playlist operation; collect video metadata, not comments | Stable channel ID, per-source cursor |
| Bluesky gateway/author feed | Resolve DID and paginate bounded feed | DID and post URI |
| Mastodon gateway/account statuses | Configured instance, stable account identity, public statuses | Instance/account identity and status ID |
| SearXNG news fetcher | Saved query/domain selection; preserve partial-engine warnings and unknown dates | URL fingerprint, publication date separate from collected time |
| AccountPlan/subscriptions/FeatureUsageCounter | Current plans, separate monitoring usage key and configured development limits | Idempotent report trigger owns one quota charge |
| BotAccess/AccountLink/DeliveryOutbox/Transport/ArtifactRegistry | Explicit digest kind, opt-in and immediate delivery authorization; preserve prohibited legacy kinds | Existing outbox remains delivery metadata |
| StyledSheet/SafeSpreadsheetValueBinder/filename policy/download headers | Query-backed XLSX and streamed JSON from immutable version, private files | Report files and generation state |
| Vue/Inertia/Wayfinder/shared components/RU+EN | Monitoring settings, lifecycle, history and report pages | No new frontend stack |

The new tables cover projects, sources, schedules, bounded collection attempts, materials, immutable report versions and report-item snapshots. No second bot, parser-run engine, checkout or cache-based history is introduced. Technical collection never creates ParserRun or consumes `*.parser` quota.

## Stages

- Stage 0: audit and reuse map completed.
- Stage 1: persistent schema, ownership, configurable plan limits and RU/EN HTTP/UI implemented.
- Stage 2: complete Telegram collection, durable versions, genuine XLSX/JSON, existing bot outbox and unattended recovery implemented and covered by fake-gateway integration tests.
- Stage 3: real YouTube uploads, Bluesky DID author feed, configured-instance Mastodon public statuses and SearXNG query adapters implemented. A mixed-source test executes all five adapters through jobs, one saved report, HTTP history, exports and fake bot transport.
- Stage 4: all four calendar periods, schedules, explicit partial coverage, compatible-period comparison, versioned regeneration, history filters and project lifecycle implemented.
- Stage 5: reliability/security/quota/retention tests, all CI gates, browser project/report/history/download smoke and RU/EN operations documentation completed locally.
- Optional AI/PDF/transcripts are outside mandatory implementation. Basic summaries must be labelled as such.

## Verified invariants

UTC storage with local calendar half-open periods; report and collection leases, stable triggers, independent source progress and bounded scheduler recovery. Pause/resume explicitly records a gap; sources cannot claim complete coverage across it. Finished snapshots survive later edits, source removal and legacy cleanup. New automatic bot delivery defaults off.

## Checks and remaining work

Verified locally: the full initial Laravel suite passed (773 tests / 4314 assertions); frontend quality, 137 tests in 16 files, and client + SSR builds passed. The new monitoring-only migration down/up preserves existing User and running ParserRun rows on a populated SQLite test database (1 test / 27 assertions). Calendar tests include DST and leap-year boundaries. Coverage includes expired leases, lost dispatch, page/window limits, bounded crash retries, independent failures, generation cancellation during network calls, HTTP/callback/job ownership, immutable snapshots, safe spreadsheet cells, retention, unsubscribe/disabled bot delivery and legacy deny rules. No real API calls or bot sends are used in these tests.

Browser smoke uses a separate testing SQLite database and disposable account on loopback, with the real bot disabled. Login and project creation succeeded. The browser exposed a Windows-only Composer startup notice from MadelineProto that prefixed HTTP bodies; `bootstrap/http-autoload.php` now sends only that exact performance notice to the server log and preserves other output. A subprocess regression test verifies valid JSON after real Composer autoload. CLI startup retains the vendor notice. No vendor file or authentication rule was changed.

Final corrections: `catch_up_reports=0` keeps normal scheduled generation while skipping a backlog and counting all skipped occurrences. RU/EN hints describe the actual SearXNG query adapter rather than RSS, the configured Mastodon instance and each adapter's real scope. All actual delivery statuses are translated; report polling continues for pending delivery after files become ready. The automatic-file label accurately promises XLSX; JSON remains available on the website and via a manual bot button.

Final gates, all exit 0:

| Command | Result |
| --- | --- |
| `composer run lint:check` | Whole-project Pint passed |
| `php artisan test --compact` | 777 tests passed / 4349 assertions, 68.75 s; no skipped tests |
| `npm run quality:check` | Prettier, ESLint, vue-tsc and relaxed locale validation passed |
| `npm run test:unit` | 145 tests passed in 16 files |
| `npm run build` | Client and SSR passed; existing Inertia sourcemap warning remains |
| `git diff --check` | Passed; generated files report existing CRLF normalization notices |

Local PHP is OSPanel PHP 8.3; frontend builds used PHP 8.4 on the command PATH for Wayfinder generation. PHP/Node temporary directories outside this workspace required sandbox approval for relevant commands. No new dependency was installed, no production database was migrated, and no external message was sent.

Browser smoke completed with the built assets and real loopback HTTP server: login → create project (one default daily schedule) → request report → process `BuildMonitoringReport` in the database queue → `monitoring:maintain` → process `ExportMonitoringReport` → open history → reopen exact v1 → download JSON and XLSX. Downloaded JSON parsed successfully; XLSX is an 8,350-byte OpenXML ZIP containing the workbook and both Summary/Materials worksheets. The JSON is 1,308 bytes. This browser fixture deliberately has no external sources and shows a partial zero-material result, with ready files and a translated unlinked-bot status. The separate mixed-source feature test covers nonempty data through all five real adapters with fake API/gateway responses, immutable exports and fake bot delivery. These are distinct checks; neither proves live credentials.

Browser screenshot is saved in the session visualization directory as `monitoring-browser-smoke.jpg`. The temporary loopback server, isolated database and private fixture exports were disposed after verification. Browser download files were checked while present; they are no longer available after browser/test cleanup. Operator setup and live source/bot smoke steps are in [monitoring guide](../modules/monitoring.md) and its English version.

Remaining external verification: authorized public Telegram test sources with a configured MadelineProto session; YouTube API key/channel, Bluesky credentials/author, configured Mastodon instance/account and SearXNG endpoint/query; and a specifically authorized linked Telegram test recipient. Production migration, service installation and external sends are not performed as part of local implementation. A populated MySQL upgrade has not been exercised here.
