<script setup lang="ts">
import { computed } from 'vue';
import InfoCard from '@/components/ui/InfoCard.vue';
import MetricCard from '@/components/ui/MetricCard.vue';
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
        <InfoCard :title="result.query">
            <p class="text-xs leading-5 text-muted-foreground">
                {{
                    t('newsMediaIntel.analytics.checkedAt', {
                        date: dateLabel(result.checkedAt),
                    })
                }}
            </p>
            <p
                v-if="result.coverage.partial"
                class="mt-2 rounded-lg bg-amber-500/10 p-2 text-xs leading-5"
            >
                {{ t('newsMediaIntel.analytics.partialHelp') }}
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
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
            <p class="mt-2 text-xs leading-5 text-muted-foreground">
                {{
                    t('newsMediaIntel.analytics.reportExpiry', {
                        date: dateLabel(result.reportExpiresAt),
                    })
                }}
            </p>
        </InfoCard>
        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
            <MetricCard
                :title="t('newsMediaIntel.summary.mentions')"
                :value="result.summary.mentions"
            /><MetricCard
                :title="t('newsMediaIntel.analytics.publishers')"
                :value="result.summary.publishers"
            /><MetricCard
                :title="t('newsMediaIntel.analytics.freshness')"
                :value="freshness"
            /><MetricCard
                :title="t('newsMediaIntel.analytics.undated')"
                :value="result.summary.undatedMentions"
            />
        </div>
        <p class="px-1 text-xs leading-5 text-muted-foreground">
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
        <BrandComparison :comparison="result.brandComparison" />
        <DomainVisibility
            :visibility="result.visibility"
            :available="result.coverage.general.status !== 'unavailable'"
        />
        <ContentIntelligence :result="result" @query="emit('query', $event)" />
        <InfoCard :title="t('newsMediaIntel.analytics.publishersTitle')">
            <p class="mb-3 text-xs leading-5 text-muted-foreground">
                {{ t('newsMediaIntel.analytics.publishersHelp') }}
            </p>
            <p v-if="!publishers.length" class="intel-empty">
                {{ t('newsMediaIntel.analytics.noPublishers') }}
            </p>
            <div v-else class="space-y-3">
                <article
                    v-for="publisher in publishers"
                    :key="publisher.host"
                    class="border-b border-border/50 pb-3 last:border-0"
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
                    <div class="my-2 h-2 rounded-full bg-muted">
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
        </InfoCard>
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
        <NewsMentions :mentions="result.mentions" />
    </div>
</template>
