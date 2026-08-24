# Telegram

## Overview и capabilities

Telegram module работает через MadelineProto user session. Search получает сообщения и комментарии, media endpoint отдаёт вложение; Analytics строит summary/HTML report; Parser поэтапно собирает сообщения и комментарии с history, stop и JSON/Excel exports.

## Architecture

`TelegramSearch/Analytics/ParserController` → module Application Service → Actions/collector → `TelegramGatewayInterface`/`TelegramService` → `MadelineProtoManager`. DTO и Presenters нормализуют data; `TelegramParserExportBuilder` создаёт summary/messages/comments/reactions sheets.

Frontend: `resources/js/pages/Telegram.vue`, `pages/telegram/tabs/*`, composables `useTelegramSearch`, `useTelegramAnalytics`, `useTelegramParser`.

## Route groups

- `/telegram/search/messages`, `/search/comments`, `/media/{chatUsername}/{messageId}`;
- `/telegram/analytics/summary|report`;
- `/telegram/parser/start|status|history|stop|download-json|download-excel`.

Все routes находятся в authenticated+verified group. Page, Analytics и Parser защищены Feature Access; Search routes throttled, но не тарифицируются текущим access map.

## Configuration

Обязательны `TELEGRAM_API_ID`, `TELEGRAM_API_HASH` и хотя бы одна valid session. `MADELINEPROTO_SESSION_PATH`, `MADELINEPROTO_LOG_PATH` и `OSINT_TELEGRAM_*` управляют storage/limits/analytics. Session pool автоматически round-robin распределяет requests по `session*.madeline`. См. [Telegram sessions](../operations/telegram-session.md).

## Beta limitations

Работа зависит от Telegram account authorization, доступности channel/post, flood limits и changes MadelineProto/Telegram. Parser jobs Telegram serialized process-wide данным application lock; это ограничивает concurrency. Session files и collected data чувствительны и должны оставаться в private storage.
