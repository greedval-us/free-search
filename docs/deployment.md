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
9. Запустите Inertia SSR как постоянный процесс и перезапускайте его после каждой сборки:

```ini
[program:free-search-ssr]
directory=/var/www/free-search
command=/usr/bin/php artisan inertia:start-ssr
user=www-data
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
redirect_stderr=true
stdout_logfile=/var/www/free-search/storage/logs/ssr.log
```

После изменения Supervisor-конфигурации:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart free-search-ssr
php artisan inertia:check-ssr
```

`npm run build` собирает и клиентский manifest, и `bootstrap/ssr/app.js`.

## Nginx assets

Для сжатия и длительного кэширования хешированных Vite-assets добавьте в HTTPS server block:

```nginx
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css application/json application/javascript application/xml image/svg+xml;

location ^~ /build/assets/ {
    try_files $uri =404;
    expires 1y;
    add_header Cache-Control "public, max-age=31536000, immutable";
}
```

Перед reload всегда выполняйте `sudo nginx -t`.

## Security baseline

- `APP_ENV=production`, `APP_DEBUG=false`, HTTPS и `SESSION_SECURE_COOKIE=true`;
- `SECURITY_HEADERS_ENABLED=true`, `SECURITY_HSTS_ENABLED=true`, `SECURITY_CSP_ENABLED=true`;
- `INERTIA_SSR_ENABLED=true`, `INERTIA_SSR_URL=http://127.0.0.1:13714`;
- уникальный `APP_KEY`, restricted `.env`, защищённые DB/API credentials;
- рабочие `MAIL_*` для verification/reset/security notifications;
- отдельные MoonShine prefix/domain, allowlist и проверенные proxy/IP settings;
- egress restrictions и request limits для Site Intel;
- private backups для database и Parser Run/session storage;
- соблюдение retention и policies внешних источников.

Подробное разделение implemented/recommended: [Security](security.md).

## Verification checklist

- `/up` отвечает успешно, главная и compiled assets доступны;
- `inertia:check-ssr` подтверждает работу SSR, а исходный HTML главной содержит title, description и canonical;
- migrations применены; cache/session/queue tables доступны;
- registration/login/email verification/password reset/2FA работают;
- blocked user теряет web session;
- Feature Access возвращает ожидаемые 403/429 и считает usage;
- каждый включённый источник проходит smoke test без утечки credentials в logs/UI;
- Parser Run проходит `start → completed`, доступен history и JSON/Excel export;
- stop и failed states корректны; cleanup dry-run показывает ожидаемые targets;
- queue failures видны оператору, Scheduler выполняет subscription, parser cleanup и request log pruning;
- MoonShine guard, throttle, allowlist и audit resources проверены;
- backup и restore database/private storage проверены практически.

Production rollout должен учитывать quota и terms Telegram, YouTube, Bluesky, Mastodon, RSS providers и NewsAPI.
