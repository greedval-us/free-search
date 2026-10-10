# Дополнительные исправления после `99b6069`

Рабочая ветка `fix-2`, исходный commit `99b6069e421ab46873873ef2a116c4efbdd41092`. Этот документ дополняет [исходный отчёт](refactoring-report.md).

## Частичный экспорт

Регрессионный HTTP-тест воспроизвёл ошибку во всех четырёх парсерах: checkpoint ровно 4096 байт сохраняет данные, затем исчерпание step budget добавляет terminal metadata и убирает дублирующий `result`; JSON/XLSX download отвечал `parser_run_result_not_found`.

Теперь download восстанавливает pure snapshot из сохранённого `data` только для failed resource-budget checkpoints без `result`. Snapshot не записывается обратно, сбор данных не запускается. Общий predicate делает links в status/history доступными. Формат collection остаётся `failed` / `complete=false`; running, обычный отсутствующий result, ownership, retention и export limits сохраняют прежние проверки. Поддерживаются уже записанные файлы `99b6069` без нового marker или миграции. Подробности: [exports](refactoring-parser-exports.md).

## Recovery после аварии

`PARSER_RUN_RECOVERY_MAX_PASS_SECONDS=30` ограничивает приём новых кандидатов по монотонному времени, допустимый диапазон 1–3600 секунд. Общий cache cursor хранит последний metadata ID и фиксированный верхний ID текущего цикла. Пропущенные/ошибочные записи продвигают cursor, а новые записи не отодвигают возврат к началу цикла. После конца диапазона scan начинается заново; dry-run cursor не меняет.

Scheduler overlap lease вычисляется из того же бюджета прохода с запасом для текущего кандидата; default 5 минут вместо 1440. Это admission deadline, а не принудительный process timeout: filesystem/DB/cache/queue I/O также должны иметь рабочие timeout. Истечение scheduler lease не отменяет per-run locks и checkpoint version guard. Занятый stable writer recovery пропускает без ожидания. Подробности и crash regression: [parser recovery](refactoring-parser.md).

Ранее созданная scheduler-блокировка сохраняет записанную старым кодом дату expiry. Перед выкладкой следует завершить старый scheduler-процесс; новая настройка применяется к новым lease. Автоматическая очистка чужих locks не выполняется.

## Миграционный переход и CI

Для перехода используются baseline migrations и статические синтетические queued/checkpoint fixtures от `415d288`. Тест начинает с отдельной пустой MySQL, применяет старый manifest обычным `migrate`, заполняет тестовые данные, затем применяет текущие migrations. Он не подменяет upgrade последовательностью `migrate:fresh` или `down/up`. Старые применённые migration-файлы не изменены.

CI получил отдельный job `migration-upgrade` с disposable `mysql:8.4`, ограниченным тестовым пользователем и dedicated database `free_search_refactor_upgrade_test`. Запуск: `php vendor/bin/phpunit --filter=MigrationUpgradeTest`; подключение задаётся явно через `MIGRATION_UPGRADE_DB_*`. Подробности fixture и сценариев: [quota upgrade](refactoring-quota.md).

GitHub CI на исправлениях не запускался: пользователь отдельно выбрал оставить изменения локально и отказался от commit/push/draft PR. Новый job подготовлен в рабочем дереве; локальные проверки не считаются результатом GitHub CI. Существующий workflow запускается по push основных веток, pull request к ним или вручную через `workflow_dispatch` после публикации.

## Выполненные проверки

| Проверка | Результат |
| --- | --- |
| `php artisan test --compact`, PHP 8.3.30 | 1704 passed, 3 skipped, 8174 assertions, 100.01 s |
| Partial-export target suite | 97 passed, 537 assertions; 19 новых публичных случаев |
| Recovery target suite | 50 passed, 268 assertions, 6.74 s |
| `php vendor/bin/phpunit --filter=MigrationUpgradeTest`, isolated MySQL 8.4.8/InnoDB | 1 passed, 183 assertions, 3.332 s |
| `composer run lint:check` | Pint всего PHP-кода успешен |
| `npm run quality:check` | Prettier, ESLint, vue-tsc, i18n успешны |
| `npm run test:unit` | 32 файла, 313 passed |
| `npm run build` | Client и SSR успешны; прежнее предупреждение Inertia sourcemap сохраняется |
| CI YAML parse, independent review, `git diff --check` | Успешны |
| GitHub CI | Не запускался: публикация отклонена пользователем |

Три пропуска обычного suite относятся к двум существующим opt-in concurrency tests и новому opt-in migration-upgrade. Новый upgrade отдельно выполнен успешно с явной dedicated DB configuration; существующий concurrency test в этом дополнительном проходе повторно не запускался. После проверки временный MySQL instance остановлен и его проверенный datadir удалён. Рабочая БД и её credentials не использовались.

Логи локальных PHP/Pint/build проверок находятся в игнорируемом `storage/logs/followup-*.log`. Сгенерированные build изменения TypeScript routes/actions убраны из целевого diff. Commit, push, PR и deployment не выполнялись.

Обновление со старой схемы: приостановить scheduler dispatch, завершить in-flight HTTP, остановить workers; выложить код, выполнить `php artisan migrate --force`, обновить config cache, перезапустить workers/scheduler. Production database и реальные внешние API в этом проходе не используются.
