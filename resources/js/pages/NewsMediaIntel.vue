<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Newspaper } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import IntelSearchForm from '@/components/ui/IntelSearchForm.vue';
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue';
import MetricCard from '@/components/ui/MetricCard.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import SectionCard from '@/components/ui/SectionCard.vue';
import { getRepeatQueryParams, isRepeatAutorunEnabled, readRepeatQueryParam } from '@/composables/useRepeatQuery';
import { useIntelLookup } from '@/composables/useIntelLookup';
import { useI18n } from '@/composables/useI18n';
import type { NewsResult } from './news-media-intel/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'News & Media Intel', href: '/news-media-intel' }],
    },
});

const { t, locale } = useI18n();
const pageTitle = computed(() => t('newsMediaIntel.headTitle'));
const query = ref('');
const sourceLabels: Record<string, string> = {
    googlenews: 'newsMediaIntel.sources.googlenews',
    bing: 'newsMediaIntel.sources.bing',
    newsapi: 'newsMediaIntel.sources.newsapi',
};

const sourceLabel = (source: string): string => {
    const key = sourceLabels[source.toLowerCase()];
    return key ? t(key) : source;
};

const buildTimelineFromMentions = (mentions: NewsResult['mentions']): Array<{ date: string; mentions: number }> => {
    const bucket: Record<string, number> = {};

    for (const mention of mentions) {
        const raw = (mention.publishedAt ?? '').trim();
        if (!raw) continue;

        const date = new Date(raw);
        if (Number.isNaN(date.getTime())) continue;

        const yyyy = date.getUTCFullYear();
        const mm = String(date.getUTCMonth() + 1).padStart(2, '0');
        const dd = String(date.getUTCDate()).padStart(2, '0');
        const key = `${yyyy}-${mm}-${dd}`;

        bucket[key] = (bucket[key] ?? 0) + 1;
    }

    return Object.entries(bucket)
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([date, mentions]) => ({ date, mentions }));
};

const timelineChart = computed(() => {
    const backendTimeline = result.value?.timeline ?? [];
    const base = backendTimeline.length > 0 ? backendTimeline : buildTimelineFromMentions(result.value?.mentions ?? []);
    const sliced = base.slice(0, 30);

    if (sliced.length === 0 && (result.value?.mentions?.length ?? 0) > 0) {
        const today = new Date();
        const yyyy = today.getUTCFullYear();
        const mm = String(today.getUTCMonth() + 1).padStart(2, '0');
        const dd = String(today.getUTCDate()).padStart(2, '0');
        const date = `${yyyy}-${mm}-${dd}`;

        return [{ date, mentions: result.value!.mentions.length, shortDate: `${mm}-${dd}` }];
    }

    return sliced.map((point) => ({
        ...point,
        shortDate: point.date.slice(5),
    }));
});

const timelineMax = computed(() => Math.max(...timelineChart.value.map((x) => x.mentions), 1));

const topTopicsChart = computed(() => (result.value?.topics ?? []).slice(0, 10));
const topTopicsMax = computed(() => Math.max(...topTopicsChart.value.map((x) => x.count), 1));

const { loading, error, result, canSearch, lookup } = useIntelLookup<NewsResult>(query, {
    endpoint: '/news-media-intel/lookup',
    minLength: 2,
    queryKey: 'query',
    locale,
    requiredError: t('newsMediaIntel.errors.queryRequired'),
    fallbackError: t('newsMediaIntel.errors.lookupFailed'),
});

onMounted(() => {
    const params = getRepeatQueryParams();

    if (!params) {
        return;
    }

    const value = readRepeatQueryParam(params, ['query']);

    if (value !== '') {
        query.value = value;
    }

    if (isRepeatAutorunEnabled(params) && canSearch.value) {
        void lookup();
    }
});
</script>

<template>
    <Head :title="pageTitle" />

    <div class="flex h-full min-h-0 flex-1 flex-col gap-4 overflow-hidden rounded-xl p-4">
        <IntelSearchPanel>
            <PageHeader
                :icon="Newspaper"
                :title="t('newsMediaIntel.title')"
                :description="t('newsMediaIntel.description')"
                :help-label="t('newsMediaIntel.help.label')"
                :help-text="t('newsMediaIntel.help.overview')"
            />

            <IntelSearchForm
                v-model="query"
                :label="t('newsMediaIntel.form.query')"
                :placeholder="t('newsMediaIntel.form.placeholder')"
                :button-text="t('newsMediaIntel.form.search')"
                :loading-text="t('newsMediaIntel.form.searching')"
                :loading="loading"
                :disabled="!canSearch"
                :error="error"
                @submit="lookup"
            />
        </IntelSearchPanel>

        <IntelResultPanel>
            <EmptyState v-if="!result" :text="t('newsMediaIntel.empty')" />

            <div v-else class="telegram-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    <MetricCard :title="t('newsMediaIntel.summary.mentions')" :value="result.mentions.length" />
                    <MetricCard :title="t('newsMediaIntel.summary.positive')" :value="result.sentiment.positive" />
                    <MetricCard :title="t('newsMediaIntel.summary.neutral')" :value="result.sentiment.neutral" />
                    <MetricCard :title="t('newsMediaIntel.summary.negative')" :value="result.sentiment.negative" />
                </div>

                <SectionCard :title="t('newsMediaIntel.topics.title')">
                    <p class="mb-2 text-xs text-muted-foreground">{{ t('newsMediaIntel.help.topics') }}</p>
                    <div class="mb-3 flex flex-wrap gap-2">
                        <span v-for="topic in result.topics.slice(0, 18)" :key="topic.topic" class="rounded-full border border-border/80 px-2 py-1 text-xs text-muted-foreground">
                            {{ topic.topic }} ({{ topic.count }})
                        </span>
                    </div>
                    <div v-if="topTopicsChart.length > 0" class="space-y-2">
                        <div v-for="topic in topTopicsChart" :key="`topic-chart-${topic.topic}`" class="space-y-1">
                            <div class="flex items-center justify-between gap-2 text-xs">
                                <span class="truncate text-foreground">{{ topic.topic }}</span>
                                <span class="shrink-0 text-muted-foreground">{{ topic.count }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-muted/60">
                                <div
                                    class="h-2 rounded-full bg-cyan-300/70 transition-all"
                                    :style="{ width: `${Math.max((topic.count / topTopicsMax) * 100, topic.count > 0 ? 4 : 0)}%` }"
                                />
                            </div>
                        </div>
                    </div>
                </SectionCard>

                <SectionCard :title="t('newsMediaIntel.timeline.title')">
                    <p class="mb-2 text-xs text-muted-foreground">{{ t('newsMediaIntel.help.timeline') }}</p>
                    <div v-if="timelineChart.length === 0" class="intel-empty">{{ t('newsMediaIntel.timeline.none') }}</div>
                    <div v-else class="space-y-1 text-sm">
                        <p v-for="point in timelineChart" :key="`timeline-list-${point.date}`">{{ point.date }}: <span class="text-muted-foreground">{{ point.mentions }}</span></p>
                    </div>
                </SectionCard>

                <SectionCard :title="t('newsMediaIntel.mentions.title')">
                    <p class="mb-2 text-xs text-muted-foreground">{{ t('newsMediaIntel.help.mentions') }}</p>
                    <div v-if="result.mentions.length === 0" class="intel-empty">{{ t('newsMediaIntel.mentions.none') }}</div>
                    <div v-else class="space-y-2">
                        <article v-for="(item, index) in result.mentions.slice(0, 60)" :key="`${item.link}-${index}`" class="intel-surface">
                            <div class="mb-1 flex min-w-0 items-center justify-between gap-2">
                                <p class="min-w-0 break-words text-sm font-medium">{{ item.title }}</p>
                                <span class="shrink-0 text-xs text-cyan-300">{{ sourceLabel(item.source) }}</span>
                            </div>
                            <p class="mb-1 break-words text-xs text-muted-foreground">{{ item.snippet }}</p>
                            <a
                                :href="item.link"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="block break-all text-xs text-cyan-300 hover:underline"
                            >
                                {{ item.link }}
                            </a>
                        </article>
                    </div>
                </SectionCard>
            </div>
        </IntelResultPanel>
    </div>
</template>
