# Telegram tracking

## Behaviour

- Telegram / Tracking is available to verified, unblocked users on all plans.
- Active quotas: Free 1, Plus 3, Pro 5. Maximum three unique sources per task.
- Paused tasks release active slots; at most five unfinished tasks can be saved. Stop an unused task to free a saved slot.
- Match mode is either a case-insensitive keyword substring (minimum three Unicode characters) or exact Telegram sender user ID. Mentions, forwarded authors and anonymous/channel senders do not match a user ID.
- Public usernames, t.me links and already accessible negative chat IDs are accepted. Invite links are not accepted. The collector never joins, imports an invite, subscribes, marks history read or sends messages to monitored chats.
- Creation validates all sources against the same authorized service session and checks readable history. Any failure rejects the entire task; no partially valid task is scheduled. Validation is cached for five minutes, not a guarantee that a source remains accessible forever.
- The first check is due six hours after creation. A job reads one history page, commits matches and the cursor, then yields. Subsequent jobs continue the same bounded window. There is no six-hour truncation when a check is late.
- Default interval is six hours, increasing with active source load on the session to at most 24 hours. Telegram FLOOD_WAIT is never shortened, and a removed session, unreadable source, unavailable worker or very large backlog can exceed that target. The UI shows delayed checks, last successful coverage and retry time.
- Only messages observed during collection can be saved. Messages deleted before a check, edits to already checkpointed messages and media attachments are not archived.
- A task lasts one calendar month (no date overflow). Renew during its last seven days for another month. Renewal preserves all collected data and does not automatically resume paused tasks.
- Pause preserves results and expiry. Resume starts collecting new messages from that moment; the paused interval is not backfilled. Finish is irreversible. Expiry finishes the task automatically.
- Paid tasks pause when paid entitlement lapses or is cancelled. Renewed paid access does not silently resume them. Manual resume checks the current quota and records the new entitlement. A downgrade also pauses tasks above the lower limit.
- Messages are retained for the whole task lifetime, including extensions, and for seven days after completion. Then the task, sources and messages are removed together. This replaces the originally proposed rolling seven-day retention so full-lifetime reports remain possible.
- Bell notifications are aggregated per completed source check. Per-task Telegram opt-in AND the linked bot account's notification preference are required for bot alerts. Notification text is shared with the site's RU/EN dictionary.
- Bot menu / Tracking reports sends current XLSX or JSON on request, not unsolicited files. Ownership, account verification, bot link and retention are checked at delivery time. Files already sent to Telegram cannot be recalled by website cleanup.

## Layers

- `app/Modules/Telegram/Tracking`: lifecycle, configuration, queue scheduling, incremental collector, gateway and reports.
- `TrackingPageProcessor` interprets a history page without database writes; `TrackingCollector` owns leases, persistence, checkpoints and retry. `TrackingNotifications` owns the shared bell/bot notification payloads.
- `TrackingService` owns user actions; `TrackingLifecycle` reconciles expiry and subscription entitlement. Listing and renewal use the same renewal-window rule on the task model.
- The Vue tracking tab composes a creation form, task cards and a results panel with shared pagination. `useTelegramTracking` owns API calls, form state, polling and cancellation.
- `app/Http/Requests/Telegram/TelegramTrackingRequest.php`: HTTP input validation and normalization.
- `app/Http/Controllers/Telegram/TelegramTrackingController.php`: authenticated responses and downloads.
- `app/Integrations/TelegramBot/TrackingArtifactProvider.php`: explicit integration adapter between module contracts. Neither feature module imports the other.
- Database uniqueness prevents duplicate messages. User row locks serialize quota/lifecycle changes; source leases and per-source locks fence stale queue jobs. No Telegram network call runs inside a database transaction.
- An interrupted worker lease becomes dispatchable again after five minutes. Jobs carry only a source ID and lease token, not message content or Telegram credentials.
- Reports use the shared safe Excel binder and styling. Query-backed Excel and streamed JSON read messages incrementally, with a fixed upper message ID. Excel still needs spreadsheet memory proportional to workbook size; watch VPS memory for long-running, high-volume tasks. JSON is preferable for very large histories.

## Deployment

1. Apply migrations with `php artisan migrate --force` and build client/SSR assets with `npm ci && npm run build`.
2. Set `TELEGRAM_TRACKING_QUEUE_CONNECTION=redis` (or `database`) and `TELEGRAM_TRACKING_QUEUE=telegram-tracking`. Never use `sync`, `deferred`, `background` or `null`.
3. Keep the existing scheduler cron: `* * * * * cd /var/www/free-search && php artisan schedule:run >> /dev/null 2>&1`.
4. Run a supervised worker, initially one process: `php artisan queue:work redis --queue=telegram-tracking --timeout=60 --tries=1 --sleep=1 --max-time=3600`. Use `database` instead of `redis` if configured. Queue `retry_after` must exceed 60 seconds (the project default is 150).
5. Run workers and scheduler as the same service user with access to private MadelineProto sessions and a shared persistent cache. Do not use the array cache in production. Existing Telegram parsers share the service sessions and may contribute to Telegram rate limiting.
6. Run `php artisan config:cache` and `php artisan queue:restart` after configuration/code changes. The bot keeps its existing separate worker and webhook; no bot token changes are required.
7. Check `php artisan schedule:list`: `telegram:tracking-maintain` runs every minute, expires/pauses tasks, prunes data and dispatches due sources. The command can be run manually but will enqueue due collection jobs.

## Verification

Automated feature tests use a fake Telegram gateway and Telegraph transport. They do not call real Telegram accounts, join groups or send real bot messages. Live Telegram readability, flood behaviour and VPS capacity still need a deployment smoke test with a public test source.

Protocol references: [history](https://core.telegram.org/method/messages.getHistory), [pagination](https://core.telegram.org/api/offsets). Collection uses MadelineProto's installed API with `floodWaitLimit: 0` on history calls rather than sleeping through long rate limits inside a worker.
