# Telegram Sessions

## Назначение

Проект поддерживает несколько авторизованных Telegram-сессий для MadelineProto.
Это нужно, чтобы распределять нагрузку между несколькими аккаунтами и снижать риск блокировок одной сессии.

Запросы к Telegram распределяются автоматически по `round-robin`:

- 1 сессия: все запросы идут через нее
- 2 сессии: запросы идут по очереди `1/2/1/2`
- 3 сессии: запросы идут по очереди `1/2/3/1/2/3`

Одна конкретная Telegram-операция закрепляется за одной выбранной сессией на все время выполнения. Внутри одного запроса переключения между аккаунтами не происходит.

## Как создать несколько сессий

Создайте базовую сессию:

```bash
php artisan app:create-telegram-session default
```

Создайте дополнительные именованные сессии:

```bash
php artisan app:create-telegram-session worker-a
php artisan app:create-telegram-session worker-b
```

Если `php` не находится в `PATH`, используйте полный путь к исполняемому файлу:

```powershell
& 'C:\greedval\OSPanel\modules\PHP-8.3\php.exe' artisan app:create-telegram-session worker-a
& 'C:\greedval\OSPanel\modules\PHP-8.3\php.exe' artisan app:create-telegram-session worker-b
```

Во время выполнения команды:

1. Введите номер телефона аккаунта.
2. Введите код подтверждения из Telegram.
3. Если включен 2FA, введите пароль.
4. После успешного логина сессия будет сохранена автоматически.

## Где хранятся сессии

По умолчанию сессии лежат в каталоге `storage/app/private/session/`.

Примеры файлов:

- `session.madeline`: сессия `default`
- `session.worker-a.madeline`: сессия `worker-a`
- `session.worker-b.madeline`: сессия `worker-b`

Служебный файл `session-pool.state` хранит текущую позицию round-robin ротации.

## Как это работает в рантайме

Пул сессий собирается автоматически по всем файлам `session*.madeline` в каталоге сессий.

Если найдена только одна валидная сессия, все запросы идут через нее.
Если найдено несколько сессий, каждая новая Telegram-операция берет следующую сессию по кругу.

Никаких дополнительных feature flag или конфигов для включения ротации не требуется.

## Практический пример

Если вы хотите распределить нагрузку на три аккаунта:

```powershell
& 'C:\greedval\OSPanel\modules\PHP-8.3\php.exe' artisan app:create-telegram-session default
& 'C:\greedval\OSPanel\modules\PHP-8.3\php.exe' artisan app:create-telegram-session worker-a
& 'C:\greedval\OSPanel\modules\PHP-8.3\php.exe' artisan app:create-telegram-session worker-b
```

После этого Telegram-запросы проекта начнут автоматически чередоваться между этими тремя сессиями.
