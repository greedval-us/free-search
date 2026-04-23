<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { LoaderCircle, Search } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useFioLookup } from './fio/composables/useFioLookup';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'FIO',
                href: '/fio',
            },
        ],
    },
});

const { t, locale } = useI18n();
const { form, loading, error, result, canLookup, lookup } = useFioLookup(t, locale);

const pageTitle = computed(() => t('fio.headTitle'));

const formatDateTime = (value: string | null): string => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleString();
};

const regionLabel = (key: string): string => {
    const translationKey = `fio.region.${key}`;
    const translated = t(translationKey);

    return translated === translationKey ? key : translated;
};

const ageBucketLabel = (key: string): string => {
    const translationKey = `fio.age.${key}`;
    const translated = t(translationKey);

    return translated === translationKey ? key : translated;
};
</script>

<template>
    <Head :title="pageTitle" />

    <div class="flex h-full min-h-0 flex-1 flex-col gap-4 overflow-hidden rounded-xl p-4">
        <section class="sticky top-0 z-10 shrink-0 rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
            <div class="flex items-center justify-between gap-3">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-sm font-semibold">
                        <Search class="h-4 w-4 text-cyan-400" />
                        <span>{{ t('fio.lookup.title') }}</span>
                    </div>
                    <p class="text-xs text-muted-foreground">{{ t('fio.lookup.description') }}</p>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-end gap-3">
                <label class="block min-w-0 flex-1">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('fio.lookup.fullName') }}</span>
                    <input
                        v-model="form.fullName"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('fio.lookup.placeholder')"
                        @keydown.enter.prevent="lookup"
                    />
                </label>
                <label class="block min-w-0 flex-1">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('fio.lookup.qualifier') }}</span>
                    <input
                        v-model="form.qualifier"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('fio.lookup.qualifierPlaceholder')"
                        @keydown.enter.prevent="lookup"
                    />
                </label>

                <button
                    :disabled="loading || !canLookup"
                    class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                    @click="lookup"
                >
                    <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                    <span>{{ loading ? t('fio.lookup.searching') : t('fio.lookup.search') }}</span>
                </button>
            </div>

            <p v-if="error" class="mt-3 text-sm text-destructive">{{ error }}</p>
        </section>

        <section class="flex min-h-0 flex-1 flex-col rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
            <div v-if="!result" class="rounded-md border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                {{ t('fio.lookup.empty') }}
            </div>

            <div v-else class="telegram-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('fio.lookup.checkedAt') }}</p>
                        <p class="mt-1 text-sm font-semibold">{{ formatDateTime(result.checkedAt) }}</p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('fio.lookup.matches') }}</p>
                        <p class="mt-1 text-xl font-semibold">{{ result.summary.matches }}</p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('fio.lookup.domains') }}</p>
                        <p class="mt-1 text-xl font-semibold">{{ result.summary.domains }}</p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('fio.lookup.topRegion') }}</p>
                        <p class="mt-1 text-sm font-semibold">{{ regionLabel(result.summary.topRegion) }}</p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('fio.lookup.medianAge') }}</p>
                        <p class="mt-1 text-sm font-semibold">{{ result.summary.medianAge ?? '-' }}</p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('fio.lookup.averageConfidence') }}</p>
                        <p class="mt-1 text-sm font-semibold">{{ result.summary.averageConfidence }}%</p>
                    </div>
                </div>

                <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                    <p>
                        {{ t('fio.lookup.providers') }}:
                        <span class="text-muted-foreground">{{ (result.source.providers ?? []).join(', ') || '-' }}</span>
                    </p>
                    <p class="mt-1">
                        {{ t('fio.lookup.qualifier') }}:
                        <span class="text-muted-foreground">{{ result.target.qualifier || '-' }}</span>
                    </p>
                    <p class="mt-1">
                        {{ t('fio.lookup.qualifierTerms') }}:
                        <span class="text-muted-foreground">{{ (result.target.qualifierTerms ?? []).join(', ') || '-' }}</span>
                    </p>
                    <p class="mt-1">
                        {{ t('fio.lookup.qualifierMatches') }}:
                        <span class="text-muted-foreground">{{ result.summary.qualifierMatches }}</span>
                    </p>
                </div>

                <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                    <p class="mb-2 font-semibold">{{ t('fio.lookup.sourceReliability') }}</p>
                    <div v-if="(result.source.stats ?? []).length === 0" class="text-muted-foreground">-</div>
                    <div v-else class="space-y-2">
                        <div v-for="stat in result.source.stats" :key="stat.source">
                            <div class="mb-1 flex items-center justify-between">
                                <span>{{ stat.source }}</span>
                                <span class="text-muted-foreground">
                                    {{ t('fio.lookup.reliability') }}: {{ Math.round(stat.reliability * 100) }}%,
                                    {{ t('fio.lookup.matches') }}: {{ stat.matches }},
                                    Avg: {{ stat.averageConfidence }}%
                                </span>
                            </div>
                            <div class="h-2 rounded bg-slate-700/70">
                                <div class="h-2 rounded bg-violet-400" :style="{ width: `${Math.round(stat.reliability * 100)}%` }" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                    <p class="mb-2 font-semibold">{{ t('fio.lookup.attemptedSources') }}</p>
                    <div v-if="(result.source.attemptedSources ?? []).length === 0" class="text-muted-foreground">-</div>
                    <div v-else class="space-y-1">
                        <p
                            v-for="attempt in result.source.attemptedSources"
                            :key="`attempt-${attempt.source}`"
                            class="text-muted-foreground"
                        >
                            {{ attempt.source }}: {{ attempt.ok ? t('fio.lookup.ok') : t('fio.lookup.failed') }},
                            {{ t('fio.lookup.matches') }} {{ attempt.count }},
                            {{ t('fio.lookup.durationMs') }} {{ attempt.durationMs }}
                        </p>
                    </div>
                </div>

                <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                    <p class="mb-2 font-semibold">{{ t('fio.lookup.sourceErrors') }}</p>
                    <div v-if="(result.source.sourceErrors ?? []).length === 0" class="text-emerald-300">{{ t('fio.lookup.noErrors') }}</div>
                    <div v-else class="space-y-1">
                        <p
                            v-for="(sourceError, idx) in result.source.sourceErrors"
                            :key="`src-error-${sourceError.source}-${idx}`"
                            class="text-rose-300"
                        >
                            {{ sourceError.source }}: {{ sourceError.message }} ({{ t('fio.lookup.durationMs') }} {{ sourceError.durationMs }})
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 xl:grid-cols-2">
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                        <p class="mb-2 font-semibold">{{ t('fio.lookup.regionClusters') }}</p>
                        <div v-if="result.clusters.regions.length === 0" class="text-muted-foreground">{{ t('fio.lookup.noClusters') }}</div>
                        <div v-else class="space-y-2">
                            <div v-for="cluster in result.clusters.regions" :key="`region-${cluster.key}`">
                                <div class="mb-1 flex items-center justify-between">
                                    <span>{{ regionLabel(cluster.key) }}</span>
                                    <span class="text-muted-foreground">{{ cluster.count }} ({{ cluster.percent }}%)</span>
                                </div>
                                <div class="h-2 rounded bg-slate-700/70">
                                    <div class="h-2 rounded bg-cyan-400" :style="{ width: `${cluster.percent}%` }" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                        <p class="mb-2 font-semibold">{{ t('fio.lookup.ageClusters') }}</p>
                        <div v-if="result.clusters.ages.length === 0" class="text-muted-foreground">{{ t('fio.lookup.noClusters') }}</div>
                        <div v-else class="space-y-2">
                            <div v-for="cluster in result.clusters.ages" :key="`age-${cluster.key}`">
                                <div class="mb-1 flex items-center justify-between">
                                    <span>{{ ageBucketLabel(cluster.key) }}</span>
                                    <span class="text-muted-foreground">{{ cluster.count }} ({{ cluster.percent }}%)</span>
                                </div>
                                <div class="h-2 rounded bg-slate-700/70">
                                    <div class="h-2 rounded bg-emerald-400" :style="{ width: `${cluster.percent}%` }" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                    <p class="mb-2 font-semibold">{{ t('fio.lookup.publicMatches') }}</p>
                    <p v-if="result.matches.length === 0" class="text-muted-foreground">{{ t('fio.lookup.noMatches') }}</p>
                    <div v-else class="space-y-2">
                        <article
                            v-for="(item, idx) in result.matches"
                            :key="`${item.url}-${idx}`"
                            class="rounded-md border border-border/70 bg-background/70 p-2"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <p class="break-words [overflow-wrap:anywhere] font-semibold">{{ item.title }}</p>
                                    <a
                                        :href="item.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="break-words [overflow-wrap:anywhere] text-cyan-300 hover:underline"
                                    >
                                        {{ item.url }}
                                    </a>
                                </div>
                                <span class="rounded-full border border-cyan-500/30 bg-cyan-500/10 px-2 py-0.5 text-[11px]">
                                    {{ t('fio.lookup.confidence') }}: {{ item.confidence }}%
                                </span>
                            </div>
                            <p class="mt-2 break-words [overflow-wrap:anywhere] text-muted-foreground">{{ item.snippet || '-' }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-3 text-muted-foreground">
                                <span>{{ t('fio.lookup.region') }}: {{ regionLabel(item.region) }}</span>
                                <span>{{ t('fio.lookup.age') }}: {{ item.age ?? '-' }}</span>
                                <span>{{ t('fio.lookup.ageBucket') }}: {{ ageBucketLabel(item.ageBucket) }}</span>
                                <span>{{ t('fio.lookup.domain') }}: {{ item.domain ?? '-' }}</span>
                                <span>{{ t('fio.lookup.source') }}: {{ item.source }}</span>
                                <span>{{ t('fio.lookup.reliability') }}: {{ Math.round(item.sourceReliability * 100) }}%</span>
                                <span>{{ t('fio.lookup.qualifierMatched') }}: {{ item.qualifierMatched ? t('fio.lookup.yes') : t('fio.lookup.no') }}</span>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
