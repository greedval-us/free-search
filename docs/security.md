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
