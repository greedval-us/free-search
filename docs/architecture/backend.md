# Backend architecture

## Bootstrap и HTTP

`bootstrap/app.php` регистрирует web routes, `/up`, web middleware, alias `feature.access` и JSON exception rendering. `routes/web.php` подключает public routes, затем группу `auth` + `verified` для Dashboard и modules; settings частично доступны просто после `auth`.

Fortify routes поставляет package. Module routes сгруппированы по source, используют named routes и per-endpoint throttles. Они являются backend для UI, а не версионированным public API.

## Application code

- `app/Modules`: source/use-case boundaries, DTO, contracts, adapters, parser/export logic.
- `app/Services`: Dashboard, Feature Access, Subscriptions, sitemap — сценарии уровня приложения.
- `app/Support`: request log sanitation, reports, notifications, MadelineProto, typed shared config.
- `app/Models`: users/subscriptions/usage, request logs, saved queries/pins, Parser Run metadata, queue/admin observability models.
- `app/Providers`: global bindings; module Providers — внутри modules.

Laravel Service Container связывает interfaces с implementations. `bootstrap/providers.php` — фактический список module/global Providers.

## Persistence

Основная persistence — Eloquent database. Parser Run payload/state хранится JSON-файлом на private disk, а searchable lifecycle metadata — в `parser_runs`. Такой split позволяет не помещать большие snapshots в DB, но требует согласованного backup/cleanup обоих хранилищ.

Dashboard использует `request_logs`, `user_saved_queries`, `user_module_pins`; quotas — `feature_usage_daily`; subscriptions — `user_subscriptions` и activation tokens; notifications — стандартная Laravel notifications table.

## Errors

Ожидаемые публичные ошибки выражаются subclasses `PublicException`; JSON renderer локализует безопасное сообщение и code. Validation возвращает field errors. Неожиданные exceptions становятся generic `internal_error`. HTML/Inertia requests следуют стандартному Laravel handling. Подробнее: [errors](../errors.md).

## Admin

MoonShine resources охватывают app users, MoonShine users/roles, subscriptions/tokens, quotas, request/admin logs, queue/failed jobs. App-user edits формируют audit records. Admin guard и model отдельны от Fortify `web` guard.
