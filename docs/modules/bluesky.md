# Bluesky

## Overview и capabilities

Bluesky module поддерживает post/actor Search, likes, reposts, thread, author feed, followers/follows, Analytics по account/hashtag и Parser profile/feed/network/interactions с exports.

## Architecture

Search/Analytics/Parser Application Services используют Actions и `BlueskyGatewayInterface`; `BlueskyApiClient` работает с AT Protocol через configured PDS. `BlueskyActorResolver` разрешает handle/DID. Presenters нормализуют actors/posts/thread/interactions. Parser stages: profile → feed → followers → follows → interactions → completed.

Frontend: page, tabs, cards и composables в `resources/js/pages/bluesky`.

## Routes и access

Основные groups: `/bluesky/search`, `/posts/{likes,reposts,thread}`, `/actors/{feed,followers,follows}`, `/analytics/*`, `/parser/*`. Page, Analytics и Parser защищены Feature Access; relation/search endpoints имеют throttle и работают внутри authenticated+verified group.

## Configuration

Требуются `BLUESKY_IDENTIFIER` и app password; `BLUESKY_PDS_URL` default — `https://bsky.social`. Timeout/retry — `config/services.php`; Search limits/type/sort — `config/osint/bluesky.php` (`OSINT_BLUESKY_SEARCH_*`).

## Beta limitations

Полнота зависит от PDS/AppView API, authentication, cursor pagination и visibility/deletion records. Analytics — прикладная агрегация доступных posts, не официальный platform analytics. Parser interaction stage может быть дорогим по API requests.
