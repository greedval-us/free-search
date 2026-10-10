<script setup lang="ts">
import InfoCard from '@/components/ui/InfoCard.vue';
import { useI18n } from '@/composables/useI18n';
import type { NewsAnalytics } from '../types';
import EvidenceLinks from './EvidenceLinks.vue';
defineProps<{ result: NewsAnalytics }>();
const emit = defineEmits<{ query: [value: string] }>();
const { t } = useI18n();
const opportunityCopy = (item: NewsAnalytics['contentOpportunities'][number]) =>
    t(`newsMediaIntel.analytics.opportunities.${item.code}`, item.params);
</script>
<template>
    <InfoCard :title="t('newsMediaIntel.analytics.contentTitle')">
        <p class="mb-3 text-xs leading-5 text-muted-foreground">
            {{ t('newsMediaIntel.analytics.contentHelp') }}
        </p>
        <div v-if="result.contentOpportunities.length" class="mb-4 space-y-2">
            <article
                v-for="(item, index) in result.contentOpportunities"
                :key="`${item.code}-${index}`"
                class="rounded-xl border border-primary/20 bg-primary/5 p-3"
            >
                <p class="text-sm leading-6 break-words">
                    {{ opportunityCopy(item) }}
                </p>
                <EvidenceLinks :urls="item.evidenceUrls" />
            </article>
        </div>
        <p class="mb-2 text-sm font-medium">
            {{ t('newsMediaIntel.topics.title') }}
        </p>
        <p v-if="!result.topics.length" class="text-xs text-muted-foreground">
            {{ t('newsMediaIntel.analytics.noTopics') }}
        </p>
        <div v-else class="space-y-2">
            <article
                v-for="topic in result.topics.slice(0, 15)"
                :key="topic.topic"
                class="border-b border-border/50 pb-2 last:border-0"
            >
                <div class="flex items-start justify-between gap-2">
                    <button
                        type="button"
                        class="text-left text-sm break-words text-primary hover:underline"
                        @click="emit('query', topic.topic)"
                    >
                        {{ topic.topic }}</button
                    ><span
                        class="shrink-0 text-xs text-muted-foreground tabular-nums"
                        >{{
                            t('newsMediaIntel.analytics.documents', {
                                count: topic.count,
                            })
                        }}</span
                    >
                </div>
                <EvidenceLinks :urls="topic.evidenceUrls" />
            </article>
        </div>
        <p class="mt-4 mb-2 text-sm font-medium">
            {{ t('newsMediaIntel.analytics.questionsTitle') }}
        </p>
        <p
            v-if="!result.questions.length"
            class="text-xs text-muted-foreground"
        >
            {{ t('newsMediaIntel.analytics.noQuestions') }}
        </p>
        <div v-else class="space-y-2">
            <article
                v-for="question in result.questions.slice(0, 10)"
                :key="question.question"
                class="rounded-lg bg-muted/30 p-2"
            >
                <button
                    type="button"
                    class="text-left text-sm break-words text-primary hover:underline"
                    @click="emit('query', question.question)"
                >
                    {{ question.question }}
                </button>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{
                        t('newsMediaIntel.analytics.documents', {
                            count: question.count,
                        })
                    }}
                </p>
                <EvidenceLinks :urls="question.evidenceUrls" />
            </article>
        </div>
    </InfoCard>
</template>
