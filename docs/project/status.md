# Project Status: Beta

Free Search активно развивается. Общий каркас, основные UI-маршруты и семь рабочих модулей существуют, но проект не следует считать завершённым или автоматически готовым к production.

## Что означает Beta

- UI props, внутренние JSON payloads, DTO и service interfaces могут меняться;
- модули имеют разную архитектуру и зрелость;
- Parser Runs уже используют queue, storage metadata, history и cleanup, но operational limits требуют проверки под реальной нагрузкой;
- поведение зависит от сторонних API, quota, federation instances, RSS и Telegram accounts;
- security defaults должны быть адаптированы к конкретной инфраструктуре.

## Подтверждённые ограничения и расхождения

1. `config/osint/mastodon.php` существует, но `config/osint.php` не подключает его, в отличие от других модулей. При `config('osint.mastodon')` это может приводить к использованию пустой конфигурации и требует проверки/исправления в кодовой задаче.
2. `.env.example` не перечисляет часть реально поддерживаемых optional settings из `config/services.php` и `config/osint/*` (например, Mastodon credentials и ряд retry/limit variables).
3. `.env.example` содержит `OSINT_FIO_*` и `OSINT_USERNAME_*`, однако активных route groups и модулей FIO/Username в текущем backend нет; они не входят в подтверждённую карту продукта.
4. В `resources/js/pages` есть каталоги `common-crawl` и `domain-infra-intel`, но нет соответствующих активных backend modules/routes; они рассматриваются как незавершённые frontend artifacts.
5. Прямой billing checkout управляется флагом, но интеграция платёжного провайдера не подтверждена; рабочий подтверждённый путь — одноразовые subscription activation tokens.
6. News sentiment/topic analysis словарный и эвристический; его нельзя трактовать как ML-классификацию или доказательную оценку тональности.
7. Site Intel выполняет активные DNS/WHOIS/HTTP/SSL запросы. В коде есть target guard и redirect protection, но лимиты, egress policy и защита внутренней сети должны проверяться для конкретного production окружения.
8. `composer.json` сохраняет starter-kit metadata (`name`, `description`); это packaging metadata, не описание Free Search.

## Перед production

Выполните [deployment checklist](../deployment.md), [security review](../security.md), нагрузочные проверки очереди и ручные smoke tests каждой включённой интеграции. Проверьте policies источников и правовые основания обработки/хранения данных.
