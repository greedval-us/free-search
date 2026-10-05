<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Newspaper } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import InfoCard from '@/components/ui/InfoCard.vue';
import IntelModuleLayout from '@/components/ui/IntelModuleLayout.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import IntelSearchForm from '@/components/ui/IntelSearchForm.vue';
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue';
import MetricCard from '@/components/ui/MetricCard.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import { useI18n } from '@/composables/useI18n';
import { useIntelLookup } from '@/composables/useIntelLookup';
import {
    getRepeatQueryParams,
    isRepeatAutorunEnabled,
    readRepeatQueryParam,
} from '@/composables/useRepeatQuery';
import { newsMediaIntel } from '@/routes';
import { lookup as newsLookup } from '@/routes/news-media-intel';
import { resolveArticleUrl } from './news-media-intel/articleUrl';
import type { NewsResult } from './news-media-intel/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'News & Media Intel',
                titleKey: 'newsMediaIntel.headTitle',
                href: newsMediaIntel(),
            },
        ],
    },
});

const { t, locale } = useI18n();
const pageTitle = computed(() => t('newsMediaIntel.headTitle'));
const query = ref('');

const timelineChart = computed(() =>
    (result.value?.timeline ?? []).slice(0, 30).map((point) => ({
        ...point,
        shortDate: point.date.slice(5),
    }))
);

const topTopicsChart = computed(() =>
    (result.value?.topics ?? []).slice(0, 10)
);
const topTopicsMax = computed(() =>
    Math.max(...topTopicsChart.value.map((x) => x.count), 1)
);

const { loading, error, result, canSearch, lookup } =
    useIntelLookup<NewsResult>(query, {
        endpoint: newsLookup.url(),
        minLength: 2,
        queryKey: 'query',
        locale,
        requiredError: t('newsMediaIntel.errors.queryRequired'),
        fallbackError: t('newsMediaIntel.errors.lookupFailed'),
    });

const mentionCards = computed(() =>
    (result.value?.mentions ?? []).slice(0, 60).map((mention) => ({
        ...mention,
        href: resolveArticleUrl(mention.link),
    }))
);

const publishedDateLabel = (value: string | null): string => {
    const raw = (value ?? '').trim();
    const date = raw ? new Date(raw) : null;

    if (!date || Number.isNaN(date.getTime())) {
        return t('newsMediaIntel.mentions.dateUnknown');
    }

    return new Intl.DateTimeFormat(locale.value, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(date);
};

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
    <h1 class="sr-only">{{ pageTitle }}</h1>

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

            <div
                v-else
                class="intel-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1"
            >
                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    <MetricCard
                        :title="t('newsMediaIntel.summary.mentions')"
                        :value="result.mentions.length"
                    />
                    <MetricCard
                        :title="t('newsMediaIntel.summary.positive')"
                        :value="result.sentiment.positive"
                        tone="positive"
                    />
                    <MetricCard
                        :title="t('newsMediaIntel.summary.neutral')"
                        :value="result.sentiment.neutral"
                    />
                    <MetricCard
                        :title="t('newsMediaIntel.summary.negative')"
                        :value="result.sentiment.negative"
                        tone="danger"
                    />
                </div>

                <InfoCard :title="t('newsMediaIntel.topics.title')">
                    <p class="mb-2 text-xs text-muted-foreground">
                        {{ t('newsMediaIntel.help.topics') }}
                    </p>
                    <div class="mb-3 flex flex-wrap gap-2">
                        <span
                            v-for="topic in result.topics.slice(0, 18)"
                            :key="topic.topic"
                            class="rounded-full border border-border/80 px-2 py-1 text-xs text-muted-foreground"
                        >
                            {{ topic.topic }} ({{ topic.count }})
                        </span>
                    </div>
                    <div v-if="topTopicsChart.length > 0" class="space-y-2">
                        <div
                            v-for="topic in topTopicsChart"
                            :key="`topic-chart-${topic.topic}`"
                            class="space-y-1"
                        >
                            <div
                                class="flex items-center justify-between gap-2 text-xs"
                            >
                                <span class="truncate text-foreground">{{
                                    topic.topic
                                }}</span>
                                <span class="shrink-0 text-muted-foreground">{{
                                    topic.count
                                }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-muted/60">
                                <div
                                    class="h-2 rounded-full bg-cyan-300/70 transition-all"
                                    :style="{
                                        width: `${Math.max((topic.count / topTopicsMax) * 100, topic.count > 0 ? 4 : 0)}%`,
                                    }"
                                />
                            </div>
                        </div>
                    </div>
                </InfoCard>

                <InfoCard :title="t('newsMediaIntel.timeline.title')">
                    <p class="mb-2 text-xs text-muted-foreground">
                        {{ t('newsMediaIntel.help.timeline') }}
                    </p>
                    <div v-if="timelineChart.length === 0" class="intel-empty">
                        {{ t('newsMediaIntel.timeline.none') }}
                    </div>
                    <div v-else class="space-y-1 text-sm">
                        <p
                            v-for="point in timelineChart"
                            :key="`timeline-list-${point.date}`"
                            class="intel-data-row text-sm"
                        >
                            <span class="intel-data-label"
                                >{{ point.date }}:</span
                            >
                            <span class="intel-data-value">{{
                                point.mentions
                            }}</span>
                        </p>
                    </div>
                </InfoCard>

                <InfoCard :title="t('newsMediaIntel.mentions.title')">
                    <p class="mb-2 text-xs text-muted-foreground">
                        {{ t('newsMediaIntel.help.mentions') }}
                    </p>
                    <div
                        v-if="result.mentions.length === 0"
                        class="intel-empty"
                    >
                        {{ t('newsMediaIntel.mentions.none') }}
                    </div>
                    <div v-else class="space-y-2">
                        <article
                            v-for="(item, index) in mentionCards"
                            :key="`${item.link}-${index}`"
                            class="intel-surface"
                        >
                            <div
                                class="mb-1 flex min-w-0 flex-col items-start gap-1 sm:flex-row sm:items-center sm:justify-between sm:gap-2"
                            >
                                <p
                                    class="min-w-0 text-sm font-medium break-words"
                                >
                                    {{ item.title }}
                                </p>
                                <span class="shrink-0 text-xs text-primary">{{
                                    t('newsMediaIntel.sources.searxng')
                                }}</span>
                            </div>
                            <p
                                class="mb-1 text-xs break-words text-muted-foreground"
                            >
                                {{ item.snippet }}
                            </p>
                            <p class="mb-1 text-xs text-muted-foreground">
                                {{ publishedDateLabel(item.publishedAt) }}
                            </p>
                            <a
                                v-if="item.href"
                                :href="item.href"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="block text-xs break-all text-primary hover:underline"
                            >
                                {{ item.link }}
                            </a>
                        </article>
                    </div>
                </InfoCard>
            </div>
        </IntelResultPanel>
    </IntelModuleLayout>
</template>
