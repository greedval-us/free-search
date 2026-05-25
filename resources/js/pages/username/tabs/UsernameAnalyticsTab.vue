<script setup lang="ts">
import { BarChart3, Download, FileText } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import IntelSearchForm from '@/components/ui/IntelSearchForm.vue';
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue';
import { useI18n } from '@/composables/useI18n';
import {
    getRepeatQueryParams,
    isRepeatAutorunEnabled,
    readRepeatQueryParam,
} from '@/composables/useRepeatQuery';
import { useUsernameSearch } from '../composables/useUsernameSearch';
import UsernameEntityGraph from './analytics/components/UsernameEntityGraph.vue';

const { t } = useI18n();

const {
    form,
    loading,
    error,
    items,
    analytics,
    localDiff,
    canSearch,
    canUseReportActions,
    search,
    openReport,
    downloadReport,
} = useUsernameSearch(t);

const checkedCount = computed(() => items.value.length);

const graphStats = computed(() => ({
    nodes: analytics.value?.graph.nodes.length ?? 0,
    edges: analytics.value?.graph.edges.length ?? 0,
    domains:
        analytics.value?.graph.nodes.filter((node) => node.type === 'domain')
            .length ?? 0,
    categories:
        analytics.value?.graph.nodes.filter((node) => node.type === 'category')
            .length ?? 0,
}));

const reasonLabel = (reason: string) => {
    const key = `username.analytics.similarityReason.${reason}`;
    const translated = t(key);

    if (translated === key) {
        return reason;
    }

    return translated;
};

onMounted(() => {
    const params = getRepeatQueryParams();

    if (!params) {
        return;
    }

    const tab = readRepeatQueryParam(params, ['tab']);

    if (tab !== 'analytics') {
        return;
    }

    const username = readRepeatQueryParam(params, ['username']);

    if (username !== '') {
        form.username = username;
    }

    if (isRepeatAutorunEnabled(params) && canSearch.value) {
        void search();
    }
});
</script>

<template>
    <IntelSearchPanel>
        <div class="flex items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <BarChart3 class="h-4 w-4 text-cyan-400" />
                    <span>{{ t('username.analytics.tabTitle') }}</span>
                    <HelpTooltip
                        :label="t('username.help.label')"
                        :text="t('username.analytics.help.overview')"
                    />
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ t('username.analytics.tabDescription') }}
                </p>
            </div>
        </div>

        <IntelSearchForm
            v-model="form.username"
            :label="t('username.search.label')"
            :placeholder="t('username.search.placeholder')"
            :button-text="t('username.search.find')"
            :loading-text="t('username.search.searching')"
            :loading="loading"
            :disabled="!canSearch"
            :error="error"
            @submit="search"
        >
            <template #actions>
                <button
                    type="button"
                    :disabled="!canUseReportActions"
                    class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                    @click="openReport"
                >
                    <FileText class="h-4 w-4" />
                    {{ t('username.analytics.report') }}
                </button>

                <button
                    type="button"
                    :disabled="!canUseReportActions"
                    class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                    @click="downloadReport"
                >
                    <Download class="h-4 w-4" />
                    {{ t('username.analytics.downloadReport') }}
                </button>
            </template>
        </IntelSearchForm>
    </IntelSearchPanel>

    <IntelResultPanel>
        <div class="intel-scroll min-h-0 flex-1 overflow-y-auto pr-1">
            <div v-if="!analytics" class="intel-empty">
                {{ t('username.analytics.empty') }}
            </div>

            <template v-else>
                <div class="mb-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    <div
                        class="rounded-lg border border-border/70 bg-background/60 p-3"
                    >
                        <p class="text-xs text-muted-foreground">
                            {{ t('username.results.checked') }}
                        </p>
                        <p class="mt-1 text-xl font-semibold">
                            {{ checkedCount }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg border border-cyan-500/30 bg-cyan-500/5 p-3"
                    >
                        <p class="text-xs text-muted-foreground">
                            {{ t('username.analytics.confidenceAverage') }}
                        </p>
                        <p class="mt-1 text-xl font-semibold text-cyan-300">
                            {{ analytics.confidence.average }}%
                        </p>
                    </div>
                    <div
                        class="rounded-lg border border-emerald-500/30 bg-emerald-500/5 p-3"
                    >
                        <p class="text-xs text-muted-foreground">
                            {{ t('username.analytics.confidenceHigh') }}
                        </p>
                        <p class="mt-1 text-xl font-semibold text-emerald-300">
                            {{ analytics.confidence.high }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg border border-rose-500/30 bg-rose-500/5 p-3"
                    >
                        <p class="text-xs text-muted-foreground">
                            {{ t('username.analytics.confidenceLow') }}
                        </p>
                        <p class="mt-1 text-xl font-semibold text-rose-300">
                            {{ analytics.confidence.low }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="localDiff && localDiff.hasPrevious"
                    class="mb-3 rounded-lg border border-border/70 bg-background/60 p-3 text-xs"
                >
                    <p class="font-semibold">
                        {{ t('username.analytics.localDiffTitle') }}:
                        {{ localDiff.changedCount }}
                    </p>
                    <p class="mt-1 text-muted-foreground">
                        {{ t('username.analytics.newlyFound') }}:
                        {{ localDiff.newlyFound.length }},
                        {{ t('username.analytics.becameNotFound') }}:
                        {{ localDiff.becameNotFound.length }},
                        {{ t('username.analytics.becameUnknown') }}:
                        {{ localDiff.becameUnknown.length }}
                    </p>
                </div>

                <div class="grid gap-2 xl:grid-cols-2">
                    <div
                        class="rounded-lg border border-border/70 bg-background/60 p-3"
                    >
                        <div class="flex items-center gap-2">
                            <p class="text-xs font-semibold">
                                {{ t('username.analytics.similarityTitle') }}
                            </p>
                            <HelpTooltip
                                :label="t('username.help.label')"
                                :text="t('username.analytics.help.similarity')"
                            />
                        </div>
                        <div
                            class="mt-2 space-y-1 text-xs text-muted-foreground"
                        >
                            <p
                                v-if="
                                    analytics.similarity.variants.length === 0
                                "
                            >
                                {{ t('username.analytics.noSimilarity') }}
                            </p>
                            <p
                                v-for="variant in analytics.similarity.variants"
                                :key="variant.username"
                            >
                                {{ variant.username }}
                                <span class="opacity-80">
                                    - {{ reasonLabel(variant.reason) }}</span
                                >
                                <span
                                    v-if="
                                        variant.foundInPrioritySources !==
                                            null &&
                                        variant.checkedPrioritySources !== null
                                    "
                                >
                                    ({{ variant.foundInPrioritySources }}/{{
                                        variant.checkedPrioritySources
                                    }})
                                </span>
                            </p>
                        </div>
                    </div>
                    <div
                        class="rounded-lg border border-border/70 bg-background/60 p-3"
                    >
                        <div class="flex items-center gap-2">
                            <p class="text-xs font-semibold">
                                {{ t('username.analytics.graphTitle') }}
                            </p>
                            <HelpTooltip
                                :label="t('username.help.label')"
                                :text="t('username.analytics.help.graph')"
                            />
                        </div>
                        <p class="mt-2 text-xs text-muted-foreground">
                            {{ t('username.analytics.graphNodes') }}:
                            {{ graphStats.nodes }},
                            {{ t('username.analytics.graphEdges') }}:
                            {{ graphStats.edges }},
                            {{ t('username.analytics.graphDomains') }}:
                            {{ graphStats.domains }},
                            {{ t('username.analytics.graphCategories') }}:
                            {{ graphStats.categories }}
                        </p>
                    </div>
                </div>

                <div class="mt-2 overflow-x-auto">
                    <div class="mb-2 flex items-center gap-2">
                        <p class="text-xs font-semibold">
                            {{ t('username.analytics.graphCanvasTitle') }}
                        </p>
                        <HelpTooltip
                            :label="t('username.help.label')"
                            :text="t('username.analytics.help.graphCanvas')"
                        />
                    </div>
                    <UsernameEntityGraph
                        :nodes="analytics.graph.nodes"
                        :edges="analytics.graph.edges"
                    />
                </div>
            </template>
        </div>
    </IntelResultPanel>
</template>
