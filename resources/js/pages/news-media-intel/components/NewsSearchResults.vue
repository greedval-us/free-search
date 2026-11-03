<script setup lang="ts">
import { computed } from 'vue';
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
        <dl class="flex flex-wrap gap-x-5 gap-y-2 text-sm">
            <div class="flex items-baseline gap-2">
                <dt class="text-muted-foreground">
                    {{ t('newsMediaIntel.summary.mentions') }}
                </dt>
                <dd class="font-semibold tabular-nums">
                    {{ result.mentions.length }}
                </dd>
            </div>
            <div class="flex items-baseline gap-2">
                <dt class="text-muted-foreground">
                    {{ t('newsMediaIntel.summary.positive') }}
                </dt>
                <dd
                    class="font-semibold text-emerald-700 tabular-nums dark:text-emerald-400"
                >
                    {{ result.sentiment.positive }}
                </dd>
            </div>
            <div class="flex items-baseline gap-2">
                <dt class="text-muted-foreground">
                    {{ t('newsMediaIntel.summary.neutral') }}
                </dt>
                <dd class="font-semibold tabular-nums">
                    {{ result.sentiment.neutral }}
                </dd>
            </div>
            <div class="flex items-baseline gap-2">
                <dt class="text-muted-foreground">
                    {{ t('newsMediaIntel.summary.negative') }}
                </dt>
                <dd
                    class="font-semibold text-rose-700 tabular-nums dark:text-rose-400"
                >
                    {{ result.sentiment.negative }}
                </dd>
            </div>
        </dl>
        <div
            class="grid items-start gap-3 xl:grid-cols-[minmax(0,2fr)_minmax(16rem,1fr)]"
        >
            <NewsMentions :mentions="result.mentions" />
            <div class="min-w-0 space-y-3">
                <details class="intel-section">
                    <summary
                        class="cursor-pointer rounded-sm text-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    >
                        {{ t('newsMediaIntel.topics.title') }}
                        <span
                            class="ml-1 font-normal text-muted-foreground tabular-nums"
                            >{{ topics.length }}</span
                        >
                    </summary>
                    <p class="mt-2 text-xs leading-5 text-muted-foreground">
                        {{ t('newsMediaIntel.help.topics') }}
                    </p>
                    <p
                        v-if="!topics.length"
                        class="mt-2 text-sm text-muted-foreground"
                    >
                        {{ t('newsMediaIntel.analytics.noTopics') }}
                    </p>
                    <div v-else class="mt-2 flex flex-wrap gap-1.5">
                        <button
                            v-for="topic in topics"
                            :key="topic.topic"
                            type="button"
                            class="max-w-full min-w-0 rounded-md border border-border px-2 py-1 text-sm break-words text-primary hover:bg-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                            @click="emit('query', topic.topic)"
                        >
                            {{ topic.topic }}
                            <span
                                class="ml-1 text-xs text-muted-foreground tabular-nums"
                                >{{ topic.count }}</span
                            >
                        </button>
                    </div>
                </details>
                <NewsTimeline :timeline="result.timeline" :undated="undated" />
                <NewsDiscovery
                    :suggestions="result.suggestions"
                    :corrections="result.corrections"
                    :answers="result.answers"
                    :infoboxes="result.infoboxes"
                    @query="emit('query', $event)"
                />
            </div>
        </div>
    </div>
</template>
