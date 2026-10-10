# Configuration reference

Настройки читаются из `.env` только в `config/*.php`; runtime services получают Laravel config arrays или typed config objects. Таблицы ниже выделяют важные переменные, а не копируют `.env.example` целиком. Пустой default означает отсутствие credential.

## Application, database и runtime

| Variable                            | Required           | Purpose                              | Default                                          |
| ----------------------------------- | ------------------ | ------------------------------------ | ------------------------------------------------ |
| `APP_NAME`, `APP_ENV`, `APP_URL`    | да                 | Имя, environment и canonical URL     | `Laravel`, `local`, `http://localhost` в example |
| `APP_KEY`                           | да                 | Application encryption key           | генерируется командой                            |
| `APP_DEBUG`                         | да                 | Debug output; в production выключить | `true` в example                                 |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE` | нет                | Backend locale/fallback              | `en`                                             |
| `DB_CONNECTION`                     | да                 | Database driver                      | `sqlite`                                         |
| `DB_*`                              | по driver          | Connection parameters                | SQLite defaults                                  |
| `CACHE_STORE`                       | да                 | Cache backend                        | `database`                                       |
| `QUEUE_CONNECTION`                  | да                 | Queue backend                        | `database`                                       |
| `SESSION_DRIVER`                    | да                 | Session backend                      | `database`                                       |
| `SESSION_LIFETIME`                  | нет                | Session lifetime, minutes            | `120`                                            |
| `SESSION_ENCRYPT`                   | нет                | Encrypt stored session payload       | `false`                                          |
| `SESSION_SECURE_COOKIE`             | production         | HTTPS-only cookie                    | `false` в example                                |
| `SESSION_SAME_SITE`                 | нет                | SameSite policy                      | `lax`                                            |
| `MAIL_MAILER`, `MAIL_*`             | для реальной почты | Verification, password reset, alerts | `log` mailer                                     |
| `RESEND_API_KEY`                    | при Resend         | Resend credential                    | пусто                                            |

Database cache, sessions и queue требуют соответствующих migrations; они присутствуют в репозитории.

## Parser Runs

| Variable                              | Required | Purpose                        | Default              |
| ------------------------------------- | -------- | ------------------------------ | -------------------- |
| `PARSER_RUN_RETENTION_DAYS`           | нет      | Metadata/file retention        | `7` в example/config |
| `PARSER_RUN_CLEANUP_BATCH_SIZE`       | нет      | Cleanup chunk size             | `500`                |
| `PARSER_RUN_CLEANUP_SCHEDULE`         | нет      | Daily cleanup time             | `03:30`              |
| `PARSER_RUN_HISTORY_LIMIT`            | нет      | History rows per user/module   | `20`                 |
| `PARSER_RUN_QUEUE_ENABLED`            | нет      | Background job execution       | `true`               |
| `PARSER_RUN_QUEUE_NAME`               | нет      | Target queue                   | `default`            |
| `PARSER_RUN_QUEUE_STEP_DELAY_SECONDS` | нет      | Delay between collection steps | `2`                  |
| `PARSER_RUN_RECOVERY_BATCH_SIZE` | нет | Metadata candidates per ID batch | `100` |
| `PARSER_RUN_RECOVERY_STALE_AFTER_SECONDS` | нет | Stale threshold; at least execution TTL and step delay + 30 seconds | `150` |
| `PARSER_RUN_RECOVERY_MAX_PASS_SECONDS` | нет | Recovery admission budget per pass, clamped to 1–3600 seconds; scheduler mutex TTL is derived from this value | `30` |
| `PARSER_RUN_MAX_STEP_ATTEMPTS` | нет | Durable total collection-step attempts, including crashed/retried steps | `10000` |
| `PARSER_RUN_MAX_SOURCE_REQUESTS` | нет | Durable explicit source requests, including application retries; hidden MadelineProto transport retries are not observable | `100000` |
| `PARSER_RUN_MAX_RECORDS` | нет | Cumulative normalized processed records across existing source counters | `100000` |
| `PARSER_RUN_MAX_DURATION_SECONDS` | нет | Total run lifetime from original createdAt | `86400` |
| `PARSER_RUN_MAX_CHECKPOINT_BYTES` | нет | Checkpoint plus prospective partial snapshot byte budget | `33554432` |
| `PARSER_RUN_MAX_EXPORT_BYTES` | нет | Prepared JSON/XLSX artifact bytes; checked before response headers | `67108864` |
| `PARSER_RUN_MAX_EXPORT_CELLS` | нет | XLSX cells checked before workbook construction | `1000000` |

Recovery требует асинхронную очередь и общий cache backend для execution/uniqueness locks. Технические бюджеты не меняют тарифы. При исчерпании сохраняется последний допустимый partial snapshot; данные не объявляются полными. Подробные границы и совместимость старых файлов: [Parser Runs](architecture/parser-runs.md).

`OSINT_SITE_HEALTH_HTTP_MAX_RESPONSE_BYTES=2097152` ограничивает фактическое декодированное HTTP-тело одного ответа Site Intel (включая redirect responses, robots, sitemap и crawl). `Content-Length` не заменяет этот предел. Превышение возвращает HTTP 503 `site_intel_response_too_large`; уже существующие timeout, redirects, TLS и SSRF guard продолжают действовать.

## External integrations

| Variable                                     | Required                     | Purpose                                                           | Default                                |
| -------------------------------------------- | ---------------------------- | ----------------------------------------------------------------- | -------------------------------------- |
| `TELEGRAM_API_ID`, `TELEGRAM_API_HASH`       | для Telegram                 | MadelineProto application credentials                             | пусто                                  |
| `MADELINEPROTO_SESSION_PATH`                 | нет                          | Private session directory                                         | `app/private/session/`                 |
| `MADELINEPROTO_LOG_PATH`                     | нет                          | MadelineProto log path                                            | `logs/madeline.log`                    |
| `YOUTUBE_DATA_API_KEY`                       | для YouTube                  | YouTube Data API v3                                               | пусто                                  |
| `YOUTUBE_DATA_API_BASE_URL`                  | нет                          | API endpoint                                                      | Google API URL                         |
| `BLUESKY_IDENTIFIER`, `BLUESKY_APP_PASSWORD` | для Bluesky                  | AT Protocol login                                                 | пусто                                  |
| `BLUESKY_PDS_URL`                            | нет                          | Personal Data Server                                              | `https://bsky.social`                  |
| `MASTODON_API_BASE_URL`                      | для Mastodon                 | Target instance                                                   | `https://mastodon.social`              |
| `MASTODON_API_TOKEN`                         | зависит от instance/API      | Bearer token                                                      | пусто                                  |
| `OSINT_NEWS_MEDIA_SEARXNG_BASE_URL`          | для новостей и веб-аналитики | Адрес своего SearXNG                                              | `http://127.0.0.1:8088`                |
| `OSINT_NEWS_MEDIA_SEARXNG_LANGUAGE`          | нет                          | Язык поиска по умолчанию; пользователь выбирает язык в интерфейсе | `ru`                                   |
| `OSINT_NEWS_MEDIA_SEARXNG_ENGINES`           | нет                          | Default-движки через запятую; фильтруются по категории            | настроенные движки выбранной категории |
| `OSINT_NEWS_MEDIA_SEARXNG_MAX_PAGES`         | нет                          | Максимум поисковых страниц                                        | `3`, верхняя граница `10`              |
| `OSINT_NEWS_MEDIA_SEARXNG_TIMEOUT`           | нет                          | Таймаут одной страницы                                            | `10` секунд, максимум `20`             |
| `OSINT_NEWS_MEDIA_SEARXNG_REQUEST_BUDGET`    | нет                          | Общий HTTP бюджет поиска; в аналитике общий для новостей и веба   | `20` секунд, максимум `25`             |
| `OSINT_NEWS_MEDIA_MAX_MENTIONS`              | нет                          | Максимум документов в итоговой выборке                            | `120`                                  |
| `OSINT_NEWS_MEDIA_SEARXNG_TIME_RANGE`        | нет                          | Default-период: пусто, `day`, `week`, `month`, `year`             | всё время                              |
| `OSINT_NEWS_MEDIA_SEARXNG_SAFESEARCH`        | нет                          | Безопасный поиск: `0`, `1`, `2`                                   | `1`                                    |

Timeout/retry variables поддерживаются в `config/services.php` (`*_TIMEOUT_SECONDS`, `*_RETRY_ATTEMPTS`, `*_RETRY_DELAY_MILLISECONDS`), но не все перечислены в `.env.example`.

Модуль новостей содержит вкладки поиска и SEO/маркетинговой аналитики. Пользователь выбирает категории `news`/`general`, язык, период, безопасный поиск, страницы и движки. Аналитика объединяет две категории в одном ограниченном временном бюджете. Явный фильтр «Всё время» снимает `TIME_RANGE` из default-конфигурации. Фильтры периода, языка и безопасности зависят от возможностей отдельных движков; `site:` и другие операторы также исполняются самими поисковыми системами.

Проект использует существующий SearXNG на VPS; локальный запуск не требуется. Задайте доступный Laravel адрес через `OSINT_NEWS_MEDIA_SEARXNG_BASE_URL`. JSON и категории `news`/`general` обязательны. `docker/searxng/settings.yml` включает четыре новостных движка (`google news`, `bing news`, `duckduckgo news`, `brave.news`) и четыре веб-движка (`google`, `bing`, `duckduckgo`, `brave`). При обновлении настроек на VPS перезапустите SearXNG обычным способом. Необязательный локальный вариант через `docker/searxng/Start.ps1` описан в [модуле новостей](modules/news-media-intel.md); скрипт Docker/WSL не устанавливает и корневой `.env` не меняет.

Списки языков и разрешённых движков находятся в `config/osint/news_media_intel.php` (`searxng.languages`, `searxng.available_engines`). Пустой `ENGINES` использует движки категории. Непустой default фильтруется по категории, при отсутствии подходящих default-движков используются движки категории. Пользовательский непустой выбор ограничивает поиск явно выбранными движками; для аналитики категория без выбранных движков пропускается и показывается как недоступная. Laravel не отправляет `categories` вместе с явным `engines`, поскольку SearXNG объединяет эти параметры.

HTML/JSON обычной вкладки аналитики привязаны к владельцу и хранятся в Laravel cache на `access.report_snapshot_ttl_seconds` (по умолчанию 3600 секунд); очистка cache может завершить их доступность раньше срока. Отдельная вкладка [«Отчёты»](modules/news-media-reports.md) сохраняет расписания и готовую историю в БД: каждые 1/3/7 дней или месяц, просмотр на сайте и отправка в Telegram. Для неё используются `NEWS_MEDIA_REPORTS_QUEUE_CONNECTION`, `NEWS_MEDIA_REPORTS_QUEUE`, `NEWS_MEDIA_REPORTS_TIMEZONE`, `NEWS_MEDIA_REPORTS_TIMEOUT`, `NEWS_MEDIA_REPORTS_RETRY_AFTER` и `NEWS_MEDIA_REPORTS_LEASE_SECONDS`; defaults — `news-media-reports-database`, `news-media-reports`, `Europe/Moscow`, 120, 180 и 300 секунд. Просмотр и загрузка сохранённого отчёта не повторяют поиск. Доли брендов, темы и присутствие домена относятся к собранной выборке; это не частотность запросов, трафик или точные позиции Google. Старые настройки NewsAPI/RSS больше не читаются.

## Module limits

- Telegram: `OSINT_TELEGRAM_ANALYTICS_*`, `OSINT_TELEGRAM_MESSAGES_*`, `OSINT_TELEGRAM_COMMENTS_*`, `OSINT_TELEGRAM_PARSER_*`.
- YouTube: `OSINT_YOUTUBE_ANALYTICS_*`, `OSINT_YOUTUBE_PARSER_*`, `OSINT_YOUTUBE_SEARCH_*`.
- Bluesky: `OSINT_BLUESKY_SEARCH_*`.
- Mastodon: `OSINT_MASTODON_SEARCH_*`.
- Site Intel: `OSINT_SITE_HEALTH_*`, `OSINT_SITE_INTEL_WHOIS_*`.
- News: `OSINT_NEWS_MEDIA_*` (SearXNG, новости и веб-результаты, аналитика SEO/маркетинга).
- Frontend retries: `OSINT_FRONTEND_RETRY_*`; config передаётся через Inertia shared props.

`OSINT_FIO_*` и `OSINT_USERNAME_*` из example сейчас не соответствуют активным backend modules/routes и считаются legacy/unwired settings.

## Billing и Feature Access

| Variable                   | Required | Purpose                        | Default |
| -------------------------- | -------- | ------------------------------ | ------- |
| `BILLING_CHECKOUT_ENABLED` | нет      | Совместимость конфигурации; в этой версии checkout отсутствует, флаг не включает оплату | `false` |

Планы, quotas, route-to-resource mappings и staff bypass находятся в `config/access.php`, а не в `.env`. Изменение quotas — code/config deployment change.

## MoonShine

| Variable                         | Required               | Purpose                        | Default          |
| -------------------------------- | ---------------------- | ------------------------------ | ---------------- |
| `MOONSHINE_ROUTE_PREFIX`         | production recommended | Non-default admin path         | `admin`          |
| `MOONSHINE_DOMAIN`               | нет                    | Dedicated admin host           | `null`           |
| `MOONSHINE_ENFORCE_IP_ALLOWLIST` | production recommended | Enable allowlist in production | `false`          |
| `MOONSHINE_ALLOWED_IPS`          | при allowlist          | Comma-separated IPs            | пусто            |
| `MOONSHINE_LOGIN_MAX_ATTEMPTS`   | нет                    | Login attempt threshold        | `3`              |
| `MOONSHINE_LOGIN_DECAY_SECONDS`  | нет                    | Throttle decay                 | `60`             |
| `MOONSHINE_LOGIN_ALERT_*`        | нет                    | Log/email alerts               | logging defaults |

Allowlist middleware применяется только в `production`; включённый пустой список блокирует доступ.

## Frontend

`VITE_APP_NAME` доступен при сборке. После изменения env/config в production пересоздайте Laravel config cache и frontend bundle, если переменная встраивается Vite.
