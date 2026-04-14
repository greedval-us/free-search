<script setup lang="ts">
import { BarChart3, LoaderCircle, Search } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useUsernameSearch } from '../composables/useUsernameSearch';
import UsernameEntityGraph from './analytics/components/UsernameEntityGraph.vue';

const { t } = useI18n();

const { form, loading, error, items, analytics, localDiff, canSearch, search } = useUsernameSearch(t);

const checkedCount = computed(() => items.value.length);

const graphStats = computed(() => ({
    nodes: analytics.value?.graph.nodes.length ?? 0,
    edges: analytics.value?.graph.edges.length ?? 0,
    domains: analytics.value?.graph.nodes.filter((node) => node.type === 'domain').length ?? 0,
    categories: analytics.value?.graph.nodes.filter((node) => node.type === 'category').length ?? 0,
}));

const reasonLabel = (reason: string) => {
    const key = `username.analytics.similarityReason.${reason}`;
    const translated = t(key);

    if (translated === key) {
        return reason;
    }

    return translated;
};
</script>

<template>
    <section class="sticky top-0 z-10 shrink-0 rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
        <div class="flex items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <BarChart3 class="h-4 w-4 text-cyan-400" />
                    <span>{{ t('username.analytics.tabTitle') }}</span>
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ t('username.analytics.tabDescription') }}
                </p>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-end gap-3">
            <label class="block min-w-0 flex-1">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('username.search.label') }}</span>
                <input
                    v-model="form.username"
                    type="text"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    :placeholder="t('username.search.placeholder')"
                    @keydown.enter.prevent="search"
                />
            </label>

            <button
                :disabled="loading || !canSearch"
                class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                @click="search"
            >
                <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                <Search v-else class="h-4 w-4" />
                <span>{{ loading ? t('username.search.searching') : t('username.search.find') }}</span>
            </button>
        </div>

        <p v-if="error" class="mt-3 text-sm text-destructive">{{ error }}</p>
    </section>

    <section class="flex min-h-0 flex-1 flex-col rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
        <div class="telegram-scroll min-h-0 flex-1 overflow-y-auto pr-1">
            <div v-if="!analytics" class="rounded-md border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                {{ t('username.analytics.empty') }}
            </div>

            <template v-else>
                <div class="mb-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('username.results.checked') }}</p>
                        <p class="mt-1 text-xl font-semibold">{{ checkedCount }}</p>
                    </div>
                    <div class="rounded-lg border border-cyan-500/30 bg-cyan-500/5 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('username.analytics.confidenceAverage') }}</p>
                        <p class="mt-1 text-xl font-semibold text-cyan-300">{{ analytics.confidence.average }}%</p>
                    </div>
                    <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/5 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('username.analytics.confidenceHigh') }}</p>
                        <p class="mt-1 text-xl font-semibold text-emerald-300">{{ analytics.confidence.high }}</p>
                    </div>
                    <div class="rounded-lg border border-rose-500/30 bg-rose-500/5 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('username.analytics.confidenceLow') }}</p>
                        <p class="mt-1 text-xl font-semibold text-rose-300">{{ analytics.confidence.low }}</p>
                    </div>
                </div>

                <div v-if="localDiff && localDiff.hasPrevious" class="mb-3 rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                    <p class="font-semibold">{{ t('username.analytics.localDiffTitle') }}: {{ localDiff.changedCount }}</p>
                    <p class="mt-1 text-muted-foreground">
                        {{ t('username.analytics.newlyFound') }}: {{ localDiff.newlyFound.length }},
                        {{ t('username.analytics.becameNotFound') }}: {{ localDiff.becameNotFound.length }},
                        {{ t('username.analytics.becameUnknown') }}: {{ localDiff.becameUnknown.length }}
                    </p>
                </div>

                <div class="grid gap-2 xl:grid-cols-2">
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <div class="flex items-center gap-2">
                        <p class="text-xs font-semibold">{{ t('username.analytics.similarityTitle') }}</p>
                        <span class="group relative inline-flex">
                            <span
                                class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                :aria-label="t('telegram.analytics.help.label')"
                            >
                                ?
                            </span>
                            <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                {{ t('username.analytics.help.similarity') }}
                            </span>
                        </span>
                    </div>
                    <div class="mt-2 space-y-1 text-xs text-muted-foreground">
                            <p v-if="analytics.similarity.variants.length === 0">{{ t('username.analytics.noSimilarity') }}</p>
                            <p v-for="variant in analytics.similarity.variants" :key="variant.username">
                                {{ variant.username }}
                                <span class="opacity-80"> - {{ reasonLabel(variant.reason) }}</span>
                                <span v-if="variant.foundInPrioritySources !== null && variant.checkedPrioritySources !== null">
                                    ({{ variant.foundInPrioritySources }}/{{ variant.checkedPrioritySources }})
                                </span>
                            </p>
                        </div>
                </div>
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <div class="flex items-center gap-2">
                        <p class="text-xs font-semibold">{{ t('username.analytics.graphTitle') }}</p>
                        <span class="group relative inline-flex">
                            <span
                                class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                :aria-label="t('telegram.analytics.help.label')"
                            >
                                ?
                            </span>
                            <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                {{ t('username.analytics.help.graph') }}
                            </span>
                        </span>
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">
                        {{ t('username.analytics.graphNodes') }}: {{ graphStats.nodes }},
                        {{ t('username.analytics.graphEdges') }}: {{ graphStats.edges }},
                        {{ t('username.analytics.graphDomains') }}: {{ graphStats.domains }},
                        {{ t('username.analytics.graphCategories') }}: {{ graphStats.categories }}
                    </p>
                </div>
            </div>

            <div class="mt-2 overflow-x-auto">
                <div class="mb-2 flex items-center gap-2">
                    <p class="text-xs font-semibold">{{ t('username.analytics.graphCanvasTitle') }}</p>
                    <span class="group relative inline-flex">
                        <span
                            class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                            :aria-label="t('telegram.analytics.help.label')"
                        >
                            ?
                        </span>
                        <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                            {{ t('username.analytics.help.graphCanvas') }}
                        </span>
                    </span>
                </div>
                <UsernameEntityGraph
                    :nodes="analytics.graph.nodes"
                    :edges="analytics.graph.edges"
                />
            </div>
            </template>
        </div>
    </section>
</template>
