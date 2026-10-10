# Parser recovery, storage и технические пределы

## Перепроверка исходных замечаний

| Наблюдение | Подтверждение | Изменение |
| --- | --- | --- |
| Recovery зависит от открытой страницы | `ParserRunExecutionCoordinator::recoverStaleRun` вызывался только `start`/`status` | Один `ParserRunRecovery`, CLI и scheduler каждую минуту |
| Истёкший running может ожить | `activeForUser`, recovery и `mutate` не проверяли DB retention | Проверка expiry в active query, под recovery writer lock и перед каждой mutation |
| Worker может повторно применить старый шаг | Уже предотвращалось checkpoint version и execution lock | Сохранены старые payloads, version-specific uniqueness и исходный retry deadline |
| JSON запись и cleanup небезопасны | Основные гарантии уже были реализованы | Сохранены `.lock`, UUID paths, atomic same-directory rename, metadata sync под lock, retry failed deletion |
| Telegram общая блокировка — ошибка | Не подтверждено: session не закреплена за run, consumers используют разные keys | Parser сериализация сохранена; добавлены bounded wait/step measurements |
| Сбор имеет общий технический предел | Были только размеры страниц, step timeout и retry window | Добавлены durable collector attempts, source requests, collected records, total duration, checkpoint/export bytes и XLSX cells |

## Инвентаризация до введения общих бюджетов

| Источник | Page/request ограничения | Особенности повторов и полноты |
| --- | --- | --- |
| Telegram | 50 messages, 20 comments за parser page; comments action получает один post и одну страницу | Первый шаг включает info + messages; public source/discussion resolution делает дополнительные Madeline RPC; action retry до 3 повторов, внутренние повторы Madeline отдельно |
| YouTube | 100 comment threads/replies на page | Один page fetch за шаг; HTTP retries настраиваются, по умолчанию 2; embedded replies добавляются отдельно |
| Bluesky | feed 50, graph/interaction 100, thread depth 6 | Profile/auth/resolve добавляют HTTP calls; до общего бюджета число descendants одной thread response отдельно не ограничивалось |
| Mastodon | 20 statuses на page | Lookup + statuses на первом шаге; до общего бюджета число context descendants одной response отдельно не ограничивалось |
| Shared | Один active run на user/module; job timeout 120s, lock TTL 150s; 3 real exceptions и 1h per-checkpoint retry deadline | Размер page не ограничивает число страниц; opaque cursor history уже останавливает циклы |

Общие бюджеты `ParserRunResourceBudget` не повторяют тарифные ограничения. `resources.stepAttempts` резервируется до I/O и сохраняется при transient failure, сериализации DTO и restart. Максимальная длительность определяется от исходного `createdAt`. Размер проверяется на новом состоянии вместе с потенциальным partial snapshot, чтобы не принимать страницу, после которой stop невозможно сохранить в обычном бюджете. При превышении сохраняется прежний collected checkpoint и неполный результат. Если дублирующий snapshot не помещается, failed budget run хранит data и `result=null`; JSON/XLSX export чисто восстанавливает snapshot из этих data без сети и записи, под прежними export budgets. Status/history оставляют ссылки; collected data не удаляются.

Byte budget — граница принятия collector data. Последующая финализация может добавить status/stage/error/exhausted сверх неё; сохранённые данные при этом сохраняются, дублирующий snapshot удаляется если не помещается. Для exact-cap checkpoint без snapshot и всех пяти причин в поставляемых EN/RU переводах тестируется overhead не более 1024 байт. Это проверка текущих сообщений, не универсальная гарантия для custom translations или legacy oversized files. Duration — допуск между шагами; текущий network call отдельно ограничивается transport/worker timeout.

`PARSER_RUN_MAX_RECORDS=100000` проверяет суммарные cumulative `stats.processed*` всех четырёх источников. Счётчики не сбрасываются между страницами/DTO serialization. Ровно допустимое количество сохраняется; превышение, включая последнюю completed страницу, отклоняет её и строит `collection.complete=false` по прежнему checkpoint. Это число сохранённых категорий записей, а не сырых полученных дубликатов.

`PARSER_RUN_MAX_SOURCE_REQUESTS=100000` реализован отдельным атомарным conditional increment `parser_runs.source_request_count` перед каждым HTTP attempt/retry YouTube, Bluesky и Mastodon, включая auth, и перед явными Telegram RPC. Он не зависит от JSON counters, не сбрасывается новым worker и не расходуется вне parser scope. Исчерпание нельзя скрыть Telegram fallback: scope возвращает typed failure и coordinator сохраняет прежний partial checkpoint. DB migration добавляет нулевое значение для старых runs; обращения до deploy не реконструируются.

JSON/XLSX exports используют 64 MiB byte budget, XLSX — также 1M cell budget. JSON готовится целиком в bounded temporary stream до заголовков; workbook проверяет входной text/cell budget до PhpSpreadsheet и actual file size после генерации. Ошибка не выдаёт усечённый файл и закрывает/удаляет временный ресурс. Независимый review этого этапа охватывает encoder, guards и cleanup; детали тестов в [refactoring-parser-exports.md](refactoring-parser-exports.md).

Обработка parser API network response может выделить память до проверки records/checkpoint. Collector attempt budget и source request counter имеют разные границы: первый ограничивает шаги, второй — фактические видимые попытки. Непрозрачные внутренние retransmissions Madeline, memory одного raw parser response и HTML report exports не ограничиваются этими механизмами.

## Recovery и конкуренция

`app:recover-parser-runs` фиксирует upper ID, выбирает только stale `running` metadata с действующим retention и использует `chunkById`. Dry run не читает большие JSON payloads и не пишет cache/files/DB. Реальный recovery держит execution lock и только на коротком участке stable writer lock: повторно проверяет состояние, cooldown и dispatch. Сбор выполняет очередь. Retry exhaustion финализируется через module processor с version guard; stop/cleanup выигрывают у поздней записи.

Command не обновляет `createdAt`, retry deadline, checkpoint version, run cursor или quota ledger. Ошибка dispatch освобождает cooldown; uniqueness той же версии может быть восстановлена следующей попыткой. Reentrant recovery не захватывает уже занятую execution lock. Новые кандидаты после upper ID обрабатываются в следующем цикле обхода.

После дополнительного замечания о суточном scheduler mutex проход получил `PARSER_RUN_RECOVERY_MAX_PASS_SECONDS=30` (1–3600), monotonic admission deadline и cache position из двух metadata ID на сутки. Верхняя граница сохраняется на цикл, чтобы новые arrivals не отодвигали возврат к busy/error candidates. Каждый допущенный ID сохраняет progress независимо от outcome; завершение диапазона сбрасывает position. Dry run имеет независимый readonly обход. Scheduler lease вычисляется `ceil((passSeconds + 150 + 60) / 60) + 1`, по умолчанию 5 минут, с прежним ключом. Старый orphan не удаляется и сохраняет исходный TTL.

Только recovery использует nonblocking stable writer `LOCK_NB`, включая повторную mutation при retry exhaustion. `writer_busy` не меняет checkpoint, quota или jobs. Обычные writes/stop/cleanup сохраняют ожидание lock. Deadline прекращает допуск новых кандидатов, не прерывает DB/cache/dispatch/filesystem I/O уже начатого кандидата; 150s execution lease — grace для scheduler, не I/O timeout. Нужны реальные component timeouts и мониторинг; multi-host/hard process runtime этими тестами не доказаны.

Поддерживаемый deployment — общий persistent private filesystem и atomic-lock cache для всех процессов одного host. Array cache — тестовый backend. Multi-host с независимыми дисками не поддерживается; сетевой filesystem не проверен этими тестами. DB row locks в MySQL/PostgreSQL и реальное завершение timeout worker не доказываются последовательными SQLite тестами.

## Проверки

Добавлены CLI regressions: autonomous recovery, duplicate/reentrant вызов, occupied execution lock, orphan uniqueness, durable deadline failure, dispatch failure/retry, corrupt/missing checkpoint, expired/terminal protection, dry run, frozen upper-key scan и запрет inline queue. Проверяются expiry и cleanup во время незавершённого collector step.

Resource regressions проверяют duration boundary, сохранение attempt counters при exceptions/restart и потере DTO extra fields, rejected oversized successful step с partial export, exact byte boundary и отсутствие расхода при future step. Для всех четырёх modules проверяются exact record cap, overshoot последней страницы, прежний partial result и cumulative counters старого DTO. Telegram test проверяет сохранённый release-after при пяти ожиданиях и bounded log с причиной ожидания; legacy job без `queuedAt` десериализуется с прежним retry deadline.

Предыдущий узкий PHP 8.3 `artisan test --compact` для `ParserRunResourceBudgetTest` (Feature + Unit), `ParserRunRecoveryTest`, `ParserCheckpointReliabilityTest`, `ParserRunStorageTest`, `ParserRunBackgroundExecutionTest`, `ParserStartReliabilityTest`, `ParserRunSourceRequestBudgetTest`, `ProcessParserRunTest`, `ParserRunConfigTest`: **82 passed, 551 assertions, 11.28 s**. Прогон включает record/byte boundaries, десять finalization-overhead cases (пять причин × EN/RU) и source-budget integration. Отдельные source transport проверки описаны в [refactoring-source-budget.md](refactoring-source-budget.md).

Дополнительный scheduler/recovery этап сначала воспроизвёл суточный TTL, unbounded scan и отсутствие resume (**7 failed / 3 passed**); отдельный RED с новым arrival подтвердил starvation при пересчёте upper ID между проходами. Итоговый узкий PHP 8.3 прогон `ParserRunRecoverySchedulingTest`, `ParserRunRecoveryTest`, `ParserRunStorageTest`, `ParserCheckpointReliabilityTest`, `ParserRunBackgroundExecutionTest`, `ParserRunConfigTest`, `ParserRunBackgroundProcessorRegistryTest`: **50 passed, 268 assertions, 6.74 s**. Scheduler test использует публичный Event mutex API: acquisition без finish оставляет orphan, повтор подавляется до 299s и допускается на 300s. Отдельный PHP fixture держит настоящий `.lock`; команда немедленно пропускает writer, не изменяет checkpoint/quota/jobs и requeues после release. Slow synthetic dispatch проверяет monotonic pause, следующий ID, ошибочного кандидата и wrap при новых arrivals. Dry run не меняет cache. Scoped Pint и `git diff --check` прошли; итоговый общий suite отражается в `refactoring-plan.md`.

## Оставшиеся отдельные границы

Полная общая Madeline session coordination между parser/tracking/report/HTTP consumers не внедрена: выбор session и все consumers нужно сначала привязать к одному доказанному lease protocol. Нельзя заменять parser global lock на session ID только по наличию session pool. Не выполнены production smoke calls, нагрузочный прогон и multi-host validation; реальные sessions/tokens и production DB не использовались.
