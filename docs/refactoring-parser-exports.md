# Ресурсные пределы экспорта parser runs

## Подтверждённая проблема

Четыре parser controller используют `AbstractParserController`, `HandlesParserDownloads` и `ExcelWorkbookService`. JSON ранее целиком проходил `json_encode` внутри callback уже после отправки download headers. XLSX строился через Maatwebsite Excel/PhpSpreadsheet без предела числа ячеек или размера файла. Предел checkpoint сам по себе не ограничивал pretty JSON и расширение данных в workbook.

Воспроизводящий `ParserExportBudgetTest` до реализации дал **2 failed / 3 passed**: JSON больше настроенного byte budget и XLSX больше cell budget возвращались как успешные downloads.

## Реализация и совместимость

- `ParserExportBudget` использует `ParserRunConfig`: `PARSER_RUN_MAX_EXPORT_BYTES=67108864` (64 MiB) и `PARSER_RUN_MAX_EXPORT_CELLS=1000000`. Значения ограничены снизу единицей. Бюджет ячеек включает headings и каждую ячейку строк всех листов. Один миллион выбран как консервативный default совместимости для существующего checkpoint до 32 MiB; это не утверждение о допустимом потреблении памяти PhpSpreadsheet на production.
- JSON кодируется по частям в `tmpfile` с проверкой накопленных фактических байтов до каждой записи. Длинные UTF-8 строки обрабатываются chunks до 8192 исходных байтов. Полный encoded document не собирается в строку памяти. Документ полностью подготовлен и проверен до создания download response, поэтому превышение не превращается в усечённый успешный JSON.
- Pretty layout, порядок keys/values, Unicode/slashes, escaping, sparse arrays, числа и флаги `collection.status`/`collection.complete` сохраняют прежние байты `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`. Temporary stream закрывается при ошибке кодирования/бюджета, ошибке создания headers и после отправки. Неуспешное кодирование также не становится пустым успешным download.
- XLSX сначала проверяет исходный payload до module builder, затем headings/rows до создания workbook: суммарные bytes строк и ключей ограничены byte budget, а число ячеек — cell budget. Проверка обходит значения без копирования всех строк в новый массив. После генерации проверяется фактический размер XLSX-файла до передачи ответа; oversized artifact удаляется. Формат листов и существующие builder/service interfaces сохранены.
- XLSX byte cap проверяет готовый ZIP-файл, а не останавливает нативную ZIP-запись посередине. Предел памяти до workbook обеспечивают предварительные text/cell budgets; их нельзя считать точным пределом RAM библиотеки. Существующий vendor cleanup обычного успешного download сохранён.
- Превышение возвращает публичный HTTP 503 с `code: "parser_export_limit_exceeded"` и EN/RU сообщением. Export ничего не записывает в parser run и не меняет полный или частичный collected result. Имеющиеся incomplete/partial flags сохраняются; пользователь может открыть результат или повторить download после изменения настроек. Ownership и feature access остаются в прежней цепочке controller/application service.

Parser HTML-export в этих маршрутах отсутствует. HTML analytics reports и report snapshots относятся к другому контуру и в данном изменении не менялись. Новых миграций для exports нет.

## Partial export у границы checkpoint

Проверка на сохранённом checkpoint ровно 4096 байт воспроизвела потерю downloads после resource failure: `ParserRunResourceBudget::fail` сохранял collected `data`, но убирал дублирующий `result`, а download guard требовал готовый snapshot. Все восемь public cases (Telegram, YouTube, Mastodon, Bluesky × JSON/XLSX) завершились ошибкой `result_not_found`.

Теперь `AbstractParserApplicationService::getDownloadPayload` восстанавливает snapshot при чтении failed checkpoint с известной причиной resource exhaustion, отсутствующим `result` и сохранённым массивом `data`. `ParserRunGuard::hasDeferredResult` применяется также к status/history download links. Проверка выполняется на исходном сохранённом run до source DTO, которые не переносят shared resources. При скачивании checkpoint не перезаписывается, quota/cursor/retry budget не меняются. Уже сохранённые failed files из предыдущего релиза поддерживаются без нового marker или миграции.

Все четыре collector snapshot method являются чистыми проекциями сохранённых context/stats/data. Они не вызывают источник и не подставляют новые timestamps; JSON сохраняет исходные строки/поля records. `collection.status` остаётся `failed`, `collection.complete` — `false`; XLSX summary отражает ту же неполноту. Обычные отсутствующие snapshots продолжают давать 404, running download — 409. Восстановленный результат по-прежнему проходит существующие ownership, feature-access и export byte/cell guards.

## Проверки

`ParserExportBudgetTest` и `JsonExportEncoderTest` проверяют границы ниже/ровно/выше JSON byte budget, XLSX cell budget, исходный text budget до вызова Excel и actual artifact byte budget. При превышении отсутствуют download headers, oversized XLSX удаляется, JSON временные streams не остаются открытыми ни после ошибки, ни после отправки. JSON сравнивается побайтно с прежним `json_encode`, включая nested partial payload, Unicode на границе chunk и максимальную поддерживаемую глубину массива.

`ParserBudgetPartialExportTest` проверяет public JSON и настоящий XLSX с полным сохранённым text у byte boundary для всех четырёх источников, incomplete summary, status/history links и отсутствие записи checkpoint после downloads. Дополнительно покрыты старые failed checkpoints всех пяти budget reasons, запрет fallback при running/ordinary missing/corrupt data и действующий export cap после восстановления snapshot.

После исправления partial boundary выполнен совместный прогон `ParserBudgetPartialExportTest`, `ParserRunGuardTest`, Feature/Unit `ParserRunResourceBudgetTest`, `ParserExportBudgetTest`, `JsonExportEncoderTest`, `DocumentResponsesTest`, `ParserExportBuildersTest`: **97 passed / 537 assertions**. Scoped Pint и `git diff --check` прошли.

Существующие `DocumentResponsesTest` и `ParserExportBuildersTest` проверяют download headers и прежние JSON/XLSX builders. Выполнено на OSPanel PHP 8.3: `php artisan test --compact tests/Feature/ParserExportBudgetTest.php tests/Unit/JsonExportEncoderTest.php tests/Feature/DocumentResponsesTest.php tests/Feature/ParserExportBuildersTest.php` — **53 passed / 185 assertions**. Scoped Pint и `git diff --check` прошли. Общий финальный прогон ведётся в `docs/refactoring-plan.md`.

## Обновление

Новые параметры приведены в `.env.example` и справочнике конфигурации. После выкладки пересобрать Laravel configuration cache стандартным способом и перезапустить долгоживущие workers, если они используются. Ограничения exports применяются при запросе download к новым и уже сохранённым parser runs. При необходимости повышать budgets с учётом реальной памяти и диска сервера; сохранённые payloads и queued jobs не требуют преобразования.
