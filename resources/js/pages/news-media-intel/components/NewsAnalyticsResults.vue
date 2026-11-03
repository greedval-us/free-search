<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import type { NewsAnalytics } from '../types';
import BrandComparison from './BrandComparison.vue';
import ContentIntelligence from './ContentIntelligence.vue';
import CoverageSummary from './CoverageSummary.vue';
import DomainVisibility from './DomainVisibility.vue';
import EvidenceLinks from './EvidenceLinks.vue';
import NewsDiscovery from './NewsDiscovery.vue';
import NewsMentions from './NewsMentions.vue';
import NewsTimeline from './NewsTimeline.vue';
const props = defineProps<{
    result: NewsAnalytics;
    viewUrl: string;
    htmlUrl: string;
    jsonUrl: string;
}>();
const emit = defineEmits<{ query: [value: string] }>();
const { t, locale } = useI18n();
const percent = (value: number) =>
    new Intl.NumberFormat(locale.value, { maximumFractionDigits: 2 }).format(
        value
    );
const dateLabel = (raw: string) => {
    const date = new Date(raw);

    return Number.isNaN(date.getTime())
        ? raw
        : new Intl.DateTimeFormat(locale.value, {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(date);
};
const freshness = computed(() =>
    props.result.summary.freshnessPercent === null
        ? '—'
        : `${percent(props.result.summary.freshnessPercent)}%`
);
const publishers = computed(() => props.result.publishers.slice(0, 20));
</script>
<template>
    <div class="space-y-3">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 flex-1 basis-64">
                <h3 class="text-base font-semibold break-words">
                    {{ result.query }}
                </h3>
                <p class="mt-1 text-xs leading-5 text-muted-foreground">
                    {{
                        t('newsMediaIntel.analytics.checkedAt', {
                            date: dateLabel(result.checkedAt),
                        })
                    }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a
                    :href="viewUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="intel-button-secondary text-xs"
                    >{{ t('newsMediaIntel.analytics.viewReport') }}</a
                ><a :href="htmlUrl" class="intel-button-secondary text-xs">{{
                    t('newsMediaIntel.analytics.html')
                }}</a
                ><a :href="jsonUrl" class="intel-button-secondary text-xs">{{
                    t('newsMediaIntel.analytics.json')
                }}</a>
            </div>
        </header>
        <p class="text-xs leading-5 text-muted-foreground">
            {{
                t('newsMediaIntel.analytics.reportExpiry', {
                    date: dateLabel(result.reportExpiresAt),
                })
            }}
        </p>
        <p
            v-if="result.coverage.partial"
            class="rounded-lg border border-amber-500/30 bg-amber-500/5 px-3 py-2 text-sm leading-5"
        >
            {{ t('newsMediaIntel.analytics.partialHelp') }}
        </p>
        <dl
            class="grid grid-cols-2 gap-x-4 gap-y-2 border-y border-border/60 py-3 sm:grid-cols-4"
        >
            <div>
                <dt class="text-xs leading-5 text-muted-foreground">
                    {{ t('newsMediaIntel.summary.mentions') }}
                </dt>
                <dd class="text-xl font-semibold tabular-nums">
                    {{ result.summary.mentions }}
                </dd>
            </div>
            <div>
                <dt class="text-xs leading-5 text-muted-foreground">
                    {{ t('newsMediaIntel.analytics.publishers') }}
                </dt>
                <dd class="text-xl font-semibold tabular-nums">
                    {{ result.summary.publishers }}
                </dd>
            </div>
            <div>
                <dt class="text-xs leading-5 text-muted-foreground">
                    {{ t('newsMediaIntel.analytics.freshness') }}
                </dt>
                <dd class="text-xl font-semibold tabular-nums">
                    {{ freshness }}
                </dd>
            </div>
            <div>
                <dt class="text-xs leading-5 text-muted-foreground">
                    {{ t('newsMediaIntel.analytics.undated') }}
                </dt>
                <dd class="text-xl font-semibold tabular-nums">
                    {{ result.summary.undatedMentions }}
                </dd>
            </div>
        </dl>
        <p class="text-xs leading-5 text-muted-foreground">
            {{
                t('newsMediaIntel.analytics.freshnessHelp', {
                    known: result.summary.knownDates,
                    fresh: result.summary.freshMentions,
                    future: result.summary.futureDates,
                })
            }}
        </p>
        <div class="grid gap-3 lg:grid-cols-2">
            <CoverageSummary
                :coverage="result.coverage.news"
                :label="t('newsMediaIntel.filters.news')"
            /><CoverageSummary
                :coverage="result.coverage.general"
                :label="t('newsMediaIntel.filters.general')"
            />
        </div>
        <div class="grid items-start gap-3 lg:grid-cols-2">
            <BrandComparison :comparison="result.brandComparison" />
            <DomainVisibility
                :visibility="result.visibility"
                :available="result.coverage.general.status !== 'unavailable'"
            />
        </div>
        <div
            class="grid items-start gap-3 xl:grid-cols-[minmax(0,2fr)_minmax(16rem,1fr)]"
        >
            <NewsMentions :mentions="result.mentions" />
            <div class="min-w-0 space-y-3">
                <ContentIntelligence
                    :result="result"
                    @query="emit('query', $event)"
                />
                <details class="intel-section min-w-0">
                    <summary
                        class="cursor-pointer rounded-sm text-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    >
                        {{ t('newsMediaIntel.analytics.publishersTitle') }}
                    </summary>
                    <p
                        class="mt-2 mb-3 text-xs leading-5 text-muted-foreground"
                    >
                        {{ t('newsMediaIntel.analytics.publishersHelp') }}
                    </p>
                    <p
                        v-if="!publishers.length"
                        class="text-sm text-muted-foreground"
                    >
                        {{ t('newsMediaIntel.analytics.noPublishers') }}
                    </p>
                    <div v-else class="divide-y divide-border/60">
                        <article
                            v-for="publisher in publishers"
                            :key="publisher.host"
                            class="py-2 first:pt-0 last:pb-0"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-medium break-all">
                                    {{ publisher.host }}
                                </p>
                                <span class="shrink-0 text-xs tabular-nums"
                                    >{{ publisher.count }} ·
                                    {{ percent(publisher.share) }}%</span
                                >
                            </div>
                            <div class="my-2 h-1.5 rounded-full bg-muted">
                                <div
                                    class="h-full rounded-full bg-primary/60"
                                    :style="{ width: `${publisher.share}%` }"
                                />
                            </div>
                            <p
                                v-if="publisher.engines.length"
                                class="text-xs text-muted-foreground"
                            >
                                {{ publisher.engines.join(', ') }}
                            </p>
                            <EvidenceLinks :urls="publisher.evidenceUrls" />
                        </article>
                    </div>
                </details>
                <NewsDiscovery
                    :suggestions="result.suggestions"
                    :corrections="result.corrections"
                    :answers="result.answers"
                    :infoboxes="result.infoboxes"
                    @query="emit('query', $event)"
                />
                <NewsTimeline
                    :timeline="result.timeline"
                    :undated="result.summary.undatedMentions"
                />
            </div>
        </div>
    </div>
</template>
