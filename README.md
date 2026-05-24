# Free Search

OSINT-платформа на Laravel + Inertia + Vue с пользовательским интерфейсом, аналитическими модулями и админ-панелью на MoonShine.

## Стек

- Backend: `PHP 8.3`, `Laravel 13`
- Frontend: `Vue 3`, `Inertia.js`, `TypeScript`, `Vite`
- Auth/Security: `Laravel Fortify` (включая 2FA для web-пользователей)
- Admin: `MoonShine`
- Queue/Storage: стандартные драйверы Laravel (по `.env`)

## Основные возможности

- Telegram OSINT (поиск, парсер, аналитика)
- Username intelligence
- Site intelligence (health, SEO audit, domain-lite)
- Company intelligence
- Document intelligence
- Email intelligence
- Domain infrastructure intelligence
- News/media intelligence
- YouTube search/parser/analytics
- FIO module
- Шифры/утилиты (`Shifr`)
- Пользовательский dashboard (избранные модули, сохраненные запросы)

## Архитектура

- Стандарт модульной архитектуры: [docs/architecture/modules.md](docs/architecture/modules.md)
- Генерация заготовки модуля:
  - `php artisan app:make-module ModuleName`

Коротко по принципам:
- Контроллеры тонкие (валидация + orchestration).
- Бизнес-логика вынесена в сервисы/доменные классы.
- Контракты и DI используются для замены реализаций.
- Конфиг OSINT разнесен на секции `config/osint/*.php`.

## Структура проекта

- `app/Modules` - предметные модули
- `app/Http` - контроллеры, middleware, requests
- `app/Providers` - сервис-провайдеры и bindings
- `app/Support` - кросс-модульные утилиты/конфиг-объекты
- `config` - конфигурация приложения
- `config/osint` - модульные конфиги OSINT
- `resources/js` - frontend-приложение
- `routes` - маршруты
- `tests` - unit/feature тесты

## Быстрый старт (локально)

1. Установить зависимости:
```bash
composer install
npm install
```

2. Подготовить окружение:
```bash
cp .env.example .env
php artisan key:generate
```

3. Поднять БД и миграции:
```bash
php artisan migrate
```

4. Запустить приложение:
```bash
composer run dev
```

Альтернатива по скрипту:
```bash
composer run setup
```

## Полезные команды

- Запуск dev-окружения: `composer run dev`
- Запуск тестов: `composer run test`
- Проверка стиля: `composer run lint:check`
- Форматирование (Pint): `composer run lint`
- Комплексная CI-проверка: `composer run ci:check`

## Переменные окружения

Базовые:
- `APP_ENV`, `APP_DEBUG`, `APP_URL`
- `DB_*`, `CACHE_STORE`, `QUEUE_CONNECTION`, `SESSION_*`

Интеграции:
- `TELEGRAM_API_ID`, `TELEGRAM_API_HASH`
- `OSINT_NEWSAPI_KEY`
- `YOUTUBE_DATA_API_KEY`

OSINT-конфиги:
- Сгруппированы в `config/osint/*.php`
- Значения берутся из `.env` с безопасными дефолтами

## Аутентификация и доступ

Web-пользователи:
- Fortify (`web` guard), email verification, rate limit логина, 2FA для пользователей

Админка (MoonShine):
- Отдельный guard/model (`moonshine`)
- Доступ можно ограничить по IP в production:
  - `MOONSHINE_ENFORCE_IP_ALLOWLIST=true`
  - `MOONSHINE_ALLOWED_IPS=...`
- Защита логина админки:
  - `MOONSHINE_LOGIN_MAX_ATTEMPTS`
  - `MOONSHINE_LOGIN_DECAY_SECONDS`
- Алерты входа в админку:
  - `MOONSHINE_LOGIN_ALERT_EMAIL_ENABLED`
  - `MOONSHINE_LOGIN_ALERT_EMAIL`
  - `MOONSHINE_LOGIN_ALERT_CHANNEL`

## Безопасность (рекомендации для production)

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://...`
- `SESSION_SECURE_COOKIE=true`
- Отдельный домен для админки (`MOONSHINE_DOMAIN`)
- Нестандартный префикс админки (`MOONSHINE_ROUTE_PREFIX`)
- Ротация API-ключей перед релизом
- HTTPS only

## Тестирование

- Feature-тесты: auth, dashboard, security flows
- Unit-тесты: ключевые модули аналитики/поиска

Запуск:
```bash
php artisan test
```

## Деплой-чеклист

1. Заполнить production `.env` без debug.
2. Выполнить миграции: `php artisan migrate --force`.
3. Собрать фронт: `npm run build`.
4. Прогреть кэш:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
5. Проверить доступность админки только по нужным правилам доступа.

## Лицензия

MIT (на базе Laravel starter-kit). Уточните внутреннюю лицензионную политику проекта при публикации.
