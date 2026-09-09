# Queue и Scheduler

## Queue

`.env.example` использует database queue. При `PARSER_RUN_QUEUE_ENABLED=true` start dispatches `ProcessParserRun` в `PARSER_RUN_QUEUE_NAME`; один job выполняет один collection step и dispatches следующий после configured delay.

```bash
php artisan queue:work
```

Если используется отдельная очередь:

```bash
php artisan queue:work --queue=parser-runs,default
```

Worker должен работать постоянно и перезапускаться после deploy. Следите за `failed_jobs`; MoonShine имеет read-only operational resources для queue/failed jobs. Таймаут задачи составляет 120 секунд. Ожидание Telegram overlap lock не расходует лимит ошибок: задача ограничена окном повторов в один час и тремя реальными исключениями.

`retry_after` для database, Redis и Beanstalkd должен быть больше таймаута задачи. Значение по умолчанию составляет 150 секунд; проверьте `DB_QUEUE_RETRY_AFTER`, `REDIS_QUEUE_RETRY_AFTER` и `BEANSTALKD_QUEUE_RETRY_AFTER` при деплое.

При переходе на атомарное JSON-хранилище сначала дождитесь завершения текущих шагов и остановите старые воркеры. Старые и новые процессы записи нельзя запускать одновременно: новый код использует стабильный файл `.lock` в каталоге пользователя вместо блокировки самого JSON. После обновления кода и конфигурации запустите воркеры снова. JSON-данные и таблица метаданных не требуют переноса.

При `PARSER_RUN_QUEUE_ENABLED=false` status polling advances run синхронно. Этот режим полезен для локальной диагностики, но меняет timing/failure model.

## Scheduler

`routes/console.php` задаёт:

- `app:notify-subscription-expiry` ежедневно в `09:00`;
- `app:cleanup-parser-runs` ежедневно в `PARSER_RUN_CLEANUP_SCHEDULE` (`03:30`).

Production infrastructure должна вызывать каждую минуту:

```bash
php artisan schedule:run
```

Laravel Scheduler не является отдельным daemon автоматически. Проверяйте timezone приложения и отсутствие overlapping infrastructure invocations.

## Monitoring

Контролируйте queue depth, failed jobs, длительность steps, runs со статусом `running` без `last_activity_at` updates, cleanup summary logs, свободное место private disk и доставку subscription notifications. Automatic stale-run reconciler в текущем code не подтверждён.
