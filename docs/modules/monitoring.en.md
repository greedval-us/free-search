# Monitoring and scheduled reports

[Русский](monitoring.md) · [Documentation index](../README.md) · [Telegram bot guide](telegram-bot.md)

This guide describes the repository implementation as of 6 October 2026. Monitoring is a separate background workflow: projects select sources, retain collected material, and produce reports for closed calendar periods. The scheduler and queue workers continue after the browser closes. Reading projects, history and reports, downloading files, and delivering an already prepared artifact use persisted data without calling source APIs.

## Complete user workflow

1. Sign in and verify email. Open Monitoring and create a project with its name, language, time zone, collection interval, and Overview or Topic mode.
2. Add a public Telegram channel or group. Validation runs in the queue. Its card displays the stable identity, status, previous/next collection time, and covered interval. Resolve server credentials/source errors and use Validate to retry.
3. Add YouTube, Bluesky, Mastodon and a news query using the formats below. Five sources in one project require the current Plus/Pro defaults or operator overrides: Free allows three sources.
4. Configure daily, three-day, weekly and monthly schedules. A new project already has a daily 09:00 schedule in its time zone. Up to four schedules are supported. Collection/report creation and delivery have separate controls.
5. At `/settings/telegram`, create a linking URL, open it in a private bot chat, press Start, then return to the website and confirm the displayed Telegram ID. Enable link notifications, project delivery, and delivery on the desired schedule. Automatic project delivery is off by default.
6. Wait for collection and a schedule occurrence, or request a report for a closed period. A new source defaults to collection from the beginning of the UTC day seven days ago. This is a bounded initial lookback, not the complete channel archive.
7. Open history, a full report, and its original publications. Download JSON/XLSX when files are ready. Receive the bot digest, open the saved full report with normal website authentication, or request a saved file with its button.

Topic mode requires an include phrase. Filters match literal, case-insensitive substrings in title and text: any include phrase is sufficient, and any exclude phrase rejects the material. They are not regular expressions or semantic search. There can be up to 20 include and 20 exclude phrases of 2–100 characters each. The optional author field is a numeric Telegram sender ID and applies only to Telegram; anonymous/channel publications may lack that ID.

## Reused components

Adapters live under `app/Modules/{Telegram,YouTube,Bluesky,Mastodon,NewsMediaIntel}/Monitoring`, with the registry and orchestration under `app/Services/Monitoring`. They reuse TrackingGateway and Telegram history processing, the YouTube channel resolver/API client (extended with uploads `playlistItems`), Bluesky profile resolver/API client, Mastodon account/status actions/presenter, and the SearXNG feed fetcher (extended with a bounded single-page operation). These are real integration points; collection does not invoke the Parser engine.

Existing authorization/verified-email checks, blocking, plans and Feature Access remain in use. Bot delivery reuses confirmed links, consent, Telegraph transport, DeliveryOutbox and delivery jobs. Artifacts reuse Excel sheet builders and filename/response policies; the UI reuses Inertia, Wayfinder and shared components. The new durable monitoring model complements manual Search, Analytics, Parser and Telegram Tracking.

## Sources and collection scope

| Source | Project input | Persisted data | Limits |
| --- | --- | --- | --- |
| Telegram | `@publicchannel`, `publicchannel`, `https://t.me/publicchannel` | Public channel/group messages, text, available author, date, URL and metrics; stable peer ID | Explicit all-publications mode through the existing Tracking gateway. No joining, private invite support, sending, media download, or guarantee of seeing already deleted messages |
| YouTube | Channel ID `UC…`, `@handle`, username, `https://www.youtube.com/@handle`, `/channel/UC…`, `/user/name` | New uploads playlist videos, video ID, title, description, date, URL and views/likes/comment count | Available public videos only. No comment text or transcripts; the existing comments Parser is not used as a channel monitor |
| Bluesky | `name.bsky.social`, `did:plc:…`, `did:web:…`, `https://bsky.app/profile/name.bsky.social` | The author's own posts/replies, AT URI, text, date, URLs and available counters; stable DID | Reposts of other authors are skipped. Handle resolution is exact without choosing a similar search result |
| Mastodon | `name`, `@name`, `name@instance.example`, `@name@instance.example`, `https://instance.example/@name` | Public original statuses/replies, URI, plain text from HTML, date and metrics | All requests use the operator-configured instance; a supplied URL only identifies an account. Boosts, private and unlisted statuses are excluded. Federated coverage depends on this instance |
| News | SearXNG query such as `energy site:news.example.com`; several domains can be selected with query `site:` clauses | Title, snippet, original URL, publication date when supplied, domain and canonical URL fingerprint | Bounded configured news index, not a complete media archive. Selected domains are also enforced by host/subdomain filtering. Full articles and user-supplied RSS/Atom URLs are not fetched |

A news source saves `OSINT_NEWS_MEDIA_SEARXNG_LANGUAGE` when validated. The project/report language does not replace that integration setting. Article pages and user-supplied Mastodon servers are never fetched; API endpoints are operator configuration. These adapters' HTTP clients do not follow redirects.

When a publication date is absent, `published_at` stays `null`. The first `collected_at` is separate and preserved on refetch. The material is included according to first receipt with an unknown-date warning; the code does not invent today's publication date or turn every rediscovery into a new daily item. Undated items are excluded from the publication-date timeline. Limited indexes, unavailable engines, finite pagination, federated visibility and unknown dates are visible in coverage and can produce a partial report.

Monitoring does not guarantee complete historical recovery or discovery of all edits/deletions. External publication links may later become unavailable. Stored text is size-bounded; media and attachments are not archived.

## Summary scope

Each report analyses persisted original material for its own period. Weekly/monthly reports do not concatenate daily report prose. Website, JSON and XLSX share the same snapshot.

The current summary is deterministic basic analysis: grouping by normalized URL while preserving meaningful query parameters, or exact normalized title/excerpt; up to seven excerpts with links; a timeline for known dates; and up to ten recurring words after stopwords. Original publications remain separate even when the summary groups them. This is not LLM analysis, semantic event detection or fact verification. Cross-platform view/like counters are not combined into a single audience.

Publication-count comparison is shown only when a suitable previous persisted completed report exists with matching sources, settings and time zone. Missing comparison data or partial coverage does not become an invented growth figure.

## Calendar, pauses and history

Boundaries use the schedule's local time zone and are stored in UTC. Intervals are half-open, `[start, end)`: a publication exactly at the end belongs to the next period.

| Period | Report interval | Automatic occurrence |
| --- | --- | --- |
| Day | Previous closed calendar day | Daily at the selected time |
| Three days | Three previous closed calendar days | Every three calendar days from `anchor_date` |
| Week | Previous complete Monday-to-Monday week | Mondays |
| Month | Previous complete calendar month | First day of the month |

After downtime, `MONITORING_CATCH_UP_REPORTS=1` creates only the latest missed occurrence; older occurrences are counted as missed rather than generating a notification avalanche. With `0`, accumulated backlog is entirely skipped, while a normal next occurrence still produces a report. This setting does not create unlimited historical collection.

Pausing or archiving stops new jobs and automatic delivery while retaining finished history. Resuming starts a new interval at the current time, exposes `pause_gap`, and does not automatically backfill the pause. Changes to mode/filters/collection mark a new configuration interval. Removing a source preserves old snapshots. Project/source/schedule changes increase configuration generations, cancelling stale work. A completed report is never silently recomputed; Regenerate makes a new version and retains the earlier one.

Build states (`queued`, `building`, `completed`, `partial`, `empty`, `failed`, `cancelled`), file states and delivery states are independent. Telegram failure does not change a finished report to a build failure. Empty means no matching material with known coverage; gaps/errors are reported separately. By default, report creation waits up to 300 seconds for coverage, then can finish partial with explicit gaps. A report requested too early or for a month before observation began does not promise complete history.

A downgrade pauses excess active projects, admitting earlier project IDs first. New work uses current limits and suspended projects need manual resumption when access becomes available. Retained reports remain readable by the owner until their own expiry.

## Storage, artifacts and quotas

Migration `2026_10_05_233105_create_monitoring_tables.php` creates seven tables: `monitoring_projects`, `monitoring_sources`, `monitoring_schedules`, `monitoring_collections`, `monitoring_materials`, `monitoring_reports`, and `monitoring_report_items`. Materials deduplicate by source/external ID. Report snapshots copy publication content and configuration, so later material edits/deletion cannot change a completed report.

JSON and XLSX are prepared through existing export infrastructure on the private disk (`storage/app/private/monitoring/{user}/{project}/{report}/`). XLSX contains summary, coverage and original materials. No public symlink is required. HTTP and bot access check ownership, expiry and readiness; private paths, credentials and source session configuration are not exposed in the UI. Files are asynchronous: downloads return 409 before readiness and 410 after expiry.

History does not use the one-hour `ReportSnapshotStore` or legacy `BotReport`. Background collection creates no manual Parser Run, spends no manual Parser daily quota, and sends no technical file for each collection step. Report creation uses a separate `monitoring_report` quota; reading, downloading and delivering a prepared report do not spend it again.

These `config/monitoring.php` values are development defaults, not approved commercial entitlements. Operators can override them in `.env`:

| Limit | Free | Plus | Pro |
| --- | ---: | ---: | ---: |
| Active projects | 1 | 3 | 5 |
| Saved projects | 5 | 10 | 20 |
| Sources per project | 3 | 10 | 20 |
| Minimum collection interval, minutes | 360 | 180 | 60 |
| Reports created per day | 2 | 10 | 30 |
| New materials per day | 1,000 | 10,000 | 30,000 |
| Report retention, days | 90 | 180 | 365 |

Report quota override: `ACCESS_{FREE|PLUS|PRO}_MONITORING_REPORT_DAILY_LIMIT`. Other overrides: `MONITORING_{FREE|PLUS|PRO}_{ACTIVE_PROJECTS|SAVED_PROJECTS|SOURCES|MIN_INTERVAL_MINUTES|ITEMS_DAILY|RETENTION_DAYS}`. A report's expiry is fixed when created; a later plan change does not extend it.

Daily pruning at 04:30 removes expired reports, item snapshots and private files. Raw materials and terminal collection records use the maximum configured plan retention (365 days by default). Pruning does not delete projects/sources. Confirmed project deletion separately removes its data/files. Server cleanup cannot remove copies already delivered to Telegram. Back up the database and private disk together.

## Integration configuration

| Integration | Existing configuration |
| --- | --- |
| Telegram user API | `TELEGRAM_API_ID`, `TELEGRAM_API_HASH`, authorized MadelineProto session; optional `MADELINEPROTO_SESSION_PATH`, `MADELINEPROTO_LOG_PATH`. Create with `php artisan app:create-telegram-session default` |
| YouTube | `YOUTUBE_DATA_API_KEY`, `YOUTUBE_DATA_API_BASE_URL` (Google v3 default); timeout/retry under `services.youtube` |
| Bluesky | `BLUESKY_IDENTIFIER`, `BLUESKY_APP_PASSWORD`, `BLUESKY_PDS_URL` (`https://bsky.social` default); timeout/retry under `services.bluesky` |
| Mastodon | `MASTODON_API_TOKEN`, `MASTODON_API_BASE_URL` (`https://mastodon.social` default). The current client requires a token. After changing instances, revalidate sources rather than reusing a numeric account ID on another server |
| News | `OSINT_NEWS_MEDIA_SEARXNG_BASE_URL`, `OSINT_NEWS_MEDIA_SEARXNG_LANGUAGE`, `OSINT_NEWS_MEDIA_SEARXNG_ENGINES`, `OSINT_NEWS_MEDIA_SEARXNG_MAX_PAGES`, `OSINT_NEWS_MEDIA_MAX_MENTIONS`, and timeout/request budget/safesearch/time range in the existing `.env.example` block. SearXNG must enable JSON output and working news engines; local startup is under `docker/searxng` |
| Telegram Bot API | `TELEGRAM_BOT_ENABLED=true`, database bot ID `TELEGRAM_BOT_ID`, `TELEGRAM_BOT_USERNAME`, `TELEGRAPH_WEBHOOK_SECRET`, `TELEGRAM_BOT_QUEUE` and its connection. Telegraph setup stores the token in its package table; this is separate from MadelineProto access |

Missing credentials produce a visible source status/error; no browser supplies credentials. See [Telegram Bot](telegram-bot.md) for linking/webhook deployment and [Telegram sessions](../operations/telegram-session.md) for service-session authorization.

## Background processes and recovery

Apply migrations, build the frontend and refresh cached configuration through the normal deployment process. Monitoring defaults to durable database queue `monitoring`; `sync`, `deferred` and `background` are unsuitable. All workers and manual Telegram operations need the same persistent cache store with atomic locks. The process user must access database/cache, sessions and private storage.

```dotenv
MONITORING_QUEUE_CONNECTION=database
MONITORING_QUEUE=monitoring
MONITORING_TIMEZONE=Europe/Moscow
MONITORING_INITIAL_LOOKBACK_DAYS=7
MONITORING_REPORT_WAIT_SECONDS=300
MONITORING_CATCH_UP_REPORTS=1
```

Operator commands (reading this documentation does not execute them):

```bash
php artisan migrate --force
php artisan config:cache
php artisan queue:restart
php artisan schedule:list
php artisan queue:work database --queue=monitoring --sleep=1 --timeout=120 --tries=3 --max-time=3600
```

Run the worker continuously under Supervisor/systemd; the command describes its process, not a complete service definition. Keep the existing bot worker or run one for its configured connection/queue as documented in the bot guide. For a dedicated `telegram-bot` queue: `php artisan queue:work database --queue=telegram-bot --sleep=1 --timeout=120 --tries=0 --max-time=3600`. Existing Parser and Telegram Tracking workers must also remain running.

The Laravel scheduler must run every minute. Do not add a second cron if one already exists:

```cron
* * * * * cd /var/www/free-search && php artisan schedule:run >> /dev/null 2>&1
```

`MonitoringServiceProvider` registers `monitoring:maintain` every minute and `monitoring:maintain --prune` daily at 04:30 in the application time zone. The former schedules/recovers work; the latter only removes expired data. A manual maintain tick may enqueue real external operations; reading the website does not.

SQL stage leases and HTTP API leases last 180 seconds; job timeout is 120 seconds. The shared Telegram session lease uses existing `telegram_tracking.lease_seconds` (300 seconds by default). Queue `retry_after` must exceed timeout (the existing 150 seconds works). The scheduler recovers lost dispatch after 240 seconds. An expired lease allows another worker to resume; domain-stage errors/timeouts are bounded by six attempts. Configuration generation, access and ownership are rechecked before results are committed.

Each step is a bounded page with a two-second next-dispatch delay. Defaults are at most 20 pages of 100 items, 2,000 items per window of up to 24 hours, 16,000 text bytes per material, and 20,000 report items. Topic analysis limits each publication to 128 words and 2,000 candidate terms. SearXNG also has its own limits (normally three pages, 120 results/page, 10-second timeout and 20-second request budget). Reaching a bound is reported as partial coverage, not a complete archive.

Monitoring HTTP requests use a shared account lock and one-second gap. Telegram uses the same session lock/cooldown as manual actions and Tracking. `FLOOD_WAIT` and HTTP `Retry-After` are retained. Collection retry waits for the greater of the platform delay and local exponential backoff (local component capped at 3,600 seconds), never shortening a platform's delay. Workers are released during the wait. After attempts are exhausted, the source displays an error; corrected credentials plus Validate start another validation cycle.

On Windows, HTTP bootstrap redirects only the MadelineProto performance-notice line to the server log, keeping it out of JSON/HTML responses. The CLI warning remains visible. This does not suppress other MadelineProto errors or prove production-load compatibility.

## Delivery and diagnostics

A finished report version enters the existing Telegram outbox as `monitoring_digest`; artifacts use `monitoring_document`. Enqueue/send checks cover ownership, verified link, blocking, current access, expiry, active project/schedule generations and separate notification consent. Skip-empty policy suppresses the empty digest while preserving history. Enabling delivery later does not automatically replay historical versions marked `not_linked` or `disabled`.

`attach_files` defaults to off. Automatic XLSX also requires link `exports_enabled`; JSON is available through manual buttons. The default document bound is 49 MiB. A document failure does not undo a text digest already accepted by Telegram. Manual delivery of a retained report works while its project is paused/archived without automatic-file consent; ownership and expiry still apply. Unlinking cancels pending delivery, not messages already received.

The outbox deduplicates normal retries and does not resend a finished record. If Telegram accepts a message but acknowledgement is lost or the worker crashes before persisting `sent`, retry can still create a copy: strict exactly-once delivery is not guaranteed by the external API.

For stalled collection, inspect scheduler, the correct queue, failed jobs, shared cache/leases and source errors. For partial reports, inspect coverage: observation start, pauses, filter changes, bounds, unavailable/rate-limited APIs and unknown dates have distinct causes. For files, check private disk/permissions, `file_status` and export worker. For a missing digest, inspect its independent `delivery_status`, all opt-ins, webhook and bot worker; report readiness alone does not mean successful delivery.

## Checks and remaining live validation

The repository includes fake adapter and complete five-source project integration tests with exact `Http::fake`, fake Tracking gateway and BotTransport: collection/deduplication, calendar reports, persisted history, real local JSON/XLSX, reads without new HTTP requests, immutable snapshots and unknown dates. Other backend/UI tests cover access, lifecycle, stale-job cancellation, artifacts and consent. These tests validate code contracts, not real external credentials.

Live API/bot integration was not exercised during this implementation. An operator with supplied test accounts must still verify each public source, a new publication after checkpoint, SearXNG JSON/news engines, quotas/rate limits, MadelineProto authorization, webhook/link confirmation, one explicitly agreed digest and both file formats. Run that check with scheduler/workers active and the browser closed. Production coverage remains unverified until live integration and operational observation are completed.
