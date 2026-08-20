# Access и subscriptions

## Модель доступа

Plans `free`, `plus`, `pro` и resource limits определены в `config/access.php`. Free имеет нулевой доступ к защищённым Analytics/Parser/SEO capabilities; Plus и Pro имеют дневные quotas. `admin` и `moderator` account types обходят quotas.

`EnsureFeatureAccess` разрешает page/tab inspection либо consume для counting route. Counting usage атомарно учитывается в `feature_usage_daily`. При нулевом plan limit JSON получает 403, при исчерпанной quota — 429; HTML request перенаправляется в billing.

Не каждый module endpoint платный: Search в social modules в основном только throttled, а Analytics/Parser защищены. Источник истины — `protected_routes` и `page_resources`, не наличие middleware на родительской page.

## Subscriptions

`User.currentPlan()` выбирает активную subscription по time window; без неё используется Free. Billing page показывает account summary и принимает one-time activation token. Token нормализуется, действует 30 дней по model behavior, одноразово связывается с user/subscription.

`BILLING_CHECKOUT_ENABLED=false` скрывает прямой checkout/upgrade UI. Сам факт флага не подтверждает реализованный payment-provider checkout; token activation — доказанный текущий путь.

Scheduler ежедневно отправляет notifications о приближении конца subscription. MoonShine управляет subscriptions и activation tokens.
