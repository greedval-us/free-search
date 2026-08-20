# Frontend architecture

Frontend — Vue 3 + TypeScript, доставляемый через Inertia.js. Laravel route обычно рендерит page component, а tab/composable делает JSON/file requests к named module endpoints.

## Структура

- `resources/js/pages`: top-level Inertia pages и module folders;
- `resources/js/pages/<module>/tabs`: Search/Parser/Analytics UI;
- `resources/js/pages/<module>/composables`: request state, polling, transforms и UI orchestration;
- `resources/js/components`: shared UI/application components;
- `resources/js/composables/useParserRun.ts`: общий Parser Run client lifecycle;
- `resources/js/routes` и `wayfinder`: typed/generated route integration;
- `resources/js/types`: global/shared TypeScript contracts.

Telegram, YouTube, Bluesky и Mastodon имеют отдельные tab structures. Site Intel имеет четыре сценарных tabs. Shifr делит toolkit по операциям. Wiki `/wiki/modules` рендерит статическое описание modules через `wiki/Modules.vue`.

## Data flow

Inertia page props несут auth, Feature Access и application configuration. User actions вызывают JSON endpoints; module composable управляет loading/error/result. Parser composables вызывают start, poll status, stop/history/download. HTML reports и file exports открываются как отдельные responses.

## Retry и errors

Frontend API retry policy формируется из `config/osint/frontend_api_retry.php`. Backend JSON errors имеют общую форму; UI должен отображать `message`, использовать field `errors` для 422 и учитывать 403/429 Feature Access. Retry не должен маскировать deterministic validation/access errors.

## Testing

Vitest покрывает общий Parser Run composable и notifications utilities. `vue-tsc`, ESLint, Prettier и i18n pipeline обеспечивают статические проверки. Текущее frontend unit coverage ограничено и не является end-to-end suite.

Каталоги `common-crawl` и `domain-infra-intel` не имеют активных backend routes/modules и не считаются доступными продуктовым modules.
