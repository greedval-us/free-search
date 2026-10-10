<script setup lang="ts">
import InfoCard from '@/components/ui/InfoCard.vue';
import { useI18n } from '@/composables/useI18n';
import type { NewsAnalytics } from '../types';
import EvidenceLinks from './EvidenceLinks.vue';
defineProps<{ comparison: NewsAnalytics['brandComparison'] }>();
const { t, locale } = useI18n();
const percent = (value: number) =>
    new Intl.NumberFormat(locale.value, { maximumFractionDigits: 2 }).format(
        value
    );
</script>
<template>
    <InfoCard :title="t('newsMediaIntel.analytics.brandsTitle')">
        <p class="mb-3 text-xs leading-5 text-muted-foreground">
            {{ t('newsMediaIntel.analytics.brandsHelp') }}
        </p>
        <p v-if="!comparison.entities.length" class="intel-empty">
            {{ t('newsMediaIntel.analytics.noBrands') }}
        </p>
        <div v-else class="space-y-3">
            <article
                v-for="entity in comparison.entities"
                :key="`${entity.kind}-${entity.name}`"
                class="intel-surface"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm font-medium break-words">
                        {{ entity.name }}
                        <span class="text-xs font-normal text-muted-foreground"
                            >·
                            {{
                                t(
                                    entity.kind === 'brand'
                                        ? 'newsMediaIntel.analytics.brandEntity'
                                        : 'newsMediaIntel.analytics.competitor'
                                )
                            }}</span
                        >
                    </p>
                    <p class="text-xs tabular-nums">
                        {{
                            t('newsMediaIntel.analytics.entityMentions', {
                                count: entity.mentions,
                                share: percent(entity.share),
                            })
                        }}
                    </p>
                </div>
                <div class="my-2 h-2 rounded-full bg-muted">
                    <div
                        class="h-full rounded-full bg-primary/70"
                        :style="{ width: `${entity.share}%` }"
                    />
                </div>
                <p class="flex flex-wrap gap-3 text-xs">
                    <span class="text-emerald-600 dark:text-emerald-400"
                        >{{ t('newsMediaIntel.summary.positive') }}:
                        {{ entity.sentiment.positive }}</span
                    ><span class="text-muted-foreground"
                        >{{ t('newsMediaIntel.summary.neutral') }}:
                        {{ entity.sentiment.neutral }}</span
                    ><span class="text-rose-600 dark:text-rose-400"
                        >{{ t('newsMediaIntel.summary.negative') }}:
                        {{ entity.sentiment.negative }}</span
                    >
                </p>
                <EvidenceLinks :urls="entity.evidenceUrls" />
            </article>
            <p class="text-xs leading-5 text-muted-foreground">
                {{
                    t('newsMediaIntel.analytics.coMentions', {
                        count: comparison.coMentionDocuments,
                        matches: comparison.totalMatches,
                    })
                }}
            </p>
        </div>
    </InfoCard>
</template>
