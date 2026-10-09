<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { availableNewsEngines } from '../form';
import type { NewsCategory, NewsFilters, NewsOptions } from '../types';

const props = defineProps<{
    modelValue: NewsFilters;
    options: NewsOptions | null;
    analytics?: boolean;
    disabled?: boolean;
}>();
const emit = defineEmits<{ 'update:modelValue': [value: NewsFilters] }>();
const { t, locale } = useI18n();
const engines = computed(() =>
    availableNewsEngines(
        props.options,
        props.analytics ? ['news', 'general'] : props.modelValue.categories
    )
);
const languages = computed(
    () => props.options?.languages ?? [props.modelValue.language]
);
const update = <K extends keyof NewsFilters>(key: K, value: NewsFilters[K]) =>
    emit('update:modelValue', { ...props.modelValue, [key]: value });
const selectCategory = (value: string) => {
    const categories: NewsCategory[] =
        value === 'both' ? ['news', 'general'] : [value as NewsCategory];
    const allowed = availableNewsEngines(props.options, categories);
    emit('update:modelValue', {
        ...props.modelValue,
        categories,
        engines: props.modelValue.engines.filter((engine) =>
            allowed.includes(engine)
        ),
    });
};
const selectEngine = (engine: string, checked: boolean) =>
    update(
        'engines',
        checked
            ? [...props.modelValue.engines, engine]
            : props.modelValue.engines.filter((value) => value !== engine)
    );
const languageLabel = (language: string) => {
    const key = `newsMediaIntel.filters.languages.${language}`;
    const translated = t(key);

    if (translated !== key) {
        return translated;
    }

    try {
        return (
            new Intl.DisplayNames([locale.value], { type: 'language' }).of(
                language
            ) ?? language
        );
    } catch {
        return language;
    }
};
</script>

<template>
    <fieldset class="mt-4 min-w-0 space-y-3" :disabled="disabled">
        <legend class="intel-label">
            {{ t('newsMediaIntel.filters.title') }}
        </legend>
        <div class="grid gap-3 sm:grid-cols-2">
            <label v-if="!analytics" class="block">
                <span class="intel-label">{{
                    t('newsMediaIntel.filters.category')
                }}</span>
                <select
                    class="intel-input"
                    :value="
                        modelValue.categories.length === 2
                            ? 'both'
                            : modelValue.categories[0]
                    "
                    @change="
                        selectCategory(
                            ($event.target as HTMLSelectElement).value
                        )
                    "
                >
                    <option value="news">
                        {{ t('newsMediaIntel.filters.news') }}
                    </option>
                    <option value="general">
                        {{ t('newsMediaIntel.filters.general') }}
                    </option>
                    <option value="both">
                        {{ t('newsMediaIntel.filters.both') }}
                    </option>
                </select>
            </label>
            <label class="block">
                <span class="intel-label">{{
                    t('newsMediaIntel.filters.language')
                }}</span>
                <select
                    class="intel-input"
                    :value="modelValue.language"
                    @change="
                        update(
                            'language',
                            ($event.target as HTMLSelectElement).value
                        )
                    "
                >
                    <option
                        v-for="language in languages"
                        :key="language"
                        :value="language"
                    >
                        {{ languageLabel(language) }}
                    </option>
                </select>
            </label>
            <label class="block">
                <span class="intel-label">{{
                    t('newsMediaIntel.filters.timeRange')
                }}</span>
                <select
                    class="intel-input"
                    :value="modelValue.timeRange"
                    @change="
                        update(
                            'timeRange',
                            ($event.target as HTMLSelectElement)
                                .value as NewsFilters['timeRange']
                        )
                    "
                >
                    <option value="">
                        {{ t('newsMediaIntel.filters.allTime') }}
                    </option>
                    <option value="day">
                        {{ t('newsMediaIntel.filters.day') }}
                    </option>
                    <option value="week">
                        {{ t('newsMediaIntel.filters.week') }}
                    </option>
                    <option value="month">
                        {{ t('newsMediaIntel.filters.month') }}
                    </option>
                    <option value="year">
                        {{ t('newsMediaIntel.filters.year') }}
                    </option>
                </select>
            </label>
            <label class="block">
                <span class="intel-label">{{
                    t('newsMediaIntel.filters.safeSearch')
                }}</span>
                <select
                    class="intel-input"
                    :value="modelValue.safeSearch"
                    @change="
                        update(
                            'safeSearch',
                            Number(($event.target as HTMLSelectElement).value)
                        )
                    "
                >
                    <option :value="0">
                        {{ t('newsMediaIntel.filters.safeOff') }}
                    </option>
                    <option :value="1">
                        {{ t('newsMediaIntel.filters.safeModerate') }}
                    </option>
                    <option :value="2">
                        {{ t('newsMediaIntel.filters.safeStrict') }}
                    </option>
                </select>
            </label>
            <label class="block">
                <span class="intel-label">{{
                    t('newsMediaIntel.filters.pages')
                }}</span>
                <select
                    class="intel-input"
                    :value="modelValue.maxPages"
                    @change="
                        update(
                            'maxPages',
                            Number(($event.target as HTMLSelectElement).value)
                        )
                    "
                >
                    <option
                        v-for="page in options?.maxPages ?? 3"
                        :key="page"
                        :value="page"
                    >
                        {{ page }}
                    </option>
                </select>
            </label>
        </div>
        <details
            v-if="engines.length"
            class="rounded-xl border border-border/70 p-3"
        >
            <summary class="cursor-pointer text-sm font-medium">
                {{ t('newsMediaIntel.filters.engines') }} ·
                {{
                    modelValue.engines.length ||
                    t('newsMediaIntel.filters.automatic')
                }}
            </summary>
            <p class="mt-2 text-xs leading-5 text-muted-foreground">
                {{ t('newsMediaIntel.filters.enginesHelp') }}
            </p>
            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                <label
                    v-for="engine in engines"
                    :key="engine"
                    class="flex min-w-0 items-center gap-2 text-xs"
                >
                    <input
                        type="checkbox"
                        :checked="modelValue.engines.includes(engine)"
                        class="accent-primary"
                        @change="
                            selectEngine(
                                engine,
                                ($event.target as HTMLInputElement).checked
                            )
                        "
                    />
                    <span class="break-words">{{ engine }}</span>
                </label>
            </div>
        </details>
        <p class="text-xs leading-5 text-muted-foreground">
            {{ t('newsMediaIntel.filters.filterHelp') }}
        </p>
    </fieldset>
</template>
