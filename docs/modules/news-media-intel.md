# News / Media Intel

## Overview

Модуль получает новости из собственного SearXNG. Сервис ограничивает выдачу, удаляет повторные упоминания, строит timeline, извлекает частотные topics и рассчитывает словарный sentiment summary. SearXNG — единственный источник поиска в модуле.

## Architecture

`NewsMediaIntelController` получает `NewsMediaIntelLookupDTO` и вызывает `NewsMediaIntelServiceInterface`. `SearxngNewsFeedFetcher` реализует `NewsFeedFetcherInterface::fetchAll`, запрашивает JSON API SearXNG с `categories=news` и преобразует результаты в `NewsMentionDTO`. Анализ использует общие DTO для mentions/topics/timeline/sentiment.

Frontend: `resources/js/pages/NewsMediaIntel.vue` и module types. Parser lifecycle, history и exports не реализованы.

## Route

`GET /news-media-intel/lookup` находится в authenticated+verified group и имеет throttle `30/min`; текущий `config/access.php` не тарифицирует его.

## Local SearXNG

Нужен установленный и запущенный Docker Desktop с Linux containers и Docker Compose v2. На подготовленной машине Docker CLI и стандартные файлы Docker Desktop не найдены; установка Docker — оставшийся шаг перед первым запуском. Наличие Python не заменяет Docker engine. Скрипт не устанавливает Docker/WSL и не изменяет корневой Laravel `.env`.

Из корня проекта в PowerShell:

```powershell
./docker/searxng/Start.ps1 -ValidateOnly
./docker/searxng/Start.ps1
```

Первый вызов проверяет CLI, Compose и Linux engine, создаёт `docker/searxng/.env` с криптографически случайным 32-byte секретом и валидирует Compose без запуска контейнера. Повторный вызов сохраняет секрет. Второй запускает контейнер и ждёт HTTP healthcheck. Не копируйте пример `.env` вручную: оставленный пустой секрет будет отклонён. Секрет не выводится в консоль и исключён из Git.

Compose публикует только `127.0.0.1:8088:8080`; SearXNG доступен на этом компьютере по `http://127.0.0.1:8088/`. Используется официальный `ghcr.io/searxng/searxng` image и его текущий Granian entrypoint. Настройки доступны в `docker/searxng/settings.yml`, cache хранится в Docker named volume. Limiter и public-instance режим выключены для локальной установки, поэтому Valkey не требуется. См. [официальную инструкцию установки контейнера](https://docs.searxng.org/admin/installation-docker.html).

Настройки включают HTML и JSON, вкладку news и только `google news`, `bing news`, `duckduckgo news`, `brave.news`. Секрет передаётся через поддерживаемую переменную [`SEARXNG_SECRET`](https://docs.searxng.org/admin/settings/settings_server.html); `settings.yml` не содержит секретов. `SEARXNG_VERSION` в локальном `.env` позволяет закрепить проверенный официальный image tag; по умолчанию используется `latest`.

Проверка JSON API после запуска:

```powershell
$response = Invoke-RestMethod 'http://127.0.0.1:8088/search' -Method Post -Body @{
    q = 'OpenAI'
    categories = 'news'
    format = 'json'
    language = 'ru'
}
$response.results | Select-Object title, url, publishedDate
$response.unresponsive_engines
```

JSON должен быть включён в `search.formats`, иначе SearXNG отвечает 403. Параметры `q`, `categories`, `format`, `language`, `pageno`, `engines` и `time_range` описаны в [Search API](https://docs.searxng.org/dev/search_api.html). Проверка healthcheck подтверждает работу HTTP сервера; наличие результатов дополнительно зависит от доступности внешних поисковых систем.

Управление из каталога `docker/searxng`:

```powershell
docker compose ps
docker compose logs --tail 100 searxng
docker compose down
docker compose pull
./Start.ps1
```

`down` сохраняет cache volume и локальный секрет. Не используйте `docker compose config` без `--quiet` в публикуемых логах: развёрнутая конфигурация содержит секрет.

## Laravel configuration

Настройки находятся в `config/osint/news_media_intel.php`. Корневой `.env.example` содержит:

```dotenv
OSINT_NEWS_MEDIA_MAX_MENTIONS=120
OSINT_NEWS_MEDIA_SEARXNG_BASE_URL=http://127.0.0.1:8088
OSINT_NEWS_MEDIA_SEARXNG_LANGUAGE=ru
OSINT_NEWS_MEDIA_SEARXNG_ENGINES=
OSINT_NEWS_MEDIA_SEARXNG_MAX_PAGES=3
OSINT_NEWS_MEDIA_SEARXNG_TIMEOUT=10
OSINT_NEWS_MEDIA_SEARXNG_REQUEST_BUDGET=20
OSINT_NEWS_MEDIA_SEARXNG_SAFESEARCH=1
OSINT_NEWS_MEDIA_SEARXNG_TIME_RANGE=
```

Пустой `ENGINES` использует все настроенные news engines. Чтобы ограничить их, задайте имена через запятую, например `"google news,bing news"`. `TIME_RANGE` можно ограничить значением `day`, `month` или `year`; поддержка зависит от engine. `MAX_PAGES`, общий временной бюджет и максимум упоминаний ограничивают один lookup. Если Laravel использует cache конфигурации, после изменения своих настроек очистите его обычным `php artisan config:clear`.

Адрес `127.0.0.1` подходит Laravel, работающему в OSPanel на этом же Windows host. При переносе Laravel в контейнер или на другой host укажите доступный ему адрес собственного SearXNG и настройте доступ отдельно; текущий Compose намеренно публикуется только на loopback.

## Beta limitations

Coverage зависит от внешних индексов, выбранного языка и доступности news engines. CAPTCHA, rate limits и региональная недоступность могут давать частичную или пустую выдачу. SearXNG не даёт архивной полноты, а верхний предел страниц и временной бюджет дополнительно ограничивают результаты. Deduplication использует URL/fingerprint rules. Sentiment и topics — простые словарные/частотные эвристики; это не ML/NLP conclusion.
