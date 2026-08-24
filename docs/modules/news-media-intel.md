# News / Media Intel

## Overview

Синхронный lookup агрегирует configured providers: NewsAPI, Google News RSS и Bing RSS. Service ограничивает выдачу, дедуплицирует mentions, строит timeline, извлекает частотные topics и рассчитывает словарный sentiment summary.

## Architecture

`NewsMediaIntelController` получает `NewsMediaIntelLookupDTO` и вызывает `NewsMediaIntelServiceInterface`. `CompositeNewsFeedFetcher` использует registry provider order; каждый provider реализует `NewsFeedProviderInterface`. Domain DTO фиксируют mentions/topics/timeline/sentiment result.

Frontend: `resources/js/pages/NewsMediaIntel.vue` и module types. Parser lifecycle, history и exports не реализованы.

## Route

`GET /news-media-intel/lookup` находится в authenticated+verified group и имеет throttle `30/min`; текущий `config/access.php` не тарифицирует его.

## Configuration

`OSINT_NEWSAPI_KEY` включает NewsAPI provider. `OSINT_NEWS_MEDIA_FETCHER_PROVIDER_ORDER`, per-provider/max limits, RSS locale/templates/timeouts и topic/dedup settings находятся в `config/osint/news_media_intel.php`. RSS providers не требуют NewsAPI key.

## Beta limitations

Coverage зависит от индекса/локали providers, RSS stability и NewsAPI plan. Deduplication основана на URL/fingerprint rules. Sentiment и topics — простые словарные/частотные эвристики, не ML/NLP conclusion. Не гарантируется архивная полнота.
