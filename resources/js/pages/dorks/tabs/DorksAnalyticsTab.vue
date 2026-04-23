<script setup lang="ts">
import { BarChart3, Download, FileText, LoaderCircle, Search } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';
import { useI18n } from '@/composables/useI18n';
import UsernameEntityGraph from '@/pages/username/tabs/analytics/components/UsernameEntityGraph.vue';
import { useDorksSearch } from '../composables/useDorksSearch';

const { t } = useI18n();
const {
    form,
    loading,
    error,
    payload,
    goals,
    canSearch,
    canUseReportActions,
    loadGoals,
    search,
    openReport,
    downloadReport,
} = useDorksSearch(t);

onMounted(() => {
    loadGoals();
});

const maxGoalCount = computed(() => Math.max(1, ...(payload.value?.analytics.goalDistribution ?? []).map((row) => row.count)));
const maxSourceCount = computed(() => Math.max(1, ...(payload.value?.analytics.sourceDistribution ?? []).map((row) => row.count)));
</script>

<template>
    <section class="sticky top-0 z-10 shrink-0 rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
        <div class="space-y-1">
            <div class="flex items-center gap-2 text-sm font-semibold">
                <BarChart3 class="h-4 w-4 text-cyan-400" />
                <span>{{ t('dorks.analytics.title') }}</span>
                <span class="group relative inline-flex">
                    <span
                        class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                        :aria-label="t('dorks.help.label')"
                    >
                        ?
                    </span>
                    <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                        {{ t('dorks.analytics.help.overview') }}
                    </span>
                </span>
            </div>
            <p class="text-xs text-muted-foreground">
                {{ t('dorks.analytics.description') }}
            </p>
        </div>

        <div class="mt-3 grid gap-3 md:grid-cols-[1fr_220px_auto_auto_auto]">
            <label class="block min-w-0">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('dorks.search.target') }}</span>
                <input
                    v-model="form.target"
                    type="text"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    :placeholder="t('dorks.search.targetPlaceholder')"
                    @keydown.enter.prevent="search"
                />
            </label>

            <label class="block min-w-0">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('dorks.search.goal') }}</span>
                <select
                    v-model="form.goal"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option v-for="goal in goals" :key="goal.key" :value="goal.key">
                        {{ goal.label }}
                    </option>
                </select>
            </label>

            <button
                :disabled="loading || !canSearch"
                class="inline-flex h-10 cursor-pointer items-center gap-2 self-end rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                @click="search"
            >
                <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                <Search v-else class="h-4 w-4" />
                <span>{{ loading ? t('dorks.search.searching') : t('dorks.search.find') }}</span>
            </button>

            <button
                type="button"
                :disabled="!canUseReportActions"
                class="inline-flex h-10 cursor-pointer items-center gap-2 self-end rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                @click="openReport"
            >
                <FileText class="h-4 w-4" />
                {{ t('dorks.analytics.report') }}
            </button>

            <button
                type="button"
                :disabled="!canUseReportActions"
                class="inline-flex h-10 cursor-pointer items-center gap-2 self-end rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                @click="downloadReport"
            >
                <Download class="h-4 w-4" />
                {{ t('dorks.analytics.downloadReport') }}
            </button>
        </div>

        <p v-if="error" class="mt-3 text-sm text-destructive">{{ error }}</p>
    </section>

    <section class="flex min-h-0 flex-1 flex-col rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
        <div class="telegram-scroll min-h-0 flex-1 overflow-y-auto pr-1">
            <div v-if="!payload" class="rounded-md border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                {{ t('dorks.analytics.empty') }}
            </div>

            <template v-else>
                <div class="mb-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('dorks.results.total') }}</p>
                        <p class="mt-1 text-xl font-semibold">{{ payload.summary.total }}</p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('dorks.results.uniqueDomains') }}</p>
                        <p class="mt-1 text-xl font-semibold">{{ payload.summary.uniqueDomains }}</p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('dorks.results.sources') }}</p>
                        <p class="mt-1 text-xl font-semibold">{{ payload.summary.sources }}</p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('dorks.results.goals') }}</p>
                        <p class="mt-1 text-xl font-semibold">{{ payload.summary.goals }}</p>
                    </div>
                </div>

                <div class="mb-3 grid gap-2 xl:grid-cols-2">
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="mb-2 text-xs font-semibold">{{ t('dorks.analytics.goalDistribution') }}</p>
                        <div class="space-y-2">
                            <div v-for="row in payload.analytics.goalDistribution" :key="`goal-${row.key}`">
                                <div class="mb-1 flex items-center justify-between text-xs">
                                    <span>{{ row.label }}</span>
                                    <span class="text-muted-foreground">{{ row.count }}</span>
                                </div>
                                <div class="h-2 rounded-full bg-muted">
                                    <div
                                        class="h-2 rounded-full bg-cyan-500"
                                        :style="{ width: `${Math.max(6, Math.round((row.count / maxGoalCount) * 100))}%` }"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="mb-2 text-xs font-semibold">{{ t('dorks.analytics.sourceDistribution') }}</p>
                        <div class="space-y-2">
                            <div v-for="row in payload.analytics.sourceDistribution" :key="`source-${row.key}`">
                                <div class="mb-1 flex items-center justify-between text-xs">
                                    <span>{{ row.label }}</span>
                                    <span class="text-muted-foreground">{{ row.count }}</span>
                                </div>
                                <div class="h-2 rounded-full bg-muted">
                                    <div
                                        class="h-2 rounded-full bg-emerald-500"
                                        :style="{ width: `${Math.max(6, Math.round((row.count / maxSourceCount) * 100))}%` }"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-2 rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="mb-2 text-xs font-semibold">{{ t('dorks.analytics.topDomains') }}</p>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="domain in payload.analytics.topDomains"
                            :key="`domain-${domain.key}`"
                            class="rounded-full border border-input px-2 py-1 text-xs"
                        >
                            {{ domain.label }} ({{ domain.count }})
                        </span>
                    </div>
                </div>

                <div class="mt-2">
                    <UsernameEntityGraph
                        :nodes="payload.analytics.graph.nodes"
                        :edges="payload.analytics.graph.edges"
                    />
                </div>
            </template>
        </div>
    </section>
</template>
