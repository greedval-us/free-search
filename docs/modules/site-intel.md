# Site Intel

## Capabilities

- **Site Health:** HTTP reachability/redirects, DNS, SSL и score.
- **Domain Lite:** DNS + WHOIS parsing и risk signals.
- **Analytics:** агрегирует site health/domain signals.
- **SEO Audit:** content/technical/quality/international/crawl analysis, link graph, score и recommendations.
- Analytics и SEO Audit имеют HTML report/download responses. Вкладка **Отчёты** запускает оба существующих вида по расписанию каждые 1 / 3 / 7 дней или месяц, сохраняет HTML/JSON и доставляет HTML в Telegram-бота по согласию. Parser Runs и Excel export здесь не используются.

Настройка расписаний, параметры SEO и отдельный worker описаны в [Отчётах сайтов по расписанию](site-intel-reports.md). Проверки сохраняют состояние сайта на фактический момент выполнения, а не статистику посещений за прошедшие дни.

## Architecture

`SiteIntelController` вызывает четыре Application Service interfaces. Implementations координируют узкие analyzers/calculators. Infrastructure clients реализуют DNS, SSL, HTTP, WHOIS и crawl fetch. `SiteIntelTargetGuard` и resolved target abstractions проверяют network target/redirect path. Result DTO формирует JSON/report data.

Frontend: пять tabs/composables в `resources/js/pages/site-intel`; link graph использует Cytoscape. `SiteIntelReportsController` и `Application/Reports` управляют отдельными долговечными снимками, используя те же аналитические сервисы и HTML-шаблоны.

## Routes и access

`site-health` и `domain-lite` throttled; page/analytics/seo-audit/reports дополнительно защищены Feature Access. Для вкладки отчётов достаточно доступа к `site-intel.analytics` или `site-intel.seo-audit`; операции и готовые документы проверяют конкретный вид. Новое формирование списывает его дневной лимит отдельно по каждому сайту, просмотр сохранённого результата лимит не расходует.

## Configuration

`OSINT_SITE_HEALTH_HTTP_*` задают User-Agent, Accept, timeout, redirects и TLS verification. `OSINT_SITE_HEALTH_HTTP_MAX_RESPONSE_BYTES` (по умолчанию 2097152 байта, 2 MiB) ограничивает каждое HTTP-тело после распаковки, включая redirect responses, HTML, robots.txt, sitemap и страницы crawl. Лимит проверяется при записи transport в ограниченный memory stream, независимо от `Content-Length`; чтение прекращается до полной загрузки слишком большого ответа. Пустое/нулевое/отрицательное числовое значение не отключает защиту: минимум один байт.

Превышение возвращает стандартную публичную ошибку HTTP 503 с кодом `site_intel_response_too_large` и переводом EN/RU. Анализ прерывается без выдачи усечённых данных или сохранения нового успешного отчёта. Этот предел относится к одному ответу; существующие ограничения redirect и crawl задают число запросов, отдельного общего байтового бюджета сценария пока нет. Connect timeout (10 секунд), общий timeout, проверки каждого redirect, закрепление проверенного IP и выбранный режим TLS сохранены.

`OSINT_SITE_INTEL_WHOIS_*` задают IANA server, socket timeouts и отдельные response bounds. Production должен сохранять TLS verification включённым. Детали регрессий и изменения поведения описаны в [рефакторинге Site Intel](../refactoring-site-intel.md).

`SITE_INTEL_REPORTS_*` задают отдельное соединение очереди, её имя, часовой пояс и предел времени формирования. Штатные `site-intel-reports-database` / `site-intel-reports-redis` используют `retry_after=960` при timeout 900 и lease 1200 секунд; нужен соответствующий worker и ежеминутный Laravel scheduler.

## Beta limitations и security

WHOIS formats нестабильны; DNS/SSL/HTTP отражают момент проверки. SEO Audit — bounded crawler, не замена полнофункциональному search-engine crawler. Target guard реализован, но production дополнительно требует outbound firewall/egress policy, trusted DNS/resolver configuration, rate limits и abuse monitoring.
