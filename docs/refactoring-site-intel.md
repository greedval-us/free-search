# Ограничение HTTP-тел Site Intel

## Подтверждённая проблема

`SiteHealthHttpInspector` и `SeoAuditHttpFetcher` выполняли обычный buffered GET. Нижний уровень Guzzle 8 использовал неограниченный `php://temp` sink; HTTP-конфигурация и `SiteIntelHttpRequestOptions` ограничения фактического размера не содержали. Даже Site Health, который использует только headers/status, сначала загружал тело. Отдельный WHOIS-предел не защищал HTTP.

Воспроизводящий `SiteIntelHttpBodyLimitTest` до исправления дал **2 failed / 2 passed**: оба HTTP-клиента принимали девять байт при настроенном пределе восемь и ложном `Content-Length: 1`; семь и восемь байт сохранялись полностью.

## Реализация и совместимость

- `SiteIntelHttpClient` объединяет настройку/получение HTTP-ответов обоих клиентов. Безопасное разрешение target и ручной redirect loop остаются в клиентах, поэтому каждый новый target снова проходит guard.
- `SiteIntelBoundedTransport` устанавливает sink непосредственно под Laravel stub layer. Реальный transport пишет распакованные chunks через `SiteIntelBoundedResponseStream`; превышение отклоняет текущий chunk и прерывает transport. `Content-Length` не используется как доказательство размера.
- Sink хранит не более настроенного предела в `php://memory`; именованные временные файлы не создаются. Sink закрывается при синхронной ошибке и при rejected promise; исходный response stream закрывается после ограниченного чтения, включая ошибку. Ограниченный response передаётся дальше целиком.
- Дополнительное чтение response stream ограничено остатком бюджета плюс один байт. Оно защищает alternate handlers/HTTP doubles без преобразования всего stream в строку.
- `OSINT_SITE_HEALTH_HTTP_MAX_RESPONSE_BYTES=2097152` задаёт предел одного декодированного ответа; положительное числовое значение ограничено снизу одним байтом. HTTP timeout, стандартный connect timeout 10 секунд, предел redirects, TLS-параметр и `CURLOPT_RESOLVE` сохранены.
- При превышении существующий public exception renderer возвращает `{ok:false,message,code:"site_intel_response_too_large"}` с HTTP 503 и EN/RU сообщением. Ошибка распространяется из root/robots/sitemap/crawl/redirect fetch, поэтому усечённый документ не участвует в score и не становится успешным report snapshot. Остальные форматы и прежняя обработка connection failures не изменены.

Бюджет каждого redirect response независим. Общего накопительного HTTP-байтового бюджета всего SEO-сценария нет; число запросов по-прежнему ограничено существующими crawl и redirect limits. Это не доказательство допустимой production-нагрузки.

## Проверки

- Boundary: ниже, ровно и выше лимита; отсутствующий/ложный `Content-Length`, chunked headers.
- Controlled transport читает **12 из 1024 байт**, останавливается на первой недопустимой записи, оставляет 1012 байт невычитанными и закрывает sink. Это проверка прерывания, дополнительная к обычному `Http::fake`.
- Infinite alternate stream через HTTP fake читается только до **limit + 1** (девять байт) и закрывается.
- Локальный односоединительный fixture проверяет настоящие Guzzle `CurlHandler` и `StreamHandler`: gzip с восемью декодированными байтами допустим, gzip с 65536 декодированными байтами отклоняется при лимите восемь. Все соединения ограничены loopback; production guard не менялся.
- Перенаправления: новый безопасный IP закрепляется отдельно, внутренний target блокируется до второго запроса; TLS/timeout/redirect options сохраняются. HTTP endpoint выдаёт точную публичную ошибку EN/RU без partial data.

Выполнено на OSPanel PHP 8.3.30: `php artisan test --compact tests/Feature/SiteIntelHttpBodyLimitTest.php tests/Feature/SiteIntelHttpBodyLimitHttpTest.php tests/Unit/SiteIntelBoundedTransportTest.php tests/Feature/SiteHealthNetworkSafetyTest.php tests/Unit/SiteIntelHttpRequestOptionsTest.php` — 28 passed / 108 assertions; отдельно `php vendor/phpunit/phpunit/phpunit tests/Unit/SiteIntelBoundedTransportTest.php` — 12 passed / 38 assertions с обоими native handlers. Scoped Pint и `git diff --check` прошли. Полный финальный прогон фиксируется в общем плане рефакторинга.

## Обновление

Миграций для этого этапа нет. Добавить параметр при необходимости, пересобрать Laravel configuration cache и перезапустить долгоживущие queue workers стандартным способом, чтобы они получили новый код/конфигурацию. Существующие queued payloads, маршруты и форматы сохранённых документов не меняются. Большой документ теперь явно завершает текущий анализ ошибкой вместо неограниченной загрузки.
