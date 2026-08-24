# Configuration reference

Настройки читаются из `.env` только в `config/*.php`; runtime services получают Laravel config arrays или typed config objects. Таблицы ниже выделяют важные переменные, а не копируют `.env.example` целиком. Пустой default означает отсутствие credential.

## Application, database и runtime

| Variable | Required | Purpose | Default |
| --- | --- | --- | --- |
| `APP_NAME`, `APP_ENV`, `APP_URL` | да | Имя, environment и canonical URL | `Laravel`, `local`, `http://localhost` в example |
| `APP_KEY` | да | Application encryption key | генерируется командой |
| `APP_DEBUG` | да | Debug output; в production выключить | `true` в example |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE` | нет | Backend locale/fallback | `en` |
| `DB_CONNECTION` | да | Database driver | `sqlite` |
| `DB_*` | по driver | Connection parameters | SQLite defaults |
| `CACHE_STORE` | да | Cache backend | `database` |
| `QUEUE_CONNECTION` | да | Queue backend | `database` |
| `SESSION_DRIVER` | да | Session backend | `database` |
| `SESSION_LIFETIME` | нет | Session lifetime, minutes | `120` |
| `SESSION_ENCRYPT` | нет | Encrypt stored session payload | `false` |
| `SESSION_SECURE_COOKIE` | production | HTTPS-only cookie | `false` в example |
| `SESSION_SAME_SITE` | нет | SameSite policy | `lax` |
| `MAIL_MAILER`, `MAIL_*` | для реальной почты | Verification, password reset, alerts | `log` mailer |
| `RESEND_API_KEY` | при Resend | Resend credential | пусто |

Database cache, sessions и queue требуют соответствующих migrations; они присутствуют в репозитории.

## Parser Runs

| Variable | Required | Purpose | Default |
| --- | --- | --- | --- |
| `PARSER_RUN_RETENTION_DAYS` | нет | Metadata/file retention | `7` в example/config |
| `PARSER_RUN_CLEANUP_BATCH_SIZE` | нет | Cleanup chunk size | `500` |
| `PARSER_RUN_CLEANUP_SCHEDULE` | нет | Daily cleanup time | `03:30` |
| `PARSER_RUN_HISTORY_LIMIT` | нет | History rows per user/module | `20` |
| `PARSER_RUN_QUEUE_ENABLED` | нет | Background job execution | `true` |
| `PARSER_RUN_QUEUE_NAME` | нет | Target queue | `default` |
| `PARSER_RUN_QUEUE_STEP_DELAY_SECONDS` | нет | Delay between collection steps | `2` |

## External integrations

| Variable | Required | Purpose | Default |
| --- | --- | --- | --- |
| `TELEGRAM_API_ID`, `TELEGRAM_API_HASH` | для Telegram | MadelineProto application credentials | пусто |
| `MADELINEPROTO_SESSION_PATH` | нет | Private session directory | `app/private/session/` |
| `MADELINEPROTO_LOG_PATH` | нет | MadelineProto log path | `logs/madeline.log` |
| `YOUTUBE_DATA_API_KEY` | для YouTube | YouTube Data API v3 | пусто |
| `YOUTUBE_DATA_API_BASE_URL` | нет | API endpoint | Google API URL |
| `BLUESKY_IDENTIFIER`, `BLUESKY_APP_PASSWORD` | для Bluesky | AT Protocol login | пусто |
| `BLUESKY_PDS_URL` | нет | Personal Data Server | `https://bsky.social` |
| `MASTODON_API_BASE_URL` | для Mastodon | Target instance | `https://mastodon.social` |
| `MASTODON_API_TOKEN` | зависит от instance/API | Bearer token | пусто |
| `OSINT_NEWSAPI_KEY` | только NewsAPI provider | NewsAPI credential | пусто |
| `OSINT_NEWSAPI_BASE_URL` | нет | NewsAPI endpoint | `/v2/everything` endpoint |

Timeout/retry variables поддерживаются в `config/services.php` (`*_TIMEOUT_SECONDS`, `*_RETRY_ATTEMPTS`, `*_RETRY_DELAY_MILLISECONDS`), но не все перечислены в `.env.example`.

## Module limits

- Telegram: `OSINT_TELEGRAM_ANALYTICS_*`, `OSINT_TELEGRAM_MESSAGES_*`, `OSINT_TELEGRAM_COMMENTS_*`, `OSINT_TELEGRAM_PARSER_*`.
- YouTube: `OSINT_YOUTUBE_ANALYTICS_*`, `OSINT_YOUTUBE_PARSER_*`, `OSINT_YOUTUBE_SEARCH_*`.
- Bluesky: `OSINT_BLUESKY_SEARCH_*`.
- Mastodon: `OSINT_MASTODON_SEARCH_*`.
- Site Intel: `OSINT_SITE_HEALTH_*`, `OSINT_SITE_INTEL_WHOIS_*`.
- News: `OSINT_NEWS_MEDIA_*`, `OSINT_NEWSAPI_*`.
- Frontend retries: `OSINT_FRONTEND_RETRY_*`; config передаётся через Inertia shared props.

`OSINT_FIO_*` и `OSINT_USERNAME_*` из example сейчас не соответствуют активным backend modules/routes и считаются legacy/unwired settings.

## Billing и Feature Access

| Variable | Required | Purpose | Default |
| --- | --- | --- | --- |
| `BILLING_CHECKOUT_ENABLED` | нет | Показывать checkout/upgrade UI | `false` |

Планы, quotas, route-to-resource mappings и staff bypass находятся в `config/access.php`, а не в `.env`. Изменение quotas — code/config deployment change.

## MoonShine

| Variable | Required | Purpose | Default |
| --- | --- | --- | --- |
| `MOONSHINE_ROUTE_PREFIX` | production recommended | Non-default admin path | `admin` |
| `MOONSHINE_DOMAIN` | нет | Dedicated admin host | `null` |
| `MOONSHINE_ENFORCE_IP_ALLOWLIST` | production recommended | Enable allowlist in production | `false` |
| `MOONSHINE_ALLOWED_IPS` | при allowlist | Comma-separated IPs | пусто |
| `MOONSHINE_LOGIN_MAX_ATTEMPTS` | нет | Login attempt threshold | `3` |
| `MOONSHINE_LOGIN_DECAY_SECONDS` | нет | Throttle decay | `60` |
| `MOONSHINE_LOGIN_ALERT_*` | нет | Log/email alerts | logging defaults |

Allowlist middleware применяется только в `production`; включённый пустой список блокирует доступ.

## Frontend

`VITE_APP_NAME` доступен при сборке. После изменения env/config в production пересоздайте Laravel config cache и frontend bundle, если переменная встраивается Vite.
