# Parser source request budget

`PARSER_RUN_MAX_SOURCE_REQUESTS` defaults to 100000. Each parser run has a durable `parser_runs.source_request_count`; the additive migration `2026_10_10_023842_add_source_request_count_to_parser_runs_table.php` creates it with zero. Existing runs start counting at deployment: earlier outbound attempts cannot be reconstructed. Existing queued jobs keep their original payloads. Deploy with drained workers, apply migrations, then restart workers as described in the quota upgrade instructions.

The coordinator opens `ParserRunSourceRequestBudget::duringRun(module, userId, runId, callback)` around source collection. Before an attempt, a single conditional database increment reserves one request only for that owner/module/run, while metadata is running, unexpired and below the configured cap. The counter survives container recreation, queue retry and recovery, and is hidden from public model serialization. Source count updates never rewrite JSON, checkpoint version, activity timestamps or retry deadlines, so they do not interfere with checkpoint compare-and-swap or stop. If stop wins during an in-flight step, its terminal state remains authoritative.

Coverage follows the existing call chains:

- YouTube parser collector → `YouTubeDataApiClient`; Mastodon collector → `MastodonApiClient`; Bluesky collector → `BlueskyApiClient`. Their Laravel HTTP `beforeSending` hook charges every retry attempt, including Bluesky session authentication. A budget refusal is not retried.
- Telegram collector → Telegram gateway/actions → `AbstractTelegramAction::executeWithRetry`. Each application attempt is charged. Public peer resolution additionally charges each explicit `refreshPeerCache` / `getInfo` call, and linked discussion metadata charges its explicit `getInfo` call.

This is a bound on HTTP transport attempts and explicit MadelineProto library invocations made by this application. MadelineProto may use cached information or perform multiple hidden RPC/wire retransmissions inside one invocation; those internal requests are not visible to this budget and are not claimed to be individually bounded. No Telegram session or external service is contacted by the regression tests.

Reservation happens before I/O and is never refunded: a connection error or a worker crash still spends its attempt. Outside an active parser scope, the shared clients retain their existing behavior. An exhausted scope remembers refusal even if a Telegram action catches the exception and returns an empty fallback. The coordinator then fails the run with localized `limit_source_requests` and a snapshot of the previously saved partial data. Scope state is always cleared in `finally`.

`ParserRunSourceRequestBudgetTest` covers retries for all three HTTP sources, Bluesky authentication before data retrieval, a fresh service instance sharing the durable counter, stopped/expired metadata, hidden internal counters, and swallowed Telegram exhaustion preserving partial results. Run with:

```powershell
php artisan test --filter=ParserRunSourceRequestBudgetTest
```

The initial two HTTP regressions failed against the old implementation: five outbound retries exceeded a cap of two, and a fresh service instance made a second request despite a cap of one. On 2026-10-10 the seven new source-budget regressions passed together with credential transport, existing YouTube scheduled analytics and parser configuration tests: **49 passed, 176 assertions, 1.52 s**. This run used the project's PHP 8.3 runtime and isolated SQLite fixtures. Targeted Pint formatting also passed.

The first full suite then exposed a regression introduced by the new retry predicate: Laravel calls it with `null` for a 3xx response without an HTTP exception. All three predicates now accept `?Throwable`, preserving the original retry decision for `null` while refusing the budget exception. Three additional HTTP 302 cases cover the shared predicate contract. The focused Mastodon scheduled analytics/source budget/credential run passed: **37 passed, 90 assertions, 1.38 s**; Pint passed for the four changed files. The final full-suite result is recorded in the refactoring report.
