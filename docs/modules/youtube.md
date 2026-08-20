# YouTube

## Overview и capabilities

Module использует YouTube Data API v3: video Search, comments preview/direct comments, channel/video Analytics, background Parser comments/replies, history, stop и JSON/Excel exports.

## Architecture

Controllers вызывают `YouTubeSearchApplicationService`, `YouTubeAnalyticsApplicationService` и `YouTubeParserApplicationService`. Actions работают через `YouTubeGatewayInterface`; implementation использует `YouTubeDataApiClient`. Parser collector проходит comments и replies, snapshot builder фиксирует результат, export builder создаёт summary/comments/replies sheets.

Frontend: `resources/js/pages/YouTube.vue`, tabs и composables в `pages/youtube`.

## Routes и access

- `/youtube/search/videos`, `/search/comments-preview`;
- `/youtube/analytics/summary|report`;
- `/youtube/parser/comments|start|status|history|stop|download-*`.

Search video route throttled; comments/Analytics/Parser paid capabilities защищены Feature Access согласно `config/access.php`.

## Configuration

`YOUTUBE_DATA_API_KEY` обязателен. Endpoint/retry/timeout находятся в `config/services.php`; module limits/periods — `config/osint/youtube.php` (`OSINT_YOUTUBE_*`). Defaults: search max 10, parser comments max 100, analytics periods 1/3/7 days.

## Beta limitations

API quota, disabled comments, deleted/private content и pagination влияют на полноту. Analytics строится только из данных, доступных Data API; это не YouTube Studio analytics. Excel/JSON доступен только для completed/stopped runs.
