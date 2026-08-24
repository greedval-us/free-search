# Dashboard, Wiki и Export

## Dashboard

`DashboardController` вызывает `UserDashboardService`, который строит summary, favorite module, cards, activity feed, seven-day chart, saved queries и pins для текущего user. Источник — sanitized `request_logs`; registry признаёт семь active modules. При отсутствии расширенной request-log schema возвращается empty payload.

Saved queries и pins имеют собственные authenticated endpoints и Eloquent models. Dashboard не запускает универсальный cross-module search: он отражает/повторяет ранее записанные UI actions.

## Wiki

`GET /wiki/modules` рендерит Inertia page `wiki/Modules.vue`. Это пользовательские module descriptions, а не runtime-generated API docs. При изменении capability синхронизируйте page и `docs/modules/*`.

## Export

Shared `app/Modules/Export/Excel` предоставляет workbook service, sheet definitions/styles и Laravel Excel integration. Telegram/YouTube/Bluesky/Mastodon определяют свои sheet layouts. JSON download использует stored Parser result. Site Intel отдаёт HTML reports, News/Shifr exports не реализованы.
