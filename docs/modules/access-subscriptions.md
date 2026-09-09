# Access и subscriptions

## Модель доступа

Plans `free`, `plus`, `pro` и resource limits определены в `config/access.php`. По умолчанию Free получает по одному запросу в день на защищённые ресурсы; значения можно переопределить через `ACCESS_*_DAILY_LIMIT`. `admin` и `moderator` account types обходят quotas.

`EnsureFeatureAccess` разрешает page/tab inspection либо consume для counting route. Counting usage атомарно учитывается в `feature_usage_daily`. При нулевом plan limit JSON получает 403, при исчерпанной quota — 429; HTML request перенаправляется в billing.

Не каждый module endpoint платный: Search в social modules в основном только throttled, а Analytics/Parser защищены. Источник истины — `protected_routes` и `page_resources`, не наличие middleware на родительской page.

Параметры запроса, включая `snapshotRole`, не освобождают новый расчёт от квоты. Telegram рассчитывает текущий и предыдущий периоды на сервере в рамках одного оплаченного квотой запроса; клиент не запускает отдельный бесплатный сбор.

HTML-отчёты читают только `ReportSnapshotStore`: ключ включает пользователя, модуль и нормализованные параметры анализа. Повторный просмотр или скачивание не расходует квоту и не обращается к внешнему сервису. При отсутствии результата возвращается HTTP 410 с локализованной ошибкой. TTL задаётся `REPORT_SNAPSHOT_TTL_SECONDS` (по умолчанию 3600); очистка кэша также делает отчёты недоступными. Это отдельное хранилище, не меняющее семидневный срок хранения выгрузок парсеров.

## Subscriptions

`User.currentPlan()` выбирает активную subscription по time window; без неё используется Free. Billing page показывает account summary и принимает one-time activation token. Token нормализуется, действует 30 дней по model behavior, одноразово связывается с user/subscription.

`BILLING_CHECKOUT_ENABLED=false` скрывает прямой checkout/upgrade UI. Сам факт флага не подтверждает реализованный payment-provider checkout; token activation — доказанный текущий путь.

Scheduler ежедневно отправляет notifications о приближении конца subscription. MoonShine управляет subscriptions и activation tokens.

`SubscriptionManager` выполняет смену плана и отправку уведомления внутри транзакции с блокировкой пользователя. Его используют MoonShine и `SubscriptionActivationService`. Повторное сохранение того же плана в админке не продлевает подписку и не дублирует уведомления; активация нового ключа создаёт новую подписку.
