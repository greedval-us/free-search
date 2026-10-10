# Итог поэтапного рефакторинга

Работа выполнена в текущем workspace `D:\Program\OSPanel\home\free-search`, ветка `fix-2`, исходный HEAD `415d288ed13f7b8dee9b96dc98c798e3cee16116`. Начальное рабочее дерево было чистым. Коммиты, PR, push, merge и deployment не выполнялись. Production DB, реальные API, пользовательские токены и Telegram sessions не использовались.

Анализ начат через `codebase-memory-mcp`: проверены проект `free-search`, его корень, индекс, архитектура и символы. После этого MCP стал возвращать `Transport closed`; ссылки и цепочки дополнительно проверены по локальным исходникам. Установленные зависимости сохранены: Laravel 13, Inertia 3, Vue 3.5, PHP 8.3.30, PHPUnit 12. Массового обновления зависимостей и форматирования репозитория нет.

## 1. Что подтвердилось

| Замечание | Результат перепроверки |
| --- | --- |
| Возврат квоты после полуночи использует новый день | Подтверждено; существовавший возврат также не идентифицировал отдельное списание |
| Recovery зависит от обращения к start/status | Подтверждено; дополнительно закрыто возобновление истёкших running |
| HTTP Site Intel загружает неограниченное тело | Подтверждено для обоих клиентов; WHOIS имел отдельный предел |
| Расписания дублируют все правила | Частично: validation и owner-first lifecycle были копиями; календарь, queue safety и доставка уже общие |
| Общая блокировка Telegram — ошибка | Не подтверждено; это ограничение пропускной способности, а session pool не обеспечивает независимость всех consumers |
| Файловое хранилище требует замены | Не подтверждено: stable lock, atomic rename, UUID paths и cleanup coordination уже существовали |
| Нужны общие ресурсные пределы | Подтверждено: page sizes и retry windows не ограничивали весь run, checkpoint и export |
| Checkout feature flag включает рабочую оплату | Не подтверждено: UI вёл на placeholder; рабочего платёжного обработчика нет |
| Конкурентные квоты достаточно проверить SQLite | Не подтверждено; реальный шестипроцессный тест MySQL дополнительно обнаружил deadlock |

## 2. Реализованные этапы и ключевые файлы

1. **Квоты.** `FeatureUsageCounter` создаёт receipt в транзакции списания и возвращает только исходный дневной расход, однократно. Контекст проходит через `FeatureAccessDecision`, middleware, parser start/dispatch и пять генераторов отчётов. Старый небезопасный refund по одному resource удалён. Staff bypass, операции без расхода и прежняя same-day eligibility scheduled reports сохранены. Инициализация daily row использует upsert вместо INSERT IGNORE: на MySQL это устраняет shared-to-exclusive conversion deadlock при одновременном первом списании, без новых связей порядка owner locks. Подробности: [quota](refactoring-quota.md).
2. **Recovery и retention.** `ParserRunRecovery` общий для HTTP и `RecoverParserRuns`; scheduler вызывает `app:recover-parser-runs` каждую минуту. Ограниченная metadata-выборка по ID с фиксированной верхней границей, dry run, execution/writer locks, version cooldown, uniqueness, исходный retry deadline и partial failure. Поздний worker не продлевает истёкшую запись. Сбор остаётся в очереди. Подробности: [parser recovery](refactoring-parser.md).
3. **Site Intel.** `SiteIntelHttpClient`, `SiteIntelBoundedTransport` и `SiteIntelBoundedResponseStream` ограничивают декодированное тело при записи transport. Redirect guard, pinned IP, TLS, timeouts и ручной redirect loop сохранены. Превышение — локализованный HTTP 503, а не обрезанный успешный анализ. Подробности: [HTTP limits](refactoring-site-intel.md).
4. **Расписания.** `ReportScheduleRules` и `ReportScheduleLifecycle` используются Telegram, YouTube, Bluesky, Mastodon, Site Intel и News / Media Intel. Общие validation, pause/resume/delete и ownership имеют одну реализацию. Module-specific source normalization, доступ, create/runNow/occurrence и генерация остаются в адаптерах. Сравнение правил: [scheduled reports](architecture/scheduled-reports.md).
5. **Ресурсы и измерения.** `ParserRunResourceBudget` ограничивает durable collection attempts, накопленные normalized records, длительность и checkpoint с потенциальным snapshot. `ParserRunSourceRequestBudget` резервирует фактические явные HTTP/RPC attempts в DB до I/O, включая прикладные повторы. Budget не сбрасывается новым worker/recovery. `ParserRunOverlapMiddleware` сохраняет Telegram serialization и добавляет ограниченные измерения ожидания/длительности без collected contents. Подробности границы запросов: [source budget](refactoring-source-budget.md).
6. **Export.** `JsonExportEncoder` готовит полный bounded JSON во временном stream до headers и сохраняет прежние pretty bytes. `ParserExportBudget` проверяет payload text и XLSX cells до workbook, затем реальный artifact size до response; ошибка закрывает/удаляет собственные временные ресурсы. Форматы четырёх parser exports и partial flags сохранены. Подробности: [exports](refactoring-parser-exports.md).
7. **Billing.** `BillingController`, `PlaceholderController` и `Billing.vue` согласованно отражают отсутствие checkout. Удалены неработающие purchase cards/links; token activation, авторизация и существующие props сохранены. EN/RU объяснение обновлено. Провайдер оплаты не подключался.

## 3. Какие правила теперь общие

- Идентичные interval/time/timezone проверки шести schedule services.
- Owner-first транзакции pause/resume/delete с явным модульным callback.
- HTTP transport/read budget двух Site Intel clients.
- Один HTTP/CLI алгоритм recovery через существующие store/processor registries.
- Receipt-based refund всех существующих counted callers.
- Общие technical run/export budgets поверх существующих source DTO/stats и config.

Общие компоненты не импортируют конкретные source-модули. Универсальный BaseService, новая биллинговая система и вторые параллельные версии перенесённых правил не добавлены.

## 4. Регрессии

Новые `FeatureUsageReceiptTest`, `FeatureUsageReceiptMigrationTest` проверяют midnight с независимым новым дневным расходом, duplicate/serialized refund, owner isolation, zero floor, bypass/no-debit, dispatch failure и legacy backfill пяти report tables. `FeatureUsageConcurrencyTest` запускает шесть процессов против отдельной MySQL/MariaDB test database.

`ParserRunRecoveryTest` покрывает autonomous CLI, повторный/reentrant recovery, занятую execution lock, orphan uniqueness, retry deadline, dispatch retry, terminal/expired/missing/corrupt checkpoint, cleanup/stop и dry run. Дополнены существующие checkpoint/background/job tests для legacy serialization, expiry и bounded telemetry.

`ParserRunResourceBudgetTest`, `ParserRunSourceRequestBudgetTest` проверяют границы, durable counters после исключения/нового service instance/DTO serialization, реальные HTTP retries и swallowed Telegram fallback, сохранение предыдущих collected data и incomplete result. `ParserExportBudgetTest` и `JsonExportEncoderTest` проверяют точные байты старого JSON, UTF-8/chunk/depth boundaries, XLSX cell/text/artifact limits и cleanup.

`SiteIntelHttpBodyLimitTest`, `SiteIntelHttpBodyLimitHttpTest` и `SiteIntelBoundedTransportTest` проверяют boundary, ложный/отсутствующий Content-Length, controlled stream interruption, gzip через настоящие CurlHandler/StreamHandler на loopback, redirects/IP/TLS и публичные EN/RU ошибки. `ReportScheduleContractTest` запускает одинаковые сценарии на всех шести адаптерах. Billing HTTP/SSR tests проверяют flag true/false и рабочую форму token activation.

## 5. Фактически выполненные проверки

Baseline до изменений: PHP **1550 passed / 7312 assertions**; Vitest **31 files / 311 tests**; Composer Pint, frontend quality и client/SSR build успешны. Начальные sandbox permission/EPERM ошибки не были ошибками приложения; команды повторены с разрешённым доступом к временным файлам OSPanel/Vitest.

Первый полный прогон после изменений выявил одну внесённую регрессию: retry callback в HTTP-клиентах ожидал `Throwable`, но Laravel передаёт `null` для redirect response. Существующий `ScheduledMastodonAnalyticsTest` обнаружил её; тип исправлен на `?Throwable` во всех трёх клиентах. Это отделено от чистого baseline и от найденной проблемы исходного MySQL locking.

Финальные проверки выполнены после исправления регрессии:

| Команда / среда | Фактический результат |
| --- | --- |
| `php artisan test --compact` | 1676 passed, 2 skipped, 7939 assertions, 96.31 s |
| `npm run quality:check` | Успешны Prettier, ESLint, vue-tsc и i18n |
| `npm run test:unit` | 32 files, 313 tests passed |
| `npm run build` | Client и SSR успешно; прежнее предупреждение Inertia sourcemap сохраняется |
| `composer run lint:check` | Pint всего PHP-кода успешен |
| `php artisan test --compact --filter=FeatureUsageConcurrencyTest`, MySQL 8.4.8/InnoDB | 2 passed, 50 assertions; six-process debit/refund и schedule cap |
| `git diff --check` | Успешно |

MySQL был отдельным временным instance на loopback, с пустым datadir и restricted synthetic test user. После проверки instance остановлен, временная папка удалена. База приложения и её credentials не использовались. Обычный suite без явной dedicated DB configuration пропускает два opt-in concurrency tests; это не отменяет отдельный фактически выполненный прогон.

Полные команды PHP используют установленный `D:\Program\OSPanel\modules\PHP-8.3\php.exe` и Composer `D:\Program\OSPanel\data\PHP-8.3\default\composer\composer.phar`. Логи проверок находятся в игнорируемом `storage/logs/refactor-*.log`.

## 6. Обновление

Добавлены только новые миграции:

- `2026_10_10_022106_create_feature_usage_receipts_table.php`: receipt ledger, nullable report references и bounded legacy backfill.
- `2026_10_10_023842_add_source_request_count_to_parser_runs_table.php`: durable source counter с default 0, без преобразования старых checkpoint/job payloads.

Перед обновлением приостановить scheduler dispatch, дождаться in-flight HTTP и остановить workers. Затем выложить код, выполнить `php artisan migrate --force`, пересобрать config cache и перезапустить workers/scheduler. Старый refund contract не должен работать одновременно с новым кодом. На workspace миграции выполнялись только тестами в изолированных базах.

Новые optional settings и defaults в `.env.example`/`docs/configuration.md`: recovery batch 100, stale 150s; attempts 10000, source requests 100000, records 100000, duration 86400s, checkpoint 32 MiB, export 64 MiB/1000000 cells; Site Intel response 2 MiB. Они технические, тарифные дневные квоты не изменяют. Для scheduler требуется durable asynchronous queue; для web/workers/recovery/cleanup — одинаковые private files и общий atomic cache lock backend. Перед включением проверить `app:recover-parser-runs --dry-run`. Подробный порядок: [deployment](deployment.md).

## 7. Ограничения и отдельный backlog

- Legacy reports не содержат historical role/quota mapping или identity исходного списания; backfill использует сохранённую дату и действующую конфигурацию. Исключительные случаи изменения исторических ролей/настроек требуют проверки. Новые counters старых runs начинают с нуля: прошлые outbound requests/attempts восстановить нельзя.
- Record cap считает накопленные нормализованные `processed*` категории; это не точный счётчик всех сырых received duplicates. Source count охватывает явные вызовы приложения и HTTP retries, но не внутренние непрозрачные MadelineProto retransmissions.
- Parser APIs могут материализовать одну большую response до record/checkpoint проверки. Site Intel transport ограничен при чтении; аналогичный универсальный RAM bound всех parser API/SDK не заявляется. XLSX caps ограничивают cells/text/artifact, не измеряют точную RAM PhpSpreadsheet; готовый ZIP проверяется после генерации.
- Checkpoint byte cap проверяется при принятии collected data с потенциальным snapshot. Финализация сохраняет уже принятые данные: служебные status/error/exhausted могут увеличить размер сверх cap. Для пяти причин в поставляемых EN/RU локалях проверена добавка не более 1024 байт; для произвольных переводов и legacy oversized files это не hard cap.
- Duration проверяется между шагами; жёсткое прерывание текущего шага зависит от worker timeout/signal support. Windows tests не доказывают Linux process timeout behaviour.
- Telegram parser serialization сохранена. Общий session lease protocol для tracking/report/HTTP/parser consumers требует отдельной работы; блокировка по session ID пока небезопасна.
- Поддерживается общий persistent private filesystem одного application host и общий atomic cache backend. Независимые локальные диски, network filesystem locking, PostgreSQL contention, production-load и multi-host guarantees не проверены.
- Реальные API smoke tests/пользовательские sessions не выполнялись согласно границам задачи. Полноценный checkout требует отдельного продуктового решения и интеграции провайдера.
