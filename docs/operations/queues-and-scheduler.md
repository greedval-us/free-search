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
- `app:recover-parser-runs` каждую минуту с scheduler overlap protection; lease по умолчанию 5 минут. Та же recovery-логика вызывается `start`/`status`.

Production infrastructure должна вызывать каждую минуту:

```bash
php artisan schedule:run
```

Laravel Scheduler не является отдельным daemon автоматически. Проверяйте timezone приложения и отсутствие overlapping infrastructure invocations.

## Monitoring

Контролируйте queue depth, failed jobs, длительность steps, runs со статусом `running` без `last_activity_at` updates, recovery outcomes (`execution_busy`, `cooldown`, `requeued`, `failed_retry_exhausted`, `recovery_error`), cleanup summary logs, свободное место private disk и доставку subscription notifications. Step duration и Telegram module wait логируются с sampling не чаще раза в минуту на run/reason; содержание сообщений и запросов не включается.

Ручная проверка и восстановление:

```bash
php artisan app:recover-parser-runs --dry-run
php artisan app:recover-parser-runs
```

`PARSER_RUN_RECOVERY_BATCH_SIZE=100` ограничивает размер DB batch. `PARSER_RUN_RECOVERY_STALE_AFTER_SECONDS=150` задаёт минимальный возраст кандидата и автоматически учитывает lock TTL/step delay. Dry run показывает metadata-кандидатов, поэтому занятые locks или повреждённый JSON могут быть отсеяны только при реальном запуске. Команда требует durable asynchronous queue; queue-disabled mode ничего не отправляет. Ошибка одного dispatch не прерывает остальные кандидаты, но даёт ненулевой exit code и очищает cooldown для повторной попытки.

`PARSER_RUN_RECOVERY_MAX_PASS_SECONDS=30` (1–3600 секунд) задаёт monotonic admission budget прохода. Команда завершает уже допущенного кандидата, затем останавливается с `time budget reached`. Cache на сутки сохраняет последние metadata ID и upper ID текущего цикла; следующий tick продолжает его, затем возвращается к нижним ID. Busy/error candidates не удерживают начало каждого прохода, а новые runs ждут следующего цикла. Dry run не читает и не меняет resume cursor. Recovery не ждёт занятый `.lock`, выводит `writer_busy`; после освобождения candidate будет перепроверен в следующем цикле. Обычные writer/stop/cleanup locks сохраняют blocking semantics.

Scheduler mutex TTL вычисляется из того же budget: `ceil((seconds + 150 + 60) / 60) + 1` минут (5 по умолчанию). Execution lease 150s используется как grace, а не обещание candidate timeout. Аварийный новый mutex сам истечёт; не очищайте cache locks вслепую. Прежнее имя mutex сохранено, поэтому orphan старой версии с суточным TTL не сокращается задним числом. Deadline допуска не прерывает зависший DB/cache/queue/filesystem I/O; проверяйте timeouts этих компонентов и операционный мониторинг. Утилита не является OS supervisor/hard runtime limiter.

`PARSER_RUN_MAX_STEP_ATTEMPTS=10000`, `PARSER_RUN_MAX_RECORDS=100000`, `PARSER_RUN_MAX_DURATION_SECONDS=86400`, `PARSER_RUN_MAX_CHECKPOINT_BYTES=33554432` — технические пределы, не тарифные квоты. Счётчик попыток хранится в checkpoint; число записей берётся из cumulative `stats.processed*`. `PARSER_RUN_MAX_SOURCE_REQUESTS=100000` ограничивает фактические HTTP attempts, включая auth/retries, и явные Telegram RPC через атомарный DB counter. Счётчики не сбрасываются перезапуском/recovery. Превышение сохраняет последний допустимый partial checkpoint и явный `failed`; увеличивайте параметры осознанно вместе с памятью worker и диском. Существующие большие файлы не мигрируются и не обрезаются. Непрозрачные внутренние retransmissions Madeline и память одного parser API ответа этим не ограничиваются.

`PARSER_RUN_MAX_EXPORT_BYTES=67108864` ограничивает полный JSON и готовый XLSX; `PARSER_RUN_MAX_EXPORT_CELLS=1000000` и предварительный byte guard проверяются до workbook. Экспорт отклоняется до download headers, временные файлы отказа закрываются/удаляются. HTML reports требуют отдельного ограничения; эти параметры относятся к JSON/XLSX.

Checkpoint byte cap проверяет новые collector data; terminal metadata может немного увеличить уже принятый документ. Для поставляемых EN/RU локалей добавка проверена в пределах 1024 байт, без удаления сохранённых данных. К старым oversized files или произвольным новым переводам эта измеренная граница не относится. Duration проверяется между шагами; завершение текущего вызова зависит от transport/worker timeout.

Если terminal budget metadata не позволяет хранить дублирующий snapshot, JSON/XLSX download восстанавливает его из сохранённых data без нового сбора и записи checkpoint. Ссылки остаются доступны в status/history; export budgets сохраняются.

Web, worker, recovery и cleanup должны работать с одним persistent private storage и координирующим cache backend. Не используйте независимые local disks или `array` cache для нескольких процессов. Telegram parser lock сохраняет сериализацию parser jobs; tracking, reports и HTTP не координируются этим class-specific key. См. [parser architecture](../architecture/parser-runs.md) и [parser refactoring evidence](../refactoring-parser.md).

После остановки старых workers примените новые migrations, включая `parser_runs.source_request_count`, обновите config cache и перезапустите long-running workers, чтобы они получили recovery/resource settings и новый middleware. Формат queued `ProcessParserRun` и старые checkpoint JSON совместимы; дополнительный `queuedAt` отсутствует у старых сообщений и измерение времени ожидания для них остаётся неизвестным. DB counter старых запусков начинается с нуля: исторические сетевые обращения до deploy восстановить невозможно.
