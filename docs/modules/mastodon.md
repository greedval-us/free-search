# Mastodon

## Overview и capabilities

Mastodon module выполняет resource Search, status context, account statuses/followers, hashtag timeline, account/hashtag Analytics и Parser statuses/comments с history и JSON/Excel exports.

## Architecture

Search/Analytics/Parser Application Services используют Actions, Presenters и `MastodonGatewayInterface`; `MastodonApiClient` обращается к configured instance. Parser разрешает account, собирает statuses и comments/context, затем формирует snapshot. Frontend находится в `resources/js/pages/mastodon`.

## Routes и access

`/mastodon/search`, `/statuses/{id}/context`, `/accounts/{id}/statuses|followers`, `/tags/{name}/statuses`, `/analytics/*`, `/parser/*`. Page, Analytics и Parser используют Feature Access; lookup routes throttled.

## Configuration

`MASTODON_API_BASE_URL` выбирает instance, `MASTODON_API_TOKEN` задаёт token; timeout/retry находятся в `config/services.php`. Search limits находятся в `config/osint/mastodon.php`.

> Beta configuration issue: root `config/osint.php` сейчас не подключает nested `mastodon.php`. До исправления проверьте результат `config('osint.mastodon')` в целевом окружении; не считайте declared defaults гарантированно активными.

## Beta limitations

Federation означает, что один instance не гарантирует глобальную полноту. Visibility, instance policies, rate limits и remote-object caching влияют на результаты. Token requirements различаются между instances.
