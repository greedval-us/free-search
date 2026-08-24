# Telegram sessions

Telegram integration использует MadelineProto user sessions. API ID/hash недостаточны: application должен видеть хотя бы один авторизованный session file.

## Создание

```bash
php artisan app:create-telegram-session default
php artisan app:create-telegram-session worker-a
php artisan app:create-telegram-session --list
```

Команда интерактивно проходит MadelineProto authorization. По умолчанию files находятся под private storage path из `MADELINEPROTO_SESSION_PATH`:

- `default` → `session.madeline`;
- `worker-a` → `session.worker-a.madeline`.

Session names normalizes to lowercase/safe characters. Pool находит `session*.madeline`; при нескольких sessions выбирает их round-robin и хранит cursor в `session-pool.state`.

## Configuration

```dotenv
TELEGRAM_API_ID=
TELEGRAM_API_HASH=
MADELINEPROTO_SESSION_PATH=app/private/session/
MADELINEPROTO_LOG_PATH=logs/madeline.log
```

Paths интерпретируются configuration classes относительно application storage rules. Проверяйте фактический resolved path в окружении, особенно при переносе с Windows на Linux.

## Operations и security

- держите session directory на private persistent disk;
- не коммитьте и не копируйте sessions между окружениями без необходимости;
- ограничьте filesystem permissions runtime user;
- храните резервную session, если module критичен;
- при compromise завершите Telegram sessions/devices, rotate credentials при необходимости и создайте session заново;
- monitor flood/rate-limit и account authorization failures.

Если integration сообщает misconfiguration, проверьте API credentials, наличие `session*.madeline`, resolved directory, permissions и authorization state. Pool не поддерживает ручной выбор конкретного account на request: strategy сейчас round-robin.
