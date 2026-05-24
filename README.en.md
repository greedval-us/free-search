# Free Search

An OSINT platform built with Laravel + Inertia + Vue, featuring a user-facing interface, analytical modules, and a MoonShine-based admin panel.

## Stack

- Backend: `PHP 8.3`, `Laravel 13`
- Frontend: `Vue 3`, `Inertia.js`, `TypeScript`, `Vite`
- Auth/Security: `Laravel Fortify` (including 2FA for web users)
- Admin: `MoonShine`
- Queue/Storage: standard Laravel drivers (configured via `.env`)

## Core Features

- Telegram OSINT (search, parser, analytics)
- Username intelligence
- Site intelligence (health, SEO audit, domain-lite)
- Company intelligence
- Document intelligence
- Email intelligence
- Domain infrastructure intelligence
- News/media intelligence
- YouTube search/parser/analytics
- FIO module
- Cipher/tooling module (`Shifr`)
- User dashboard (pinned modules, saved queries)

## Architecture

- Module architecture standard: [docs/architecture/modules.md](docs/architecture/modules.md)
- Generate a module scaffold:
  - `php artisan app:make-module ModuleName`

Short principles:
- Controllers stay thin (validation + orchestration).
- Business logic is moved into services/domain classes.
- Contracts and DI are used for replaceable implementations.
- OSINT config is split into `config/osint/*.php` sections.

## Project Structure

- `app/Modules` - domain modules
- `app/Http` - controllers, middleware, requests
- `app/Providers` - service providers and bindings
- `app/Support` - cross-module utilities/config objects
- `config` - app configuration
- `config/osint` - modular OSINT configs
- `resources/js` - frontend application
- `routes` - route definitions
- `tests` - unit/feature tests

## Quick Start (Local)

1. Install dependencies:
```bash
composer install
npm install
```

2. Prepare environment:
```bash
cp .env.example .env
php artisan key:generate
```

3. Prepare DB and run migrations:
```bash
php artisan migrate
```

4. Start application:
```bash
composer run dev
```

Setup shortcut:
```bash
composer run setup
```

## Useful Commands

- Start dev environment: `composer run dev`
- Run tests: `composer run test`
- Check style: `composer run lint:check`
- Format (Pint): `composer run lint`
- Full CI checks: `composer run ci:check`

## Environment Variables

Base:
- `APP_ENV`, `APP_DEBUG`, `APP_URL`
- `DB_*`, `CACHE_STORE`, `QUEUE_CONNECTION`, `SESSION_*`

Integrations:
- `TELEGRAM_API_ID`, `TELEGRAM_API_HASH`
- `OSINT_NEWSAPI_KEY`
- `YOUTUBE_DATA_API_KEY`

OSINT configuration:
- Grouped in `config/osint/*.php`
- Values loaded from `.env` with safe defaults

## Authentication and Access

Web users:
- Fortify (`web` guard), email verification, login rate limiting, 2FA for users

Admin panel (MoonShine):
- Separate guard/model (`moonshine`)
- Access can be IP-restricted in production:
  - `MOONSHINE_ENFORCE_IP_ALLOWLIST=true`
  - `MOONSHINE_ALLOWED_IPS=...`
- Admin login protection:
  - `MOONSHINE_LOGIN_MAX_ATTEMPTS`
  - `MOONSHINE_LOGIN_DECAY_SECONDS`
- Admin login alerts:
  - `MOONSHINE_LOGIN_ALERT_EMAIL_ENABLED`
  - `MOONSHINE_LOGIN_ALERT_EMAIL`
  - `MOONSHINE_LOGIN_ALERT_CHANNEL`

## Security (Production Recommendations)

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://...`
- `SESSION_SECURE_COOKIE=true`
- Separate admin domain (`MOONSHINE_DOMAIN`)
- Non-default admin prefix (`MOONSHINE_ROUTE_PREFIX`)
- Rotate API keys before release
- HTTPS only

## Testing

- Feature tests: auth, dashboard, security flows
- Unit tests: key analytics/search module logic

Run:
```bash
php artisan test
```

## Deployment Checklist

1. Configure production `.env` with debug disabled.
2. Run migrations: `php artisan migrate --force`.
3. Build frontend: `npm run build`.
4. Warm caches:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
5. Verify admin panel access is restricted as intended.

## License

MIT (based on Laravel starter-kit). Verify your internal licensing policy before publishing.
