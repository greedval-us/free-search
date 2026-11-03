# Telegram

## Overview и capabilities

Telegram module работает через MadelineProto user session. Search получает сообщения и комментарии, media endpoint отдаёт вложение; Analytics строит summary/HTML report; Parser поэтапно собирает сообщения и комментарии с history, stop и JSON/Excel exports.

## Architecture

`TelegramSearch/Analytics/ParserController` → module Application Service → Actions/collector → `TelegramGatewayInterface`/`TelegramService` → `MadelineProtoManager`. DTO и Presenters нормализуют data; `TelegramParserExportBuilder` создаёт summary/messages/comments/reactions sheets.

Frontend: `resources/js/pages/Telegram.vue`, `pages/telegram/tabs/*`, composables `useTelegramSearch`, `useTelegramAnalytics`, `useTelegramParser`.

Вкладка **Отчёты** сохраняет расписания аналитики по нескольким группам: каждый день, каждые 3/7 дней или ежемесячно. История HTML/JSON доступна на сайте; готовые отчёты могут автоматически отправляться привязанному боту. Настройка очереди, периоды и права описаны в [отчётах по расписанию](telegram-analytics-reports.md).

## Route groups

- `/telegram/search/messages`, `/search/comments`, `/media/{chatUsername}/{messageId}`;
- `/telegram/analytics/summary|report`;
- `/telegram/parser/start|status|history|stop|download-json|download-excel`.

Все routes находятся в authenticated+verified group. Page, Analytics и Parser защищены Feature Access; Search routes throttled, но не тарифицируются текущим access map.

## Configuration

Обязательны `TELEGRAM_API_ID`, `TELEGRAM_API_HASH` и хотя бы одна valid session. `MADELINEPROTO_SESSION_PATH`, `MADELINEPROTO_LOG_PATH` и `OSINT_TELEGRAM_*` управляют storage/limits/analytics. Session pool автоматически round-robin распределяет requests по `session*.madeline`. См. [Telegram sessions](../operations/telegram-session.md).

## Beta limitations

Аналитика обходит выдачу `messages.search` по ID сообщений до пустой успешной страницы или нижней границы периода. Флаг `inexact` и приблизительный `count` могут сохраняться на последней пустой странице; сами по себе они не означают ошибку запроса. Аналитика описывает полученную поисковую выборку, которая может отличаться от истории канала. Реальный сбой загрузки, зацикливание курсора и достижение лимита страниц по-прежнему прерывают расчёт. Такое завершение поиска соответствует [официальному Telegram Desktop](https://github.com/telegramdesktop/tdesktop/blob/dev/Telegram/SourceFiles/api/api_messages_search.cpp#L183-L210).

Работа зависит от Telegram account authorization, доступности channel/post, flood limits и changes MadelineProto/Telegram. Parser jobs Telegram serialized process-wide данным application lock; это ограничивает concurrency. Session files и collected data чувствительны и должны оставаться в private storage.
