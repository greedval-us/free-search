# Установка и первый запуск

## Требования

- PHP 8.3 (ограничение `composer.json`: `^8.3`);
- Composer 2;
- Node.js 22 и npm — это версия CI;
- SQLite для конфигурации по умолчанию либо другая поддерживаемая Laravel БД;
- системные PHP extensions, запрошенные Composer dependencies.

## Установка

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Для SQLite создайте пустой `database/database.sqlite`, если его ещё нет, затем выполните:

```bash
php artisan migrate
```

Не используйте локальный `.env` из чужого окружения: он может содержать credentials и machine-specific paths.

## Запуск

```bash
composer run dev
```

Composer запускает три процесса через `concurrently`: `php artisan serve`, `php artisan queue:listen --tries=1` и `npm run dev`. Альтернатива — запускать web server, queue worker и Vite раздельно.

`composer run setup` устанавливает зависимости, копирует `.env`, генерирует ключ, выполняет `migrate --force` и frontend build. Скрипт не создаёт SQLite-файл явно, поэтому проверьте его наличие заранее.

## Интеграции

Приложение запускается без всех внешних credentials, но соответствующие сценарии будут недоступны или вернут configuration error. Настройте только нужные интеграции по [configuration reference](configuration.md). Для Telegram дополнительно создайте session: [Telegram sessions](operations/telegram-session.md).

## Минимальная проверка

```bash
php artisan test
npm run test:unit
npm run types:check
npm run build
```

Откройте `/up`, `/`, зарегистрируйте тестового пользователя, подтвердите email способом, подходящим локальному mail driver, и проверьте `/dashboard`.
