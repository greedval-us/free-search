# Telegram Sessions

## Назначение

Проект поддерживает несколько авторизованных `MadelineProto`-сессий для Telegram. Это позволяет:

- распределять нагрузку между несколькими аккаунтами;
- уменьшать риск, что вся интеграция завязана на одну сессию;
- последовательно ротировать запросы между доступными аккаунтами.

Сессии выбираются автоматически через `round-robin`.

Примеры:

- 1 сессия → все запросы идут через нее;
- 2 сессии → `default`, `worker-a`, `default`, `worker-a`;
- 3 сессии → `default`, `worker-a`, `worker-b`, затем по кругу.

Важно: одна конкретная Telegram-операция закрепляется за одной выбранной сессией на весь lifecycle этого запроса. Внутри одного запроса переключения между аккаунтами не происходит.

## Что нужно настроить

В `.env` должны быть заполнены:

- `TELEGRAM_API_ID`
- `TELEGRAM_API_HASH`

Опционально:

- `MADELINEPROTO_SESSION_PATH`
- `MADELINEPROTO_LOG_PATH`

По умолчанию:

- сессии хранятся в `storage/app/private/session/`
- лог MadelineProto хранится в `storage/logs/madeline.log`

## Как создать или обновить сессию

Базовая команда:

```bash
php artisan app:create-telegram-session default
```

Дополнительные именованные сессии:

```bash
php artisan app:create-telegram-session worker-a
php artisan app:create-telegram-session worker-b
```

Если `php` не в `PATH`, можно использовать полный путь к бинарнику:

```powershell
& 'C:\path\to\php.exe' artisan app:create-telegram-session worker-a
```

## Что происходит во время команды

Команда:

1. Нормализует имя сессии.
2. Поднимает клиент MadelineProto для этой сессии.
3. Запрашивает номер телефона.
4. Запрашивает код подтверждения.
5. При необходимости запрашивает 2FA-пароль.
6. В редком случае signup-flow попросит имя и фамилию.
7. Сохраняет готовую сессию в storage.

После успешного логина команда выводит список активных Telegram-сессий, которые сейчас видит приложение.

## Как именуются файлы

По умолчанию:

- `default` → `session.madeline`
- `worker-a` → `session.worker-a.madeline`
- `worker-b` → `session.worker-b.madeline`

Имена нормализуются:

- приводятся к lowercase;
- недопустимые символы заменяются;
- пустое имя превращается в `default`.

## Как работает session pool

Пул сессий собирается автоматически по файлам `session*.madeline` в директории сессий.

Логика выбора:

1. Если валидной сессии нет, интеграция считается не настроенной.
2. Если сессия одна, используется она.
3. Если сессий несколько, выбирается следующая по кругу.

Текущее состояние round-robin хранится в служебном файле:

- `session-pool.state`

Он лежит в той же директории, что и session-файлы.

## Практический пример

Если нужно распределить запросы между тремя аккаунтами:

```powershell
php artisan app:create-telegram-session default
php artisan app:create-telegram-session worker-a
php artisan app:create-telegram-session worker-b
```

После этого Telegram-запросы проекта начнут автоматически чередоваться между этими тремя сессиями.

## Эксплуатационные рекомендации

- Держи хотя бы одну рабочую резервную сессию, если Telegram-модуль критичен для проекта.
- Не удаляй служебный `session-pool.state`, если просто хочешь “переавторизовать” одну из сессий.
- Если аккаунт перевыпускается или теряет авторизацию, проще всего снова запустить `app:create-telegram-session <name>`.
- Храни директорию сессий только в приватном storage.

## Безопасность

- Не коммить session-файлы в git.
- Не делись файлами сессий между окружениями без необходимости.
- Не публикуй `TELEGRAM_API_ID`, `TELEGRAM_API_HASH` и storage-снимки.
- При компрометации аккаунта пересоздай сессию и проверь привязанные ключи/устройства.

## Типовые проблемы

### “Telegram not configured”

Проверь:

- заполнены ли `TELEGRAM_API_ID` и `TELEGRAM_API_HASH`;
- есть ли хотя бы один `session*.madeline` файл;
- совпадает ли путь `MADELINEPROTO_SESSION_PATH` с фактическим storage.

### Сессия не подхватывается

Проверь:

- не изменилось ли имя файла;
- лежит ли файл в правильной директории;
- соответствует ли имя формату `session.<name>.madeline` или `session.madeline`.

### Хочется “переключить” запросы на конкретный аккаунт

Текущая логика пула этого не делает. Выбор происходит автоматически через round-robin. Если нужна другая стратегия, ее нужно реализовывать отдельно на уровне session-pool логики.

## Связанные файлы

- `config/madelineproto.php`
- `app/Support/MadelineProto/MadelineProtoConfig.php`
- `app/Support/MadelineProto/MadelineProtoSessionPool.php`
- `app/Console/Commands/CreateTelegramSession.php`
