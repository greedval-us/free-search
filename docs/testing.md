# Testing

## Backend

`tests/Feature` проверяет authentication/email verification/2FA, settings, Dashboard, Feature Access, subscriptions, notifications, controller isolation, Parser Run history/storage/background execution и exports. `tests/Unit` покрывает actions, DTO validation, parser state machine/job registry, Telegram session pool, Site Intel guards, request sanitizing и MoonShine security config.

```bash
php artisan test
composer run test
```

Вторая команда предварительно очищает config cache и запускает Pint в check mode.

## Frontend

Vitest tests находятся рядом с composables/utilities (`useParserRun`, notifications):

```bash
npm run test:unit
npm run types:check
npm run lint:check
npm run format:check
npm run i18n:check
```

`npm run quality:check` объединяет все frontend checks, кроме Vitest. `i18n:check:strict` доступен локально, но CI использует обычный `i18n:check`.

## GitHub Actions

| Job | Фактические проверки |
| --- | --- |
| `quality` | `npm ci`, затем `npm run quality:check` |
| `tests` | env + SQLite, asset build, `php artisan test` |
| `build` | env preparation и `npm run build` |

CI не запускает `npm run test:unit` и не вызывает `composer run test`; PHP style проверяется локально отдельной Composer-командой. Это текущая стратегия, а не заявление о полном покрытии.

## Перед merge

```bash
composer run lint:check
php artisan test
npm run quality:check
npm run test:unit
npm run build
```
