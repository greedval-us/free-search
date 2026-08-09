# Free Search

Free Search is a modular OSINT platform built with `Laravel 13` + `Inertia.js` + `Vue 3`, with a user-facing workspace, analytical modules, subscription-aware access control, and a dedicated `MoonShine` admin panel.

Russian version: [README.md](README.md)

## Overview

The platform collects, normalizes, and presents signals from open sources. Its main goals are:

- fast access to practical OSINT workflows;
- a unified UX across different source types;
- an extensible modular architecture;
- predictable operations and maintenance.

The current platform includes:

- `Telegram` search, parser, and analytics;
- `YouTube` search, parser, and analytics;
- `Bluesky` search, parser, and analytics;
- `Mastodon` search, parser, and analytics;
- `Site Intel`: `site-health`, `domain-lite`, `analytics`, `seo-audit`;
- `News / Media Intel`;
- `Shifr Toolkit` for applied crypto and IOC workflows;
- a user `Dashboard` with history, pinned modules, and saved queries;
- web auth, 2FA, notifications, and subscription activation/billing flows;
- a `MoonShine` admin panel.

## Tech Stack

- Backend: `PHP 8.3`, `Laravel 13`
- Frontend: `Vue 3`, `TypeScript`, `Inertia.js`, `Vite`
- UI: `Tailwind CSS 4`, `Reka UI`, `Lucide`
- Auth/Security: `Laravel Fortify`, email verification, 2FA
- Admin: `MoonShine`
- Files/Exports: `maatwebsite/excel`
- Telegram integration: `danog/madelineproto`
- Testing: `PHPUnit`, `Vitest`

## Main User Flows

- Run open-source lookups from a single workspace.
- Save repeated queries and re-run them from the dashboard.
- Start parser runs, track status, stop them, and download `JSON` or `Excel`.
- Generate analytical reports for Telegram, YouTube, Bluesky, Mastodon, or Site Intel.
- Manage profile, security, notifications, and subscription settings from the app.

## Architecture Principles

- The HTTP layer stays thin: request → validation/normalization → application service → response.
- Business rules live outside controllers in modular services and narrow collaborators.
- External integrations are hidden behind contracts.
- Runtime code does not call `env()` directly; configuration comes from `config/*`.
- Architecture agreements are documented and kept close to the codebase.

Related docs:

- [docs/architecture/modules.md](docs/architecture/modules.md)
- [docs/errors.md](docs/errors.md)
- [docs/telegram-sessions.md](docs/telegram-sessions.md)

## Repository Structure

- `app/Modules` - modular domains and use cases
- `app/Http` - controllers, requests, middleware
- `app/Services` - top-level application services outside module folders
- `app/Support` - shared infrastructure and cross-cutting support classes
- `app/MoonShine` - admin panel resources and UI integration
- `config` - application configuration
- `config/osint` - OSINT and parser-run configuration
- `resources/js` - frontend application
- `routes` - public, protected, and settings routes
- `database` - migrations, factories, seeders
- `tests` - unit and feature tests
- `docs` - architecture and operations guides

## Module Map

### Telegram

- `search/messages`
- `search/comments`
- message media streaming
- parser-run lifecycle: `start`, `status`, `history`, `stop`, `download-json`, `download-excel`
- analytics: `summary`, `report`

### YouTube

- video search
- comments preview
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
- HTML and analytics reports

### News / Media Intel

- aggregated lookup across configured news/media providers

### Shifr

- hash lookup
- text transform
- IOC extraction
- JWT inspection
- classic ciphers

## Quick Start

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Prepare the environment

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure the database

The default setup uses `sqlite`, but any Laravel-supported driver can be configured through `DB_*`.

```bash
php artisan migrate
```

### 4. Start the local environment

```bash
composer run dev
```

This script starts:

- `php artisan serve`
- `php artisan queue:listen --tries=1`
- `vite`

### One-command alternative

```bash
composer run setup
```

## Development Commands

### Composer

- `composer run dev` - local development environment
- `composer run setup` - initial project bootstrap
- `composer run lint` - `pint --parallel`
- `composer run lint:check` - PHP style check
- `composer run test` - clear config cache, run PHP style check, then `php artisan test`
- `composer run ci:check` - local CI-style pass: frontend checks + PHP tests

### NPM

- `npm run dev` - Vite dev server
- `npm run build` - production build
- `npm run build:ssr` - build + SSR bundle
- `npm run lint` - ESLint autofix
- `npm run lint:check` - ESLint check
- `npm run format` - Prettier write
- `npm run format:check` - Prettier check
- `npm run types:check` - `vue-tsc --noEmit`
- `npm run test:unit` - `vitest`
- `npm run i18n:check` - frontend translation sync check
- `npm run i18n:check:strict` - strict translation check
- `npm run quality:check` - bundled frontend quality pass

## Configuration

### Base variables

- `APP_*`
- `DB_*`
- `CACHE_*`
- `QUEUE_*`
- `SESSION_*`
- `LOG_*`
- `MAIL_*`

### Integrations

- `TELEGRAM_API_ID`
- `TELEGRAM_API_HASH`
- `YOUTUBE_DATA_API_KEY`
- `OSINT_NEWSAPI_KEY`
- `RESEND_API_KEY`

### OSINT and Site Intel

Configuration is split across `config/osint/*.php` and related config files:

- `config/osint/telegram.php`
- `config/osint/youtube.php`
- `config/osint/bluesky.php`
- `config/osint/mastodon.php`
- `config/osint/site_intel.php`
- `config/osint/news_media_intel.php`
- `config/osint/parser_runs.php`
- `config/access.php`

Useful `.env.example` groups include:

- `OSINT_TELEGRAM_*`
- `OSINT_SITE_HEALTH_*`
- `OSINT_SITE_INTEL_*`
- `OSINT_NEWSAPI_*`
- `PARSER_RUN_*`

### MadelineProto

- `TELEGRAM_API_ID`
- `TELEGRAM_API_HASH`
- `MADELINEPROTO_SESSION_PATH`
- `MADELINEPROTO_LOG_PATH`

Details: [docs/telegram-sessions.md](docs/telegram-sessions.md)

## Authentication, Access, and Plans

### User-facing app

- `Fortify` on the `web` guard
- registration, password reset, email verification
- 2FA
- profile, security, notifications, and billing settings

### Feature access

Plans and quotas are configured in `config/access.php`.

Current plans:

- `free`
- `plus`
- `pro`

Quota-controlled resources currently include:

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

### Admin panel

- separate `moonshine` guard/model
- production domain and route prefix configured through `MOONSHINE_*`
- IP allowlist support:
  - `MOONSHINE_ENFORCE_IP_ALLOWLIST=true`
  - `MOONSHINE_ALLOWED_IPS=...`
- login throttling and login alert settings

## Background Jobs and Maintenance

`routes/console.php` schedules:

- `app:notify-subscription-expiry` - daily at `09:00`
- `app:cleanup-parser-runs` - daily at `PARSER_RUN_CLEANUP_SCHEDULE` or `03:30`

Operational notes:

- parser runs are stored in the `parser_runs` table and private storage
- retention and cleanup batching are configured in `config/osint/parser_runs.php`

Useful commands:

```bash
php artisan app:create-telegram-session default
php artisan app:cleanup-parser-runs --dry-run
php artisan app:notify-subscription-expiry
```

## Testing

### Backend

- `Feature` tests for auth/security, dashboard, controller isolation, parser history, subscriptions, and billing
- `Unit` tests for DTO validation, parser state machines, access logic, payload sanitizing, and search actions

Run:

```bash
php artisan test
```

### Frontend

- `Vitest` for composables and utility code
- `vue-tsc` for type safety
- `ESLint` and `Prettier`
- an `i18n` pipeline for translation consistency

## Deployment Checklist

1. Prepare production `.env`.
2. Make sure `APP_URL`, `SESSION_SECURE_COOKIE`, `MAIL_*`, `QUEUE_CONNECTION`, external API keys, and `MOONSHINE_*` are configured.
3. Run migrations:

```bash
php artisan migrate --force
```

4. Build the frontend:

```bash
npm run build
```

5. Warm Laravel caches:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

6. Configure cron/scheduler:

```bash
php artisan schedule:run
```

7. Verify:

- user login;
- 2FA flow;
- `MoonShine` access;
- parser/export flows;
- queue workers and cleanup jobs.

## Further Reading

- [docs/architecture/modules.md](docs/architecture/modules.md)
- [docs/errors.md](docs/errors.md)
- [docs/telegram-sessions.md](docs/telegram-sessions.md)

## License

`MIT` as inherited from the starter kit base. Before public distribution, verify your internal licensing policy, content rules, integrations, and data usage constraints.
