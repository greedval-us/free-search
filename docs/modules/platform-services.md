# Dashboard, Wiki и Export

## Dashboard

`DashboardController` вызывает `UserDashboardService`, который строит summary, favorite module, cards, activity feed, seven-day chart, saved queries и pins для текущего user. Источник — sanitized `request_logs`; registry признаёт семь active modules. При отсутствии расширенной request-log schema возвращается empty payload.

Saved queries и pins имеют собственные authenticated endpoints и Eloquent models. Dashboard не запускает универсальный cross-module search: он отражает/повторяет ранее записанные UI actions.

## Wiki

`GET /wiki/modules` открывает справку по модулям. Страница `wiki/Modules.vue` берёт ссылки из общего каталога навигации. При изменении возможностей модуля обновите обе версии `resources/js/locales/{en,ru}/moduleWiki.json` и соответствующий документ в `docs/modules/`.

## Export

Shared `app/Modules/Export/Excel` предоставляет workbook service, sheet definitions/styles и Laravel Excel integration. Telegram/YouTube/Bluesky/Mastodon определяют свои sheet layouts. JSON download использует stored Parser result. Site Intel отдаёт HTML reports, News/Shifr exports не реализованы.
