# Error Handling Guide

Этот документ описывает, как в проекте оформлять ошибки, пользовательские сообщения и переводы так, чтобы:

- не утекали технические детали наружу;
- backend и frontend пользовались предсказуемыми ключами и соглашениями;
- новые модули не приносили хаотичный набор текстов и exception-практик.

## Базовое правило

Пользовательские сообщения не хардкодятся в service/action/controller-коде.

Используем:

- `lang/*` для серверных сообщений;
- `resources/js/locales/*` для frontend-only текста;
- именованные исключения и translation keys вместо случайных строк.

Нежелательно писать что-то вроде:

```php
__('Some English sentence...')
```

Для новых сообщений используем ключи.

## Где хранить тексты

### Backend

Файлы:

- `lang/en/errors.php`
- `lang/ru/errors.php`

Здесь лежат:

- API errors;
- validation messages, если они серверные;
- access/quota messages;
- domain-level user-facing errors.

### Frontend

Файлы:

- `resources/js/locales/en/*.json`
- `resources/js/locales/ru/*.json`

Здесь лежат:

- кнопки, подписи, пустые состояния;
- клиентские fallback-тексты;
- UI-описания, не приходящие с сервера.

## Основные namespace-группы ключей

### `errors.api.*`

Используем, если сообщение:

- возвращается в JSON/API;
- описывает проблему внешней интеграции;
- используется публичным exception/responder-механизмом;
- относится к not found, invalid target, unavailable service и similar transport-level errors.

Примеры:

- `errors.api.telegram.load_messages_failed`
- `errors.api.telegram.not_configured`
- `errors.api.site_intel.invalid_target`
- `errors.api.youtube.request_failed`

### `errors.validation.*`

Используем, если сообщение:

- относится к `FormRequest`;
- добавляется в validator errors;
- связано с конкретным полем ввода;
- рождается через `ValidationException`.

Примеры:

- `errors.validation.date_from_before_or_equal_date_to`
- `errors.validation.custom_period_requires_both_dates`
- `errors.validation.shifr_transform_only_for_rot`

### `errors.access.*`

Используем для:

- отказа в доступе;
- ограничений по тарифу;
- исчерпания квоты;
- сообщений из feature access слоя.

Примеры:

- `errors.access.feature_denied`
- `errors.access.feature_paid_only`
- `errors.access.feature_daily_limit_reached`

### `errors.domain.*`

Используем, если сообщение:

- относится к domain invariant;
- связано с DTO/value object/business rule;
- важно для единообразия UX и тестов;
- не является transport-level validation.

Примеры:

- `errors.domain.telegram.messages_peer_required`
- `errors.domain.telegram.participants_filter_unsupported`
- `errors.domain.shifr.jwt_token_parts_invalid`

## Исключения

### Public exceptions

Папка:

- `app/Exceptions/Public/*`

Назначение:

- безопасные публичные ошибки, пригодные для API и контролируемого вывода;
- связь между exception-типом и translation key.

Типичные сценарии:

- integration misconfigured;
- external service unavailable;
- external request failed;
- public resource not found;
- public validation error.

Правило:

- exception должен ссылаться на translation key;
- нельзя пробрасывать наружу случайный `$exception->getMessage()` из внешней библиотеки.

### Domain exceptions

Папка:

- `app/Exceptions/Domain/*`

Назначение:

- инварианты домена;
- внутренние прикладные запреты;
- предсказуемое поведение при invalid domain state.

Если текст ошибки важен для UX, тестов или API consistency, он тоже должен идти через `errors.domain.*`.

## Как выбирать тип ошибки

1. Сообщение уйдет пользователю через JSON/API?
   Используй `errors.api.*` и при необходимости public exception.

2. Сообщение относится к полю формы?
   Используй `errors.validation.*`.

3. Ошибка про тариф, квоту или разрешение?
   Используй `errors.access.*`.

4. Ошибка про DTO, invariant или business rule?
   Используй `errors.domain.*`.

## Что считается хорошей практикой

- Один source of truth для текста ошибки.
- Translation key стабилен и пригоден для тестов.
- Внешняя библиотека логируется детально, а пользователю показывается безопасный текст.
- Domain/service слой не смешивает технический лог и пользовательский message.
- Frontend не дублирует backend-тексты, если сообщение уже приходит с сервера.

## Что считается плохой практикой

- хардкод пользовательской строки внутри action/service/controller;
- возврат raw exception message из внешнего API;
- смесь frontend и backend переводов в одном месте;
- повторение одной и той же ошибки разными фразами в разных модулях;
- generic `RuntimeException` для пользовательского сценария, где нужен именованный тип или хотя бы translation key.

## Рекомендации для новых модулей

При добавлении нового модуля:

1. Сразу договорись о namespace ошибок:
   например, `errors.api.my_module.*`, `errors.domain.my_module.*`.
2. Определи, какие ошибки transport-level, а какие domain-level.
3. Добавь ключи как минимум в `en` и `ru`.
4. Покрой критичные ошибочные ветки тестами.

## Чек-лист перед merge

1. Текст ошибки хранится в правильном месте?
2. Есть translation key, а не случайная строка?
3. Пользователю не показывается техническая внутрянка?
4. Ошибка имеет нужный уровень: `api`, `validation`, `access` или `domain`?
5. Ключ добавлен в обе локали?
6. Критичный error path покрыт тестом?
