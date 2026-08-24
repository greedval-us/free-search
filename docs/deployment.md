# Production deployment

В репозитории нет Docker/Kubernetes, web-server или process-manager templates. Выбор nginx/Apache, systemd/Supervisor, containers и secret store остаётся задачей инфраструктуры.

## Release sequence

1. Установите PHP 8.3+, Composer 2, Node 22/npm и необходимые extensions.
2. Создайте production `.env`; не копируйте secrets из development.
3. Настройте persistent database, private storage, cache, sessions, queue, mail и включённые external integrations.
4. Установите зависимости и соберите assets:

```bash
composer install --no-dev --classmap-authoritative
npm ci
npm run build
php artisan migrate --force
php artisan optimize
```

5. Дайте runtime-пользователю доступ на запись к `storage` и `bootstrap/cache`, не открывая private storage через web server.
6. Запустите постоянный queue worker. Если `PARSER_RUN_QUEUE_NAME` отличается от default, включите эту очередь в worker configuration.
7. Настройте вызов `php artisan schedule:run` каждую минуту.
8. Перезапустите long-running workers после deploy (`queue:restart` или эквивалент вашего process manager).

## Security baseline

- `APP_ENV=production`, `APP_DEBUG=false`, HTTPS и `SESSION_SECURE_COOKIE=true`;
- уникальный `APP_KEY`, restricted `.env`, защищённые DB/API credentials;
- рабочие `MAIL_*` для verification/reset/security notifications;
- отдельные MoonShine prefix/domain, allowlist и проверенные proxy/IP settings;
- egress restrictions и request limits для Site Intel;
- private backups для database и Parser Run/session storage;
- соблюдение retention и policies внешних источников.

Подробное разделение implemented/recommended: [Security](security.md).

## Verification checklist

- `/up` отвечает успешно, главная и compiled assets доступны;
- migrations применены; cache/session/queue tables доступны;
- registration/login/email verification/password reset/2FA работают;
- blocked user теряет web session;
- Feature Access возвращает ожидаемые 403/429 и считает usage;
- каждый включённый источник проходит smoke test без утечки credentials в logs/UI;
- Parser Run проходит `start → completed`, доступен history и JSON/Excel export;
- stop и failed states корректны; cleanup dry-run показывает ожидаемые targets;
- queue failures видны оператору, Scheduler выполняет обе команды;
- MoonShine guard, throttle, allowlist и audit resources проверены;
- backup и restore database/private storage проверены практически.

Production rollout должен учитывать quota и terms Telegram, YouTube, Bluesky, Mastodon, RSS providers и NewsAPI.
