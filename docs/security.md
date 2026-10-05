# Security

Free Search обрабатывает credentials внешних API, пользовательские данные, сетевые targets и результаты OSINT-сборов. Ниже отдельно указано реализованное поведение и рекомендации.

## Implemented

- Laravel session/web authentication через Fortify; passwords имеют Eloquent cast `hashed`.
- `User` реализует email verification; основной app group использует `auth` + `verified`.
- Fortify 2FA с challenge, confirmation и recovery codes; security page может требовать password confirmation.
- Login и 2FA rate limits — по 5 попыток в минуту; password update route — `throttle:6,1`.
- Blocked users не аутентифицируются; middleware завершает существующую web session.
- Form Requests валидируют и нормализуют module input; Laravel CSRF middleware защищает web routes.
- JSON exceptions нормализуются и не возвращают raw internal exception message по умолчанию.
- Feature Access middleware ограничивает paid capabilities и daily quotas; admin/moderator account types обходят quotas.
- API endpoints имеют route-level throttles.
- Site Intel содержит target guard/resolution logic и тесты SSRF-sensitive redirects/targets.
- Secrets читаются через config; `.env` игнорируется Git.
- MadelineProto sessions и Parser Run JSON хранятся на private disk.
- MoonShine использует отдельный `moonshine` guard/model, login throttle, production-only IP allowlist и security alerts configuration.
- Admin actions над app users пишут audit records; request/activity logging sanitizes payloads через dedicated support class.
- Production web responses используют HSTS, CSP с nonce, Referrer Policy, Permissions Policy и anti-sniffing headers; MoonShine path можно исключить из CSP через конфигурацию.
- Request/activity logs автоматически удаляются после настраиваемого retention period.

## Модель доступа безопасного пилота

| Источник | Разрешённый доступ | Граница |
| --- | --- | --- |
| Telegram | Публичные каналы и супергруппы по username | Каждый запрос заново разрешает username через Telegram; числовые peers, личные диалоги, Saved Messages, invite links, закрытые и защищённые источники отклоняются. Кэш общей сессии не является разрешением доступа. |
| Telegram comments/media/tracking | Контент проверенного публичного источника | Linked discussion проверяется отдельно; media привязано к запрошенному peer и message ID; tracking повторно проверяет публичность и ID источника перед сбором. |
| YouTube | Данные, доступные через YouTube Data API | Серверный API key передаётся в `X-Goog-Api-Key`, вне query string. |
| Bluesky | Данные, доступные настроенной API-сессии | App password отправляется в теле запроса создания сессии, API-запросы используют Authorization header. |
| Mastodon | Данные, доступные API настроенного instance | Token передаётся в Authorization header; доступность результатов зависит от правил instance и аккаунта. |
| Parser history/exports | Запуски текущего пользователя | JSON на private disk и metadata scoped по user/module; хранилище принимает только UUID запуска и отклоняет traversal до доступа к файлу. |

Shifr принимает операции только через POST с CSRF. JWT, HMAC/cipher keys и входной текст не включаются в ссылки повторного запуска; чувствительные поля маскируются в activity payload. Старые GET endpoints операций возвращают 405. Ответы Shifr используют `private, no-store`.

Общий frontend API client добавляет CSRF только к изменяющим same-origin запросам, сохраняет `HeadersInit` и не повторяет POST/PUT/PATCH/DELETE без явной retry policy. `AbortSignal` отменяет запрос и ожидание retry. Parser polling отменяется при остановке и unmount; ссылки скачивания принимаются только для HTTP(S) текущего origin без credentials и управляющих символов.

«Новости и медиа» обращается только к настроенному SearXNG через серверный POST `/search` с категорией `news`. Адрес задаётся администратором через config; пользователь не может передать свой upstream URL. HTTP redirects отключены, URL с credentials отклоняются. Результаты преобразуются в обычный текст, ссылки ограничены HTTP(S). Локальный контейнер опубликован только на `127.0.0.1:8088`; JSON-выдача включается в его settings. Передача YouTube API key в header соответствует [рекомендациям Google](https://docs.cloud.google.com/docs/authentication/api-keys-best-practices).

Telegram media получает MIME по содержимому файла, а не по metadata источника. Inline разрешён только для известных растровых изображений, audio/video; HTML, SVG, JavaScript и прочие документы скачиваются как `application/octet-stream`. Ответы документов имеют `nosniff`, sandbox CSP, `no-referrer`, запрет framing и `private, no-store` независимо от production-флага общих headers. HTML-отчёты допускают стили, но sandbox запрещает scripts, формы, сетевые ресурсы и доступ к origin приложения. Общий middleware сохраняет специальную CSP ответа.

Сбор Telegram participants в пилоте отключён: публичный username не делает списки subscribers/скрытых участников публичными и не даёт пользователю права общей сессии администратора. Info DTO содержит только выбранные публичные metadata; session flags, access hashes, invite data и кэш пользователей не выдаются.

Граница пилота проверяется локальными регрессионными тестами с подставными внешними API. Проверка реальных credentials, лимитов и доступности каждого включённого источника остаётся отдельным smoke test перед запуском пилота.

## Recommended for production

- Используйте HTTPS, secure cookies, `APP_DEBUG=false`, secret manager и регулярную rotation credentials.
- Рассмотрите `SESSION_ENCRYPT=true`, оценив совместимость и operational impact.
- Зафиксируйте trusted proxies, чтобы IP allowlist/throttling не доверяли подменённым headers.
- Включите MoonShine allowlist; предпочтительно изолируйте admin host сетевым контролем и MFA/VPN на уровне инфраструктуры.
- Ограничьте outbound network access Site Intel; запретите metadata/internal ranges на уровне сети дополнительно к application guard.
- Настройте central logs/alerts без raw request bodies, tokens, JWT, session content и OSINT payloads.
- Разделите workers/credentials по окружениям и минимизируйте права Telegram/Bluesky/Mastodon accounts.
- Шифруйте backups, задайте retention, проверяйте restore и удаление пользовательских данных.
- Выполните threat model для stored XSS/HTML reports, CSV/Excel formula injection, SSRF и abuse of expensive endpoints.
- Проверьте legal basis, terms of service и rate limits каждого источника.

## Credentials и чувствительные данные

Никогда не коммитьте `.env`, `TELEGRAM_API_HASH`, MadelineProto session files, app passwords, API tokens, database dumps и `storage/app/private` snapshots. Shifr JWT inspection декодирует данные локально в request lifecycle, но UI/HTTP/request logging policy всё равно следует проверить перед обработкой реальных secrets.

## Reporting

Не публикуйте exploit details в публичном issue до согласования. В репозитории не найден отдельный vulnerability disclosure address; maintainer должен определить приватный канал перед публичным release.
