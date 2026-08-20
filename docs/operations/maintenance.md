# Maintenance

## Parser Run cleanup

```bash
php artisan app:cleanup-parser-runs --dry-run
php artisan app:cleanup-parser-runs
```

Команда удаляет expired private JSON file (если существует), затем metadata row. Перед изменением retention всегда запускайте dry-run. Backup/restore должен учитывать DB и private files как связанную пару.

## Subscription notifications

```bash
php artisan app:notify-subscription-expiry
```

Проверьте mail/notification channel и deduplication behavior на staging перед ручным повторным запуском в production.

## Laravel maintenance

После deploy применяйте migrations, обновляйте caches и перезапускайте workers. Регулярно контролируйте failed jobs, queue depth, DB growth (`request_logs`, `feature_usage_daily`, notifications), private disk usage, MadelineProto logs/sessions и admin audit logs.

Не удаляйте storage directories целиком для очистки Parser Runs: используйте lifecycle command, чтобы metadata и files оставались согласованными.
