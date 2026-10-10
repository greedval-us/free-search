# План рефакторинга Free Search

Исходное состояние: 2026-10-10, ветка `fix-2`, HEAD `415d288ed13f7b8dee9b96dc98c798e3cee16116`, рабочее дерево чистое. Анализ начат через `codebase-memory-mcp`, проект `free-search`, корень проверен. После успешных `index_status`, `get_architecture`, `search_graph` сервер начал возвращать `Transport closed`; дальнейшие выводы проверяются по исходникам.

## Baseline

- PHP проекта: OSPanel PHP 8.3.30; зависимости установлены, PHPUnit 12, Laravel 13; тесты используют SQLite `:memory:`, array cache, sync queue и HTTP doubles.
- `npm run quality:check`: успешно.
- `npm run test:unit`: 31 файл, 311 тестов успешно после повторного запуска вне sandbox (первый запуск остановлен EPERM при rename временных файлов).
- `npm run build`: client и SSR успешно; предупреждения sourcemap Inertia/plugin timings уже присутствуют.
- `composer run lint:check`: успешно вне sandbox; первоначальный запуск заблокирован временной папкой OSPanel.
- `php artisan test --compact`: 1550 passed, 7312 assertions, 108.30s вне sandbox; первоначальный запуск остановлен до тестов из-за Windows Process temp permissions.

## Перепроверенные замечания и этапы

| Этап | Подтверждение и файлы | Изменение и проверка |
| --- | --- | --- |
| 1. Квоты | `FeatureUsageCounter::release` повторно вычисляет дату; callers в middleware, coordinator и пяти генераторах отчётов | Durable receipt исходного списания, идемпотентный возврат, перенос всех callers, недеструктивная миграция; midnight/duplicate/bypass/ownership/dispatch регрессии |
| 2. Recovery | `ParserRunExecutionCoordinator::recoverStaleRun` вызывается только HTTP; retention не проверяется при recovery | Общий механизм HTTP/CLI, батчи metadata по ID, dry-run, execution lock и checkpoint CAS, неизменный retry deadline; CLI/lock/cleanup/dispatch тесты |
| 3. Site Intel | `SiteHealthHttpInspector`, `SeoAuditHttpFetcher` буферизуют неограниченное тело | Общий HTTP transport с ограничением декодированных байтов при записи, безопасная публичная ошибка; stream boundary, gzip, redirects/SSRF regression |
| 4. Расписания | Шесть сервисов повторяют input validation и lifecycle mutations | Небольшие общие компоненты через композицию; module-specific доступ, нормализация и occurrence остаются в модулях; contract-тесты всех шести реализаций и существующие календарные/ownership тесты |
| 5. Telegram/storage | Stable file lock, atomic rename, UUID checks, версии checkpoint и retry deadline уже есть; Telegram parser lock не координирует все остальные потребители | Сохранить сериализацию, документировать реальную топологию/границы; инвентаризировать бюджеты, добавлять лишь отсутствующие технические ограничения с сохранением partial snapshot |
| 6. Billing | `Billing.vue` при включении flag отправляет покупки на `/settings/placeholder`; платёжного обработчика нет | Не выдавать flag за готовый checkout, удалить placeholder purchase UI, сохранить payload props и token activation; HTTP/SSR регрессии |

## Что уже общее

| Правило | Текущее состояние | Обязательные различия |
| --- | --- | --- |
| Интервалы, календарь, timezone/DST | `ReportInterval`, `ReportCalendar` уже объединены | Социальные завершённые периоды против последних проверок Site Intel/News |
| Queue safety | `ReportQueueSafety` уже общий | Различные timeout/lease и публичные module errors |
| Готовые отчёты, HTML/JSON и bot delivery | Общие providers/renderer/outbox уже существуют | Source-specific модели, доступ, имя файла и шаблон |
| Schedule mutations/validation | Копии в шести сервисах | Owner lock до schedule lock, отдельные лимиты и source-specific access, в News нет quota inspect |

## Инварианты и порядок

- Не менять маршруты, JSON/error/status/export форматы, авторизацию, ownership и коммерческие квоты. Сохранить старые queued payloads/checkpoints.
- Не выполнять реальные внешние API, production DB, чтение токенов/сессий, коммиты, push, merge или deployment.
- Для исправлений: воспроизводящий тест → RED → минимальный fix → GREEN. Для extraction: contract-тесты существующего поведения → перенос → повторная проверка.
- Backend тесты с `Storage::fake` запускать последовательно: fake-диски общие между процессами. PHP runtime и временная папка требуют разрешённого запуска вне sandbox.
- Каждый этап заканчивается кодом, тестами и документацией; неизвестные продуктовые решения выносить отдельно, не останавливая независимые изменения.

## Финальная проверка и оставшиеся задачи

- [x] Квоты и миграционная совместимость; MySQL contention RED → upsert fix → 2 passed / 50 assertions.
- [x] Автономное recovery, Telegram/storage/resource audit и общие durable бюджеты.
- [x] Ограничение HTTP-ответов с native gzip/stream проверками.
- [x] Расписания, Billing и JSON/XLSX export guards.
- [x] `composer run lint:check`, `php artisan test --compact` (1676 passed, 2 opt-in skips, 7939 assertions), `npm run quality:check`, `npm run test:unit` (313 passed), `npm run build`, `git diff --check`.
- [x] Обновить конфигурацию, `.env.example`, deployment и точный итог проверок.

Проверки SQLite не доказывают row locking MySQL/PostgreSQL. Многопроцессные quota/schedule проверки выполнены дополнительно на отдельном временном MySQL 8.4.8, instance остановлен после тестов. Production-нагрузка, PostgreSQL, raw parser response memory, непрозрачные Madeline retries и общая session coordination остаются отдельными границами. Полноценная платёжная интеграция остаётся отдельной продуктовой задачей. Подробные изменения, тесты, rollout и ограничения: [итоговый отчёт](refactoring-report.md).
