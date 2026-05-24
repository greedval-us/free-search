# Free Search

Free Search is a modular OSINT platform built with Laravel + Inertia + Vue, with a user-facing interface, analytical modules, and a dedicated MoonShine admin panel.

## What This Project Is

The platform collects and structures open-source signals from Telegram, web/news, email/domain, username footprints, and related OSINT directions.  
The main goal is to deliver useful analytics quickly while keeping architecture extensible and maintainable.

## Tech Stack

- Backend: `PHP 8.3`, `Laravel 13`
- Frontend: `Vue 3`, `TypeScript`, `Inertia.js`, `Vite`
- Auth/Security: `Laravel Fortify` (including 2FA for web users)
- Admin: `MoonShine`
- Queue/Storage: standard Laravel drivers (configured via `.env`)

## Core Modules

- Telegram OSINT (search, parser, analytics)
- Username intelligence
- Site intelligence (`site-health`, `seo-audit`, `domain-lite`)
- Company intelligence
- Document intelligence
- Email intelligence
- Domain infrastructure intelligence
- News/media intelligence
- YouTube search/parser/analytics
- FIO
- Shifr toolkit

## Architecture Principles

- Module architecture standard: [docs/architecture/modules.md](docs/architecture/modules.md)
- Generate module scaffold: `php artisan app:make-module ModuleName`

In short:
- Controllers stay thin: receive request, validate, delegate.
- Business rules live in domain/application services.
- External integrations are hidden behind contracts.
- Runtime code uses config objects, not raw `env()` calls.

## Repository Structure

- `app/Modules` - modular domains
- `app/Http` - controllers, middleware, requests
- `app/Providers` - service providers
- `app/Support` - cross-module utilities and support classes
- `config` - app configuration
- `config/osint` - sectioned OSINT config
- `resources/js` - frontend app
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

3. Run migrations:
```bash
php artisan migrate
```

4. Start dev environment:
```bash
composer run dev
```

One-command setup:
```bash
composer run setup
```

## Development Commands

- Start dev: `composer run dev`
- Run tests: `composer run test`
- Style check: `composer run lint:check`
- Format code: `composer run lint`
- Full local CI pass: `composer run ci:check`

## Configuration

Base variables:
- `APP_ENV`, `APP_DEBUG`, `APP_URL`
- `DB_*`, `CACHE_STORE`, `QUEUE_CONNECTION`, `SESSION_*`

Integration keys:
- `TELEGRAM_API_ID`, `TELEGRAM_API_HASH`
- `OSINT_NEWSAPI_KEY`
- `YOUTUBE_DATA_API_KEY`

OSINT config:
- sectioned under `config/osint/*.php`
- values loaded from `.env` with safe defaults

## Authentication and Access

Web authentication:
- Fortify (`web` guard), email verification, login rate limits, 2FA

Admin panel:
- separate guard/model (`moonshine`)
- production IP allowlist:
  - `MOONSHINE_ENFORCE_IP_ALLOWLIST=true`
  - `MOONSHINE_ALLOWED_IPS=...`
- admin login throttling:
  - `MOONSHINE_LOGIN_MAX_ATTEMPTS`
  - `MOONSHINE_LOGIN_DECAY_SECONDS`
- admin login alerts:
  - `MOONSHINE_LOGIN_ALERT_EMAIL_ENABLED`
  - `MOONSHINE_LOGIN_ALERT_EMAIL`
  - `MOONSHINE_LOGIN_ALERT_CHANNEL`

Production baseline:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://...`
- `SESSION_SECURE_COOKIE=true`
- `MOONSHINE_DOMAIN=admin.example.com`
- non-default `MOONSHINE_ROUTE_PREFIX`

## Code Quality Approach

- New features should include a test for the critical happy path.
- Large classes should be split via narrow collaborators.
- Core module rules: SRP, DI, contracts, explicit layer boundaries.
- Architecture agreements are documented in `docs/architecture/modules.md`.

## Testing

- Feature tests: auth/security and primary user flows
- Unit tests: core calculations, parsers, normalizers

Run:
```bash
php artisan test
```

## Deployment Checklist

1. Prepare production `.env`.
2. Run migrations: `php artisan migrate --force`.
3. Build frontend: `npm run build`.
4. Warm caches:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
5. Verify admin panel access restrictions and security settings.

## License

MIT (based on Laravel starter kit). Validate your internal licensing policy before public distribution.
