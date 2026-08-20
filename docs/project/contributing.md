# Contributing

Проект находится в Beta, поэтому изменения должны сохранять доказуемость поведения и не расширять публичные обещания раньше реализации.

## Перед изменением

- прочитайте [Architecture overview](../architecture/overview.md) и страницу нужного модуля;
- проверьте существующий module style и contracts;
- не переносите бизнес-логику в Controller/Form Request;
- не используйте `env()` вне `config/*.php`;
- не коммитьте secrets, sessions, Parser Run payloads и локальный `.env`.

## Проверка

```bash
composer run lint:check
php artisan test
npm run quality:check
npm run test:unit
npm run build
```

Изменение behaviour должно сопровождаться тестом и обновлением соответствующей документации. Новая тарифицируемая capability требует route policy в `config/access.php`. Новая интеграция требует typed/validated configuration и явного failure mode.

## Pull request

Опишите scope, пользовательское поведение, конфигурационные изменения, migration/queue impact, ограничения и выполненные проверки. Для breaking changes отдельно укажите затронутые routes, DTO, Inertia props и stored payloads.
