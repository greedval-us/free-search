# Intel UI Guide

Короткий гайд по переиспользуемым компонентам UI для модулей разведки/аналитики.

## Компоненты

### `IntelSearchPanel`
Контейнер верхней поисковой зоны (заголовок, форма, фильтры).

Когда использовать:
- В каждом модуле, где есть зона ввода параметров поиска/анализа.

### `IntelResultPanel`
Контейнер основной зоны результата (пустое состояние, метрики, секции).

Когда использовать:
- Сразу после `IntelSearchPanel` в layout модуля.

### `IntelSearchForm`
Базовая типовая форма:
- один input
- одна основная кнопка
- loading/error
- опциональный слот `actions` для доп.кнопок

Когда использовать:
- Простые формы (`query`, `domain`, `target`, `username`).

### `IntelSearchFormComplex`
Обертка для сложных форм:
- несколько контролов (input/select/checkbox)
- несколько кнопок
- общий вывод `error`

Когда использовать:
- Если форма не укладывается в `IntelSearchForm`.

### `IntelAdvancedFilters`
Блок расширенных фильтров:
- проп `open` (основной)
- проп `contentClass` для сетки (`md:grid-cols-3` и т.д.)
- проп `withDivider` для включения/выключения верхнего разделителя

Когда использовать:
- Для collapsible advanced-опций в поиске.

### `EmptyState`
Стандартное пустое состояние результата.

Когда использовать:
- Если данных ещё нет или список пуст.

## Стили и токены

Используем утилиты из `resources/css/app.css`:
- `intel-panel`
- `intel-panel-strong`
- `intel-surface`
- `intel-section`
- `intel-title`
- `intel-value`
- `intel-caption`
- `intel-empty`

Правило:
- Не копировать длинные цепочки tailwind-классов для повторяющихся карточек/панелей.
- Сначала проверять, есть ли подходящий `intel-*` токен или UI-компонент.

## Рекомендованный шаблон страницы

```vue
<template>
  <div class="flex h-full min-h-0 flex-1 flex-col gap-4 overflow-hidden rounded-xl p-4">
    <IntelSearchPanel>
      <!-- PageHeader + форма -->
    </IntelSearchPanel>

    <IntelResultPanel>
      <EmptyState v-if="!result" :text="t('...empty')" />
      <div v-else class="telegram-scroll min-h-0 flex-1 overflow-y-auto space-y-3 pr-1">
        <!-- метрики и секции -->
      </div>
    </IntelResultPanel>
  </div>
</template>
```

## Практические правила

1. Для нового модуля сначала пробуем `IntelSearchForm`.
2. Если есть 2+ поля/селекты с отдельной логикой, берем `IntelSearchFormComplex`.
3. Для дополнительных кнопок рядом с primary submit используем слот `actions` в `IntelSearchForm`.
4. Для расширенных фильтров используем `IntelAdvancedFilters`.
5. Error-текст всегда выводим в форме (`IntelSearchForm`/`IntelSearchFormComplex`), а не дублируем вручную.
