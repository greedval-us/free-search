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

CI отдельно запускает Vitest и полную проверку PHP-стиля через `composer run lint:check`. Pint использует Laravel preset из `pint.json` и проверяет весь проект, а не только изменённые файлы.

## GitHub Actions

| Job | Фактические проверки |
| --- | --- |
| `quality` | проверка Composer manifest/lock, генерация Wayfinder routes, `npm run quality:check`, `npm run test:unit`, `composer run lint:check` |
| `tests` | env + SQLite, asset build, `php artisan test` |
| `build` | env preparation и `npm run build` |
| `migration-upgrade` | обновление baseline-схемы и queued data на MySQL 8.4 через `MigrationUpgradeTest` |
| `repository-hygiene` (отдельный workflow) | отсутствие отслеживаемых файлов из `.gitignore`, наличие `.env.example`, `composer.lock`, `package-lock.json` |

Бейджи в обоих README ссылаются на реальные GitHub Actions workflow и показывают результат ветки `main`. Проверки pull request отображаются в блоке Checks самого PR. Для защиты `main` в GitHub Settings → Rules → Rulesets можно сделать обязательными `quality`, `tests`, `build`, `migration-upgrade`, `repository-hygiene`; настройки ruleset хранятся в GitHub, а не в YAML workflow.

CI запускает PHP-тесты через `php artisan test`, а полную проверку Pint выполняет отдельно в job `quality`. Локальная команда `composer run test` объединяет очистку config cache, Pint и PHP-тесты.

## Перед merge

```bash
composer run lint:check
php artisan test
npm run quality:check
npm run test:unit
npm run build
```
