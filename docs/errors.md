# Error Handling Guide

Этот документ фиксирует, как использовать ошибки и переводы в проекте, чтобы:

- не показывать пользователю технические детали;
- не хардкодить публичные сообщения в PHP и сервисах;
- держать единый стандарт для API, валидации, доступа и доменной логики.

## Базовое правило

- `lang/*` — все сообщения, которые формируются на сервере:
  API-ошибки, validation, access, domain messages, exception messages.
- `resources/js/locales/*` — все сообщения, которые живут только во Vue:
  кнопки, подписи, пустые состояния, клиентские fallback-тексты.
- В PHP нельзя писать `__('Some English text...')` для новых пользовательских сообщений.
  Нужно использовать ключи вида `errors.api.*`, `errors.validation.*`, `errors.access.*`, `errors.domain.*`.

## Структура ключей

Файл: `lang/en/errors.php`, `lang/ru/errors.php`

Используем 4 основных секции:

- `errors.api.*`
  Публичные сообщения для JSON/API и контролируемых серверных ошибок.
- `errors.validation.*`
  Сообщения для `FormRequest`, `ValidationException` и полевой HTTP-валидации.
- `errors.access.*`
  Сообщения для ограничений доступа, тарифов и квот.
- `errors.domain.*`
  Сообщения для доменных инвариантов, DTO и внутренних бизнес-проверок, если их текст может дойти до пользователя или тестов.

## Когда использовать `errors.api.*`

Использовать, если сообщение:

- возвращается в JSON-ответе;
- пробрасывается через `PublicException`;
- используется в контроллере, middleware или responder'е как публичная ошибка;
- описывает недоступность интеграции, внешнего API, not found, invalid target и т.д.

Примеры:

- `errors.api.telegram.load_messages_failed`
- `errors.api.site_intel.invalid_target`
- `errors.api.youtube.request_failed`
- `errors.api.shifr.unsupported_cipher_configuration`

## Когда использовать `errors.validation.*`

Использовать, если сообщение:

- добавляется через `$validator->errors()->add(...)`;
- относится к `FormRequest`;
- привязано к конкретному полю формы;
- используется в `ValidationException::withMessages(...)`.

Примеры:

- `errors.validation.date_from_before_or_equal_date_to`
- `errors.validation.custom_period_requires_both_dates`
- `errors.validation.custom_analytics_range_max_days`
- `errors.validation.shifr_transform_only_for_rot`

## Когда использовать `errors.access.*`

Использовать для:

- отказа в доступе;
- тарифных ограничений;
- исчерпанной квоты;
- редиректов и JSON-ответов слоя feature access.

Примеры:

- `errors.access.feature_denied`
- `errors.access.feature_paid_only`
- `errors.access.feature_daily_limit_reached`

## Когда использовать `errors.domain.*`

Использовать, если:

- сообщение относится к инварианту DTO или value-like объекта;
- ошибка рождается в доменном слое;
- это не transport-level validation, а бизнес- или domain-level проверка;
- текст должен быть предсказуемым и единым в тестах и обработке.

Примеры:

- `errors.domain.telegram.messages_peer_required`
- `errors.domain.telegram.participants_filter_unsupported`
- `errors.domain.shifr.jwt_token_parts_invalid`

## Исключения

### `app/Exceptions/Public/*`

Используются для безопасных публичных ошибок API.

Примеры:

- `IntegrationMisconfiguredException`
- `ExternalServiceUnavailableException`
- `ExternalServiceRequestException`
- `PublicResourceNotFoundException`
- `PublicValidationException`

Такие исключения должны ссылаться на ключ перевода, а не держать текст в коде.

### `app/Exceptions/Domain/*`

Используются для доменных ошибок и инвариантов.

Сейчас основной тип:

- `DomainValidationException`

Если текст доменной ошибки важен для UX, тестов или единообразия, он тоже должен приходить из `errors.domain.*`.

### Named domain exceptions

Если у сценария устойчивый набор причин, лучше отдельный тип исключения.

Пример:

- `SubscriptionActivationException`

С подходом:

- `invalid()`
- `used()`
- `expired()`

## Как выбирать тип ошибки

1. Ошибка уходит пользователю через JSON/API?
Используй `errors.api.*` и при необходимости `PublicException`.

2. Ошибка относится к полю формы?
Используй `errors.validation.*` через `FormRequest` или `ValidationException`.

3. Ошибка относится к тарифам, квотам, разрешениям?
Используй `errors.access.*`.

4. Ошибка относится к DTO, domain invariant или внутренней бизнес-логике?
Используй `errors.domain.*` и `DomainValidationException` либо отдельное доменное исключение.

## Анти-паттерны

Нежелательно:

- `__('Some English sentence...')` вместо ключа перевода;
- хардкодить пользовательский текст прямо в service/action/controller;
- отдавать `$exception->getMessage()` пользователю напрямую;
- использовать `RuntimeException` или `InvalidArgumentException` как универсальный способ для новых пользовательских ошибок;
- смешивать frontend-переводы и backend-переводы в одном месте.

## Практический стандарт

Для новых изменений проверяй:

1. Это backend message или frontend-only message?
2. Если backend — есть ли правильный ключ в `lang/en/errors.php` и `lang/ru/errors.php`?
3. Если frontend-only — лежит ли текст в `resources/js/locales/*`?
4. Не утечёт ли техническая строка пользователю?
5. Есть ли тест на ошибочный сценарий, если поведение критично?
