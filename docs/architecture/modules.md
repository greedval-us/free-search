# Module boundaries

## Фактические стили

| Modules | Фактическая организация |
| --- | --- |
| Telegram | Search/Analytics/Parser application services, Actions, DTO, Presenters, Gateway, MadelineProto support |
| YouTube | Search/Analytics/Parser services, Actions, DTO, Data API Gateway/client |
| Bluesky, Mastodon | Search/Analytics/Parser, Actions, DTO, Presenters, Gateway/API client, typed config |
| Site Intel | Application contracts/services, Domain DTO, Infrastructure clients, Support guard/config |
| News / Media Intel | Application service/contracts, Domain DTO, Infrastructure feed providers |
| Shifr | Application services, Actions, DTO, cipher/toolkit Support; no external infrastructure |
| ParserSupport, Export | Shared technical modules used by social parsers |

Следовательно, проект module-oriented, но не имеет одного обязательного шаблона директорий.

## Правила границ

- Controller зависит от Application Service interface или узкого service, не от raw external client.
- Form Request валидирует/нормализует и при наличии собирает DTO.
- External implementation регистрируется module Service Provider.
- DTO/Result/Presenter отделяет raw source payload от transport/export.
- Module-specific limits находятся в `config/osint/*` или `config/services.php` и часто оборачиваются typed config.
- Shared Parser/Excel infrastructure не должна знать source-specific fields; module export builder формирует sheets.
- Dashboard/Access/Subscriptions остаются в app-level Services, поскольку пересекают несколько modules.

## Добавление модуля

Минимум: named UI/operation routes, Controller + Request, application entry point, source boundary, config/Provider, frontend page/composable, localized errors и tests. Parser нужен только для длительного пошагового сбора; не копируйте Parser lifecycle для синхронного lookup. Paid capability требует resource/route/page mapping в `config/access.php`.

Проверяйте регистрацию Provider в `bootstrap/providers.php` и подключение nested config в `config/osint.php`: наличие файла само по себе не делает его доступным как `config('osint.<key>')`.
