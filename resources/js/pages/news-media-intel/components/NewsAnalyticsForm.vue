<script setup lang="ts">
import { LoaderCircle } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';
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
const update = (field: keyof MarketingForm, value: string) =>
    emit('update:modelValue', { ...props.modelValue, [field]: value });
</script>
<template>
    <form class="mt-4 space-y-3" @submit.prevent="emit('submit')">
        <label class="block"
            ><span class="intel-label">{{
                t('newsMediaIntel.form.query')
            }}</span
            ><input
                :value="modelValue.query"
                maxlength="180"
                required
                class="intel-input"
                :placeholder="t('newsMediaIntel.analytics.queryPlaceholder')"
                @input="
                    update('query', ($event.target as HTMLInputElement).value)
                "
        /></label>
        <label class="block"
            ><span class="intel-label">{{
                t('newsMediaIntel.analytics.brand')
            }}</span
            ><input
                :value="modelValue.brand"
                maxlength="80"
                class="intel-input"
                :placeholder="t('newsMediaIntel.analytics.brandPlaceholder')"
                @input="
                    update('brand', ($event.target as HTMLInputElement).value)
                "
        /></label>
        <label class="block"
            ><span class="intel-label">{{
                t('newsMediaIntel.analytics.competitors')
            }}</span
            ><textarea
                :value="modelValue.competitors"
                rows="3"
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
            /><span
                class="mt-1 block text-xs leading-5 text-muted-foreground"
                >{{ t('newsMediaIntel.analytics.competitorsHelp') }}</span
            ></label
        >
        <label class="block"
            ><span class="intel-label">{{
                t('newsMediaIntel.analytics.domain')
            }}</span
            ><input
                :value="modelValue.domain"
                maxlength="253"
                class="intel-input"
                placeholder="example.com"
                @input="
                    update('domain', ($event.target as HTMLInputElement).value)
                "
            /><span
                class="mt-1 block text-xs leading-5 text-muted-foreground"
                >{{ t('newsMediaIntel.analytics.domainHelp') }}</span
            ></label
        >
        <NewsFilters
            :model-value="filters"
            :options="options"
            analytics
            :disabled="loading || optionsLoading"
            @update:model-value="emit('update:filters', $event)"
        />
        <p
            v-if="validationError && modelValue.query.trim()"
            class="text-xs leading-5 text-destructive"
        >
            {{ validationError }}
        </p>
        <p
            v-if="error"
            role="alert"
            class="rounded-xl border border-destructive/20 bg-destructive/5 px-3 py-2 text-sm leading-6 text-destructive"
        >
            {{ error }}
        </p>
        <div class="flex flex-wrap gap-2">
            <button
                type="submit"
                class="intel-button-primary grow justify-center"
                :disabled="loading || !canRun"
            >
                <LoaderCircle
                    v-if="loading"
                    class="h-4 w-4 animate-spin"
                    aria-hidden="true"
                />{{
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
        <p class="text-xs leading-5 text-muted-foreground">
            {{ t('newsMediaIntel.analytics.sampleHelp') }}
        </p>
    </form>
</template>
