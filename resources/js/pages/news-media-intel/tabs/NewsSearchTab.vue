<script setup lang="ts">
import { Newspaper } from 'lucide-vue-next';
import EmptyState from '@/components/ui/EmptyState.vue';
import IntelModuleLayout from '@/components/ui/IntelModuleLayout.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import IntelSearchForm from '@/components/ui/IntelSearchForm.vue';
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import { useI18n } from '@/composables/useI18n';
import NewsFilters from '../components/NewsFilters.vue';
import NewsSearchResults from '../components/NewsSearchResults.vue';
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
    searchResult,
    canRun,
    loadOptions,
    run,
    cancel,
} = useNewsIntel('search');
const selectQuery = (value: string) => {
    form.value.query = value;
    void run();
};
</script>
<template>
    <IntelModuleLayout>
        <IntelSearchPanel>
            <PageHeader
                :icon="Newspaper"
                :title="t('newsMediaIntel.title')"
                :description="t('newsMediaIntel.description')"
                :help-label="t('newsMediaIntel.help.label')"
                :help-text="t('newsMediaIntel.help.overview')"
            />
            <IntelSearchForm
                v-model="form.query"
                :label="t('newsMediaIntel.form.query')"
                :placeholder="t('newsMediaIntel.form.placeholder')"
                :button-text="t('newsMediaIntel.form.search')"
                :loading-text="t('newsMediaIntel.form.searching')"
                :loading="loading"
                :disabled="!canRun"
                :error="error"
                @submit="run"
            >
                <template #actions
                    ><button
                        v-if="loading"
                        type="button"
                        class="intel-button-secondary"
                        @click="cancel"
                    >
                        {{ t('newsMediaIntel.form.cancel') }}
                    </button></template
                >
            </IntelSearchForm>
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
            <NewsFilters
                v-model="filters"
                :options="options"
                :disabled="loading || optionsLoading"
            />
        </IntelSearchPanel>
        <IntelResultPanel>
            <EmptyState
                v-if="!searchResult"
                :text="t('newsMediaIntel.empty')"
            />
            <div
                v-else
                class="intel-scroll min-h-0 flex-1 overflow-y-auto pr-1"
                :aria-busy="loading"
            >
                <NewsSearchResults
                    :result="searchResult"
                    @query="selectQuery"
                />
            </div>
        </IntelResultPanel>
    </IntelModuleLayout>
</template>
