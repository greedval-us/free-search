# Testing

## Backend

`tests/Feature` проверяет authentication/email verification/2FA, settings, Dashboard, Feature Access, subscriptions, notifications, controller isolation, Parser Run history/storage/background execution и exports. `tests/Unit` покрывает actions, DTO validation, parser state machine/job registry, Telegram session pool, Site Intel guards, request sanitizing и MoonShine security config.

Для тестов, работающих с БД, используются MySQL 8.4 и расширение PHP `pdo_mysql`. Создайте выделенную базу `free_search_test` и пользователя с правами только на неё. PHPUnit выполняет в ней `migrate:fresh` и может удалять все таблицы. Настройки подключения приложения `DB_*` тесты заменяют собственными.

По умолчанию PHPUnit использует пользователя `free_search_test` и адрес `127.0.0.1:3306`. Задайте пароль через переменную окружения процесса `TEST_DB_PASSWORD`. Для другого хоста, порта или имени пользователя задайте `TEST_DB_HOST`, `TEST_DB_PORT` или `TEST_DB_USERNAME`. Пример для Bash:

```bash
read -rs -p "MySQL test password: " TEST_DB_PASSWORD
export TEST_DB_PASSWORD
```

```bash
php artisan test
composer run test
```

Вторая команда сначала проверяет PHP-стиль с помощью Pint.

## Отдельные сценарии MySQL

Два интеграционных теста требуют отдельных пустых баз InnoDB и пользователей с правами только на соответствующую базу. Имена баз фиксированы в тестах. Для каждого запуска установите переменные окружения с указанным префиксом и суффиксами `DATABASE`, `HOST`, `PORT`, `USERNAME` и `PASSWORD`. Без явных настроек тесты пропускаются.

| Сценарий | Имя базы | Префикс переменных | Команда |
| --- | --- | --- | --- |
| Конкурентные списания и возвраты квоты, лимит расписаний YouTube | `free_search_quota_concurrency_test` | `QUOTA_CONCURRENCY_DB_` | `php artisan test --filter=FeatureUsageConcurrencyTest` |
| Обновление схемы, старых заданий очереди и контрольной точки парсера | `free_search_refactor_upgrade_test` | `MIGRATION_UPGRADE_DB_` | `php vendor/bin/phpunit --filter=MigrationUpgradeTest` |

Для каждого запуска второго сценария подготовьте пустую базу обновления. Тест загружает [синтетические данные предыдущей версии](../tests/Fixtures/refactor-upgrade-baseline.README.md), применяет миграции и проверяет, что старые задания корректно продолжаются.

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
| `tests` | сборка интерфейса и основной PHPUnit-набор на MySQL 8.4, включая конкурентные транзакции на отдельной тестовой базе |
| `build` | env preparation и `npm run build` |
| `migration-upgrade` | обновление baseline-схемы и queued data на MySQL 8.4 через `MigrationUpgradeTest` |
| `repository-hygiene` (отдельный workflow) | правила `.gitignore`, обязательные файлы и поиск распространённых форматов ключей и токенов в индексе Git |

Результаты проверок доступны на вкладке Checks каждого pull request. [CI](../.github/workflows/ci.yml) · [Проверка репозитория](../.github/workflows/repository-hygiene.yml)

Группа PHPUnit `migration-upgrade` запускается в отдельном CI job на пустой базе. Полная локальная команда `php artisan test` включает эту группу при заданных переменных подключения.

## Перед merge

```bash
composer run lint:check
php artisan test
npm run quality:check
npm run test:unit
npm run build
```
