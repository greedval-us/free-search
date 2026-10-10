# Документация Free Search

Документация описывает состояние репозитория на 20 августа 2026 года. Код и конфигурация имеют приоритет над этими страницами.

> **Project Status: Beta.** Контракты могут меняться, модули имеют разную зрелость, а production-ready эксплуатация требует самостоятельной проверки.

## Начало работы

- [Установка и первый запуск](getting-started.md)
- [Configuration reference](configuration.md)
- [Development guide](development.md)
- [Testing](testing.md)
- [Deployment](deployment.md)
- [Security](security.md)
- [Error handling](errors.md)

## Архитектура

- [Обзор](architecture/overview.md)
- [Backend](architecture/backend.md)
- [Frontend](architecture/frontend.md)
- [Границы модулей](architecture/modules.md)
- [Parser Runs](architecture/parser-runs.md)
- [Отчёты по расписанию](architecture/scheduled-reports.md)
- [Data flow](architecture/data-flow.md)

## Модули и подсистемы

- [Telegram](modules/telegram.md)
- [Telegram Bot / Telegraph](modules/telegram-bot.md)
- [Отчёты аналитики Telegram по расписанию](modules/telegram-analytics-reports.md)
- [YouTube](modules/youtube.md)
- [Отчёты аналитики YouTube по расписанию](modules/youtube-analytics-reports.md)
- [Bluesky](modules/bluesky.md)
- [Mastodon](modules/mastodon.md)
- [Отчёты Bluesky и Mastodon по расписанию](modules/social-analytics-reports.md)
- [Site Intel](modules/site-intel.md)
- [Аналитика сайтов и SEO-аудит по расписанию](modules/site-intel-reports.md)
- [News / Media Intel](modules/news-media-intel.md)
- [Отчёты новостей и медиа по расписанию](modules/news-media-reports.md)
- [Shifr](modules/shifr.md)
- [Dashboard, Wiki и Export](modules/platform-services.md)
- [Access и subscriptions](modules/access-subscriptions.md)

## Эксплуатация и проект

- [Queue и Scheduler](operations/queues-and-scheduler.md)
- [Telegram sessions](operations/telegram-session.md)
- [Maintenance](operations/maintenance.md)
- [Статус и известные ограничения](project/status.md)
- [Contributing](project/contributing.md)

Документация не является публичной REST API specification: маршруты ориентированы на Inertia UI и его JSON/file запросы.
