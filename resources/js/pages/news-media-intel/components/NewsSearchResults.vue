<script setup lang="ts">
import { computed } from 'vue';
import InfoCard from '@/components/ui/InfoCard.vue';
import MetricCard from '@/components/ui/MetricCard.vue';
import { useI18n } from '@/composables/useI18n';
import type { NewsResult } from '../types';
import CoverageSummary from './CoverageSummary.vue';
import NewsDiscovery from './NewsDiscovery.vue';
import NewsMentions from './NewsMentions.vue';
import NewsTimeline from './NewsTimeline.vue';
const props = defineProps<{ result: NewsResult }>();
const emit = defineEmits<{ query: [value: string] }>();
const { t } = useI18n();
const topics = computed(() => props.result.topics.slice(0, 18));
const undated = computed(
    () =>
        props.result.mentions.filter(
            (mention) =>
                !mention.publishedAt ||
                Number.isNaN(new Date(mention.publishedAt).getTime())
        ).length
);
</script>
<template>
    <div class="space-y-3">
        <p class="text-sm font-medium break-words">{{ result.query }}</p>
        <CoverageSummary v-if="result.coverage" :coverage="result.coverage" />
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
        <NewsDiscovery
            :suggestions="result.suggestions"
            :corrections="result.corrections"
            :answers="result.answers"
            :infoboxes="result.infoboxes"
            @query="emit('query', $event)"
        />
        <InfoCard :title="t('newsMediaIntel.topics.title')">
            <p class="mb-2 text-xs leading-5 text-muted-foreground">
                {{ t('newsMediaIntel.help.topics') }}
            </p>
            <p v-if="!topics.length" class="intel-empty">
                {{ t('newsMediaIntel.analytics.noTopics') }}
            </p>
            <div v-else class="flex flex-wrap gap-2">
                <button
                    v-for="topic in topics"
                    :key="topic.topic"
                    type="button"
                    class="rounded-lg border border-border px-2 py-1 text-xs text-primary hover:bg-muted"
                    @click="emit('query', topic.topic)"
                >
                    {{ topic.topic }} · {{ topic.count }}
                </button>
            </div>
        </InfoCard>
        <NewsTimeline :timeline="result.timeline" :undated="undated" />
        <NewsMentions :mentions="result.mentions" />
    </div>
</template>
