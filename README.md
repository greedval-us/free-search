# Free Search

Free Search — модульная OSINT-платформа на `Laravel 13` + `Inertia.js` + `Vue 3` с пользовательским интерфейсом, аналитическими модулями, системой тарифов и отдельной админ-панелью на `MoonShine`.

English version: [README.en.md](README.en.md)

## Что это за проект

Проект собирает, нормализует и показывает сигналы из открытых источников. Основной фокус:

- быстрый доступ к прикладовой OSINT-аналитике;
- единый UX для разных источников данных;
- расширяемая модульная архитектура;
- предсказуемая эксплуатация и поддержка.

Сейчас платформа включает:

- поиск, парсинг и аналитику по `Telegram`;
- поиск, парсинг и аналитику по `YouTube`;
- поиск, парсинг и аналитику по `Bluesky`;
- поиск, парсинг и аналитику по `Mastodon`;
- `Site Intel`: `site-health`, `domain-lite`, `analytics`, `seo-audit`;
- `News / Media Intel`;
- `Shifr Toolkit` для прикладовых крипто- и IOC-сценариев;
- пользовательский `Dashboard` с историей, закрепленными модулями и сохраненными запросами;
- web-auth, 2FA, уведомления и биллинг/активацию подписки;
- административную панель на `MoonShine`.

## Технологический стек

- Backend: `PHP 8.3`, `Laravel 13`
- Frontend: `Vue 3`, `TypeScript`, `Inertia.js`, `Vite`
- UI: `Tailwind CSS 4`, `Reka UI`, `Lucide`
- Auth/Security: `Laravel Fortify`, email verification, 2FA
- Admin: `MoonShine`
- Files/Exports: `maatwebsite/excel`
- Telegram integration: `danog/madelineproto`
- Testing: `PHPUnit`, `Vitest`

## Основные пользовательские сценарии

- Запустить поиск по публичным источникам без ручного переключения между инструментами.
- Сохранить повторяющийся запрос и быстро запустить его снова из dashboard.
- Запустить parser-run, отследить статус, остановить задачу и скачать `JSON` или `Excel`.
- Построить аналитический отчет по Telegram, YouTube, Bluesky, Mastodon или Site Intel.
- Управлять профилем, безопасностью, уведомлениями и подпиской из пользовательских настроек.

## Архитектурные принципы

- HTTP-слой тонкий: request -> validation/normalization -> application service -> response.
- Бизнес-правила вынесены из контроллеров в модульные сервисы и узкие коллабораторы.
- Внешние интеграции скрыты за контрактами.
- Runtime-код не читает `env()` напрямую: конфигурация идет через `config/*`.
- Общие архитектурные договоренности документируются и поддерживаются в репозитории.

Связанные документы:

- [docs/architecture/modules.md](docs/architecture/modules.md)
- [docs/errors.md](docs/errors.md)
- [docs/telegram-sessions.md](docs/telegram-sessions.md)

## Структура репозитория

- `app/Modules` — модульные домены и прикладные сценарии
- `app/Http` — controllers, requests, middleware
- `app/Services` — прикладные сервисы верхнего уровня вне модулей
- `app/Support` — общая инфраструктура и cross-cutting support-классы
- `app/MoonShine` — ресурсы и UI админ-панели
- `config` — конфигурация приложения
- `config/osint` — конфиги OSINT-модулей и parser-runs
- `resources/js` — клиентское приложение
- `routes` — публичные, защищенные и settings-маршруты
- `database` — миграции, фабрики, сидеры
- `tests` — unit/feature тесты
- `docs` — архитектурные и эксплуатационные гайды

## Модульная карта

### Telegram

- `search/messages`
- `search/comments`
- media streaming по сообщению
- parser-run lifecycle: `start`, `status`, `history`, `stop`, `download-json`, `download-excel`
- analytics: `summary`, `report`

### YouTube

- поиск видео
- preview комментариев
- parser-run lifecycle
- analytics: `summary`, `report`

### Bluesky

- content search
- actor feed / followers / follows
- likes / reposts / thread
- parser-run lifecycle
- analytics: `summary`, `report`

### Mastodon

- resource search
- account statuses / followers
- tag timeline
- status context
- parser-run lifecycle
- analytics: `summary`, `report`

### Site Intel

- `site-health`
- `domain-lite`
- `analytics`
- `seo-audit`
- HTML/analytics reports

### News / Media Intel

- агрегированный lookup по news/media источникам

### Shifr

- hash lookup
- text transform
- IOC extraction
- JWT inspection
- classic ciphers

## Быстрый старт

### 1. Установить зависимости

```bash
composer install
npm install
```

### 2. Подготовить окружение

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Настроить базу

По умолчанию проект использует `sqlite`, но можно переключить драйвер через `DB_*`.

```bash
php artisan migrate
```

### 4. Запустить dev-окружение

```bash
composer run dev
```

Скрипт поднимает:

- `php artisan serve`
- `php artisan queue:listen --tries=1`
- `vite`

### Альтернатива одним скриптом

```bash
composer run setup
```

## Команды разработки

### Composer

- `composer run dev` — локальное окружение
- `composer run setup` — первичная подготовка
- `composer run lint` — `pint --parallel`
- `composer run lint:check` — проверка PHP-стиля
- `composer run test` — очистка config cache, PHP style check, затем `php artisan test`
- `composer run ci:check` — локальный CI-проход: frontend checks + PHP tests

### NPM

- `npm run dev` — Vite dev server
- `npm run build` — production build
- `npm run build:ssr` — build + SSR bundle
- `npm run lint` — ESLint autofix
- `npm run lint:check` — ESLint check
- `npm run format` — Prettier write
- `npm run format:check` — Prettier check
- `npm run types:check` — `vue-tsc --noEmit`
- `npm run test:unit` — `vitest`
- `npm run i18n:check` — сверка фронтовых переводов
- `npm run i18n:check:strict` — строгая проверка переводов
- `npm run quality:check` — frontend quality bundle

## Конфигурация

### Базовые переменные

- `APP_*`
- `DB_*`
- `CACHE_*`
- `QUEUE_*`
- `SESSION_*`
- `LOG_*`
- `MAIL_*`

### Интеграции

- `TELEGRAM_API_ID`
- `TELEGRAM_API_HASH`
- `YOUTUBE_DATA_API_KEY`
- `OSINT_NEWSAPI_KEY`
- `RESEND_API_KEY`

### Биллинг и checkout

- `BILLING_CHECKOUT_ENABLED`

Текущее поведение:

- при `BILLING_CHECKOUT_ENABLED=false` UI прямой покупки скрыт, а доступ к подписке можно активировать только одноразовым токеном;
- при `BILLING_CHECKOUT_ENABLED=true` checkout и upgrade UI снова включаются без изменений в коде.

### OSINT и Site Intel

Конфигурация секционирована в `config/osint/*.php` и связанных конфиг-файлах:

- `config/osint/telegram.php`
- `config/osint/youtube.php`
- `config/osint/bluesky.php`
- `config/osint/mastodon.php`
- `config/osint/site_intel.php`
- `config/osint/news_media_intel.php`
- `config/osint/parser_runs.php`
- `config/access.php`

Часть полезных переменных из `.env.example`:

- `OSINT_TELEGRAM_*`
- `OSINT_SITE_HEALTH_*`
- `OSINT_SITE_INTEL_*`
- `OSINT_NEWSAPI_*`
- `PARSER_RUN_*`

Parser runs выполняются фоновыми queue jobs. По умолчанию они отправляются в
очередь `default`, поэтому достаточно запущенного `php artisan queue:work`.
Для отдельного worker задайте `PARSER_RUN_QUEUE_NAME=parser-runs` и запускайте
его с `php artisan queue:work --queue=parser-runs`.
- `BILLING_CHECKOUT_ENABLED`

### MadelineProto

- `TELEGRAM_API_ID`
- `TELEGRAM_API_HASH`
- `MADELINEPROTO_SESSION_PATH`
- `MADELINEPROTO_LOG_PATH`

Подробности: [docs/telegram-sessions.md](docs/telegram-sessions.md)

## Аутентификация, доступ и тарифы

### Пользовательская часть

- `Fortify` на `web` guard
- регистрация, восстановление пароля, подтверждение email
- 2FA
- личные настройки профиля, безопасности, уведомлений и биллинга

Заметки по биллингу:

- активация подписки по токену остается доступной на странице биллинга;
- UI прямой покупки и апгрейда управляется флагом `BILLING_CHECKOUT_ENABLED`;
- значение по умолчанию в `.env.example` — `false`.

### Ограничение возможностей

Тарифы и квоты описаны в `config/access.php`.

Сейчас в проекте есть планы:

- `free`
- `plus`
- `pro`

Квоты применяются к ресурсам вроде:

- `telegram.analytics`
- `telegram.parser`
- `youtube.analytics`
- `youtube.parser`
- `bluesky.analytics`
- `bluesky.parser`
- `mastodon.analytics`
- `mastodon.parser`
- `site-intel.analytics`
- `site-intel.seo-audit`

### Админ-панель

- отдельный guard/model `moonshine`
- production-домен и route-prefix настраиваются через `MOONSHINE_*`
- поддерживается IP allowlist:
  - `MOONSHINE_ENFORCE_IP_ALLOWLIST=true`
  - `MOONSHINE_ALLOWED_IPS=...`
- есть throttling логина и алерты по входу

## Фоновые задачи и обслуживание

В `routes/console.php` настроены scheduled jobs:

- `app:notify-subscription-expiry` — ежедневно в `09:00`
- `app:cleanup-parser-runs` — ежедневно по `PARSER_RUN_CLEANUP_SCHEDULE` или `03:30`

Дополнительно:

- parser-runs хранятся в таблице `parser_runs` и приватном storage
- retention и batch cleanup настраиваются через `config/osint/parser_runs.php`

Полезные команды:

```bash
php artisan app:create-telegram-session default
php artisan app:cleanup-parser-runs --dry-run
php artisan app:notify-subscription-expiry
```

## Тестирование

### Backend

- `Feature` — auth/security, dashboard, controller isolation, parser history, subscriptions, billing
- `Unit` — DTO validation, parser state machine, access logic, request payload sanitizing, search actions

Запуск:

```bash
php artisan test
```

### Frontend

- `Vitest` для composables и utility-кода
- `vue-tsc` для проверки типов
- `ESLint` и `Prettier`
- `i18n` pipeline для синхронизации переводов

## Деплой-чеклист

1. Подготовить production `.env`.
2. Убедиться, что настроены `APP_URL`, `SESSION_SECURE_COOKIE`, `MAIL_*`, `QUEUE_CONNECTION`, внешние API-ключи и `MOONSHINE_*`.
   Также проверить нужный режим биллинга через `BILLING_CHECKOUT_ENABLED`.
3. Выполнить миграции:

```bash
php artisan migrate --force
```

4. Собрать frontend:

```bash
npm run build
```

5. Прогреть кэши:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

6. Настроить cron/scheduler:

```bash
php artisan schedule:run
```

7. Проверить:

- вход пользователя;
- работу 2FA;
- доступ в `MoonShine`;
- доступность parser/export флоу;
- job queue и cleanup-задачи;
- нужный режим биллинга: token-only или checkout-enabled.

## Что читать дальше

- [docs/architecture/modules.md](docs/architecture/modules.md)
- [docs/errors.md](docs/errors.md)
- [docs/telegram-sessions.md](docs/telegram-sessions.md)

## Лицензия

`MIT` как база starter-kit. Перед публичной публикацией проверь внутреннюю лицензионную политику проекта, контент, интеграции и права на данные.
