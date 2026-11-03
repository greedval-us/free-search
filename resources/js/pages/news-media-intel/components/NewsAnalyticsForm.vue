<script setup lang="ts">
import { LoaderCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import SettingsDisclosure from '@/components/ui/SettingsDisclosure.vue';
import { useI18n } from '@/composables/useI18n';
import { competitorNames, marketingFormError } from '../form';
import type {
    MarketingForm,
    NewsFilters as Filters,
    NewsOptions,
} from '../types';
import NewsFilters from './NewsFilters.vue';

const props = defineProps<{
    modelValue: MarketingForm;
    filters: Filters;
    options: NewsOptions | null;
    loading: boolean;
    optionsLoading: boolean;
    error: string;
    validationError: string;
    canRun: boolean;
}>();
const emit = defineEmits<{
    'update:modelValue': [value: MarketingForm];
    'update:filters': [value: Filters];
    submit: [];
    cancel: [];
}>();
const { t } = useI18n();
const contextOpen = ref(false);
const contextSummary = computed(() => {
    const competitors = competitorNames(props.modelValue.competitors);

    return (
        [
            props.modelValue.brand.trim(),
            props.modelValue.domain.trim(),
            competitors.length
                ? t('newsMediaIntel.analytics.competitorSummary', {
                      count: competitors.length,
                  })
                : '',
        ]
            .filter(Boolean)
            .join(' · ') || t('newsMediaIntel.analytics.contextEmpty')
    );
});
watch(
    () => props.validationError,
    (error) => {
        if (error && marketingFormError(props.modelValue) !== 'queryRequired') {
            contextOpen.value = true;
        }
    },
    { immediate: true }
);
const update = (field: keyof MarketingForm, value: string) =>
    emit('update:modelValue', { ...props.modelValue, [field]: value });
</script>

<template>
    <form class="mt-3 min-w-0 space-y-3" @submit.prevent="emit('submit')">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
            <label class="intel-field flex-1">
                <span class="intel-label">{{
                    t('newsMediaIntel.form.query')
                }}</span>
                <input
                    :value="modelValue.query"
                    maxlength="180"
                    required
                    class="intel-input"
                    :placeholder="
                        t('newsMediaIntel.analytics.queryPlaceholder')
                    "
                    @input="
                        update(
                            'query',
                            ($event.target as HTMLInputElement).value
                        )
                    "
                />
            </label>
            <div class="flex shrink-0 gap-2">
                <button
                    type="submit"
                    class="intel-button-primary grow sm:grow-0"
                    :disabled="loading || !canRun"
                >
                    <LoaderCircle
                        v-if="loading"
                        class="size-4 animate-spin"
                        aria-hidden="true"
                    />
                    {{
                        t(
                            loading
                                ? 'newsMediaIntel.analytics.running'
                                : 'newsMediaIntel.analytics.run'
                        )
                    }}
                </button>
                <button
                    v-if="loading"
                    type="button"
                    class="intel-button-secondary"
                    @click="emit('cancel')"
                >
                    {{ t('newsMediaIntel.form.cancel') }}
                </button>
            </div>
        </div>
        <SettingsDisclosure
            v-model:open="contextOpen"
            :title="t('newsMediaIntel.analytics.contextTitle')"
            :summary="contextSummary"
        >
            <div class="space-y-3">
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="intel-field">
                        <span class="intel-label">{{
                            t('newsMediaIntel.analytics.brand')
                        }}</span>
                        <input
                            :value="modelValue.brand"
                            maxlength="80"
                            class="intel-input"
                            :placeholder="
                                t('newsMediaIntel.analytics.brandPlaceholder')
                            "
                            @input="
                                update(
                                    'brand',
                                    ($event.target as HTMLInputElement).value
                                )
                            "
                        />
                    </label>
                    <label class="intel-field">
                        <span class="intel-label">{{
                            t('newsMediaIntel.analytics.domain')
                        }}</span>
                        <input
                            :value="modelValue.domain"
                            maxlength="253"
                            class="intel-input"
                            placeholder="example.com"
                            @input="
                                update(
                                    'domain',
                                    ($event.target as HTMLInputElement).value
                                )
                            "
                        />
                        <span
                            class="mt-1 block text-xs leading-5 text-muted-foreground"
                            >{{
                                t('newsMediaIntel.analytics.domainHelp')
                            }}</span
                        >
                    </label>
                </div>
                <label class="intel-field">
                    <span class="intel-label">{{
                        t('newsMediaIntel.analytics.competitors')
                    }}</span>
                    <textarea
                        :value="modelValue.competitors"
                        rows="2"
                        class="intel-input resize-y"
                        :placeholder="
                            t('newsMediaIntel.analytics.competitorsPlaceholder')
                        "
                        @input="
                            update(
                                'competitors',
                                ($event.target as HTMLTextAreaElement).value
                            )
                        "
                    />
                    <span
                        class="mt-1 block text-xs leading-5 text-muted-foreground"
                        >{{
                            t('newsMediaIntel.analytics.competitorsHelp')
                        }}</span
                    >
                </label>
            </div>
        </SettingsDisclosure>
        <NewsFilters
            :model-value="filters"
            :options="options"
            analytics
            :disabled="loading || optionsLoading"
            @update:model-value="emit('update:filters', $event)"
        />
        <p
            v-if="validationError && modelValue.query.trim()"
            role="alert"
            class="text-xs leading-5 text-destructive"
        >
            {{ validationError }}
        </p>
        <p
            v-if="error"
            role="alert"
            class="rounded-lg border border-destructive/20 bg-destructive/5 px-3 py-2 text-sm leading-6 text-destructive"
        >
            {{ error }}
        </p>
        <p class="text-xs leading-5 text-muted-foreground">
            {{ t('newsMediaIntel.analytics.sampleHelp') }}
        </p>
    </form>
</template>
