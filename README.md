# Free Search

Free Search - модульная OSINT-платформа на Laravel + Inertia + Vue с пользовательским интерфейсом, аналитическими модулями и отдельной админ-панелью на MoonShine.

## Что это за проект

Платформа собирает и структурирует сигналы из открытых источников: Telegram, web, news, site intelligence и другие направления OSINT.  
Ключевая цель проекта: быстро давать пользователю полезную аналитику, сохраняя расширяемую архитектуру и предсказуемую поддержку кода.

## Технологический стек

- Backend: `PHP 8.3`, `Laravel 13`
- Frontend: `Vue 3`, `TypeScript`, `Inertia.js`, `Vite`
- Auth/Security: `Laravel Fortify` (включая 2FA для web-пользователей)
- Admin: `MoonShine`
- Queue/Storage: стандартные драйверы Laravel (через `.env`)

## Основные модули

- Telegram OSINT (поиск, парсер, аналитика)
- Site intelligence (`site-health`, `seo-audit`, `domain-lite`)
- Email intelligence
- News/media intelligence
- YouTube search/parser/analytics
- Shifr toolkit

## Архитектурные принципы

- Стандарт модульной архитектуры: [docs/architecture/modules.md](docs/architecture/modules.md)
- Генерация каркаса модуля: `php artisan app:make-module ModuleName`

Коротко:
- Контроллеры тонкие: принимают запрос, валидируют и делегируют.
- Бизнес-правила живут в доменных/прикладных сервисах.
- Инфраструктурные адаптеры изолированы за контрактами.
- Конфиг инициализируется через `config/*`, не через `env()` в runtime-коде.

## Структура репозитория

- `app/Modules` - модульные домены
- `app/Http` - controllers, middleware, requests
- `app/Providers` - service providers
- `app/Support` - общие утилиты и инфраструктурные support-классы
- `config` - конфигурация приложения
- `config/osint` - секционный OSINT-конфиг
- `resources/js` - frontend-приложение
- `routes` - маршруты
- `tests` - unit/feature тесты

## Быстрый старт (локально)

1. Установите зависимости:
```bash
composer install
npm install
```

2. Подготовьте окружение:
```bash
cp .env.example .env
php artisan key:generate
```

3. Примените миграции:
```bash
php artisan migrate
```

4. Запустите dev-окружение:
```bash
composer run dev
```

Альтернатива одним скриптом:
```bash
composer run setup
```

## Команды разработки

- Запуск dev: `composer run dev`
- Тесты: `composer run test`
- Проверка стиля: `composer run lint:check`
- Форматирование: `composer run lint`
- Полный локальный CI-проход: `composer run ci:check`

## Конфигурация

Базовые переменные:
- `APP_ENV`, `APP_DEBUG`, `APP_URL`
- `DB_*`, `CACHE_STORE`, `QUEUE_CONNECTION`, `SESSION_*`

Ключи интеграций:
- `TELEGRAM_API_ID`, `TELEGRAM_API_HASH`
- `OSINT_NEWSAPI_KEY`
- `YOUTUBE_DATA_API_KEY`

OSINT-конфиг:
- секционирован в `config/osint/*.php`
- значения читаются из `.env` с дефолтами

## Безопасность и доступ

Web-auth:
- Fortify (`web` guard), email verification, rate limits, 2FA

Админка:
- отдельный guard/model (`moonshine`)
- IP allowlist для production:
  - `MOONSHINE_ENFORCE_IP_ALLOWLIST=true`
  - `MOONSHINE_ALLOWED_IPS=...`
- троттлинг входа:
  - `MOONSHINE_LOGIN_MAX_ATTEMPTS`
  - `MOONSHINE_LOGIN_DECAY_SECONDS`
- алерты входа:
  - `MOONSHINE_LOGIN_ALERT_EMAIL_ENABLED`
  - `MOONSHINE_LOGIN_ALERT_EMAIL`
  - `MOONSHINE_LOGIN_ALERT_CHANNEL`

Production baseline:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://...`
- `SESSION_SECURE_COOKIE=true`
- `MOONSHINE_DOMAIN=admin.example.com`
- нестандартный `MOONSHINE_ROUTE_PREFIX`

## Подход к качеству кода

- Новая фича должна идти с тестом на критичный happy path.
- Рефакторинг больших классов делаем через выделение узких коллабораторов.
- Общие правила модуля: SRP, DI, контракты, явные границы слоев.
- Архитектурные договоренности фиксируем в `docs/architecture/modules.md`.

## Тестирование

- Feature: auth/security, ключевые пользовательские сценарии
- Unit: доменные вычисления, калькуляторы, парсеры, нормализаторы

Запуск:
```bash
php artisan test
```

## Деплой-чеклист

1. Подготовить production `.env`.
2. Выполнить миграции: `php artisan migrate --force`.
3. Собрать фронт: `npm run build`.
4. Прогреть кэш:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
5. Проверить ограничения доступа в админку и корректность security-настроек.

## Лицензия

MIT (на базе Laravel starter kit). Для публичной публикации уточните внутреннюю лицензионную политику проекта.
