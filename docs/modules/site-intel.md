# Site Intel

## Capabilities

- **Site Health:** HTTP reachability/redirects, DNS, SSL и score.
- **Domain Lite:** DNS + WHOIS parsing и risk signals.
- **Analytics:** агрегирует site health/domain signals.
- **SEO Audit:** content/technical/quality/international/crawl analysis, link graph, score и recommendations.
- Analytics и SEO Audit имеют HTML report/download responses; Parser Runs и JSON/Excel export здесь не используются.

## Architecture

`SiteIntelController` вызывает четыре Application Service interfaces. Implementations координируют узкие analyzers/calculators. Infrastructure clients реализуют DNS, SSL, HTTP, WHOIS и crawl fetch. `SiteIntelTargetGuard` и resolved target abstractions проверяют network target/redirect path. Result DTO формирует JSON/report data.

Frontend: четыре tabs/composables в `resources/js/pages/site-intel`; link graph использует Cytoscape.

## Routes и access

`site-health` и `domain-lite` throttled; page/analytics/seo-audit/reports additionally Feature Access protected в соответствии с route policy.

## Configuration

`OSINT_SITE_HEALTH_HTTP_*` задают User-Agent, Accept, timeout, redirects и TLS verification. `OSINT_SITE_INTEL_WHOIS_*` задают IANA server, socket timeouts и response bounds. Production должен сохранять TLS verification включённым.

## Beta limitations и security

WHOIS formats нестабильны; DNS/SSL/HTTP отражают момент проверки. SEO Audit — bounded crawler, не замена полнофункциональному search-engine crawler. Target guard реализован, но production дополнительно требует outbound firewall/egress policy, trusted DNS/resolver configuration, rate limits и abuse monitoring.
