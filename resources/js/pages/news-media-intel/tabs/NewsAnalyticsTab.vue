<script setup lang="ts">
import { BarChart3 } from 'lucide-vue-next';
import EmptyState from '@/components/ui/EmptyState.vue';
import IntelModuleLayout from '@/components/ui/IntelModuleLayout.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import { useI18n } from '@/composables/useI18n';
import NewsAnalyticsForm from '../components/NewsAnalyticsForm.vue';
import NewsAnalyticsResults from '../components/NewsAnalyticsResults.vue';
import { useNewsIntel } from '../useNewsIntel';
const { t } = useI18n();
const {
    form,
    filters,
    options,
    loading,
    optionsLoading,
    error,
    optionsError,
    analyticsResult,
    validationError,
    canRun,
    loadOptions,
    run,
    cancel,
    reportUrl,
} = useNewsIntel('analytics');
const selectQuery = (value: string) => {
    form.value.query = value;
    void run();
};
</script>
<template>
    <IntelModuleLayout>
        <IntelSearchPanel>
            <PageHeader
                :icon="BarChart3"
                :title="t('newsMediaIntel.analytics.title')"
                :description="t('newsMediaIntel.analytics.description')"
            />
            <p
                v-if="optionsLoading"
                class="mt-3 text-xs text-muted-foreground"
                role="status"
            >
                {{ t('newsMediaIntel.filters.loading') }}
            </p>
            <div
                v-if="optionsError"
                class="mt-3 space-y-2 text-sm text-destructive"
                role="alert"
            >
                <p>{{ optionsError }}</p>
                <button
                    type="button"
                    class="intel-button-secondary"
                    @click="loadOptions"
                >
                    {{ t('newsMediaIntel.form.retry') }}
                </button>
            </div>
            <NewsAnalyticsForm
                v-model="form"
                v-model:filters="filters"
                :options="options"
                :loading="loading"
                :options-loading="optionsLoading"
                :error="error"
                :validation-error="validationError"
                :can-run="canRun"
                @submit="run"
                @cancel="cancel"
            />
        </IntelSearchPanel>
        <IntelResultPanel>
            <EmptyState
                v-if="!analyticsResult"
                :text="t('newsMediaIntel.analytics.empty')"
            />
            <div
                v-else
                class="intel-scroll min-h-0 flex-1 overflow-y-auto pr-1"
                :aria-busy="loading"
            >
                <NewsAnalyticsResults
                    :result="analyticsResult"
                    :view-url="reportUrl('html')"
                    :html-url="reportUrl('html', true)"
                    :json-url="reportUrl('json', true)"
                    @query="selectQuery"
                />
            </div>
        </IntelResultPanel>
    </IntelModuleLayout>
</template>
