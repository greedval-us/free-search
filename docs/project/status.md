# Project Status: Beta

Free Search активно развивается. Общий каркас, основные UI-маршруты и семь рабочих модулей существуют, но проект не следует считать завершённым или автоматически готовым к production.

## Что означает Beta

- UI props, внутренние JSON payloads, DTO и service interfaces могут меняться;
- модули имеют разную архитектуру и зрелость;
- Parser Runs уже используют queue, storage metadata, history и cleanup, но operational limits требуют проверки под реальной нагрузкой;
- поведение зависит от сторонних API, quota, federation instances, SearXNG engines и Telegram accounts;
- security defaults должны быть адаптированы к конкретной инфраструктуре.

## Подтверждённые ограничения и расхождения

1. Mastodon подключён в `config/osint.php`; работа с реальным instance всё ещё требует корректных credentials и проверки доступности API.
2. `.env.example` не перечисляет часть реально поддерживаемых optional settings из `config/services.php` и `config/osint/*` (например, Mastodon credentials и ряд retry/limit variables).
3. `.env.example` содержит `OSINT_FIO_*` и `OSINT_USERNAME_*`, однако активных route groups и модулей FIO/Username в текущем backend нет; они не входят в подтверждённую карту продукта.
4. В `resources/js/pages` есть каталоги `common-crawl` и `domain-infra-intel`, но нет соответствующих активных backend modules/routes; они рассматриваются как незавершённые frontend artifacts.
5. Платёжного checkout нет: Billing сохраняет активацию одноразовым кодом и не показывает фиктивные ссылки оплаты, даже если старый `BILLING_CHECKOUT_ENABLED` включён. Интеграция провайдера остаётся отдельной задачей.
6. «Новости и медиа» использует локальный SearXNG; для запуска требуется Docker с Linux containers. Полнота поиска зависит от включённых news engines и лимитов. Sentiment/topic analysis словарный и эвристический; его нельзя трактовать как ML-классификацию или доказательную оценку тональности.
7. Site Intel выполняет активные DNS/WHOIS/HTTP/SSL запросы. В коде есть target guard и redirect protection, но лимиты, egress policy и защита внутренней сети должны проверяться для конкретного production окружения.
8. `composer.json` сохраняет starter-kit metadata (`name`, `description`); это packaging metadata, не описание Free Search.

## Проверенные изменения надёжности

Возврат квоты привязан к durable receipt исходного списания и допускается однократно. Scheduler вызывает `app:recover-parser-runs` без открытой страницы; recovery сохраняет checkpoint/retry budget и не запускает сбор напрямую. Site Intel ограничивает фактический декодированный HTTP body; превышение — публичная ошибка, не усечённый успешный результат. Общие schedule validation и owner-first pause/resume/delete используются всеми шестью модулями.

Parser runs имеют устойчивые технические budgets попыток, видимых запросов, сохранённых записей, длительности и checkpoint; JSON/XLSX export проверяет bytes/cells до отправки. Исчерпание сохраняет прежние данные как неполные. Точные границы — включая raw API response memory, скрытые Madeline retransmissions и terminal metadata — описаны в [итоговом отчёте](../refactoring-report.md).

Baseline, результаты регрессий и непроверенные условия перечислены в [плане рефакторинга](../refactoring-plan.md). Дополнительно выполнены многопроцессные проверки списаний/возвратов квоты и лимита YouTube-расписаний на отдельном временном MySQL 8.4.8. Эти локальные проверки не подтверждают production-нагрузку, PostgreSQL или работу независимых локальных дисков на нескольких хостах.

## Перед production

Выполните [deployment checklist](../deployment.md), [security review](../security.md), нагрузочные проверки очереди и ручные smoke tests каждой включённой интеграции. Проверьте policies источников и правовые основания обработки/хранения данных.
