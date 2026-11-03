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
    <InfoCard
        :title="t('newsMediaIntel.analytics.brandsTitle')"
        :help-label="t('newsMediaIntel.analytics.brandsTitle')"
        :help-text="t('newsMediaIntel.analytics.brandsHelp')"
    >
        <p
            v-if="!comparison.entities.length"
            class="text-sm leading-6 text-muted-foreground"
        >
            {{ t('newsMediaIntel.analytics.noBrands') }}
        </p>
        <div v-else class="divide-y divide-border/60">
            <article
                v-for="entity in comparison.entities"
                :key="`${entity.kind}-${entity.name}`"
                class="min-w-0 py-3 first:pt-0"
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
                <div class="my-2 h-1.5 rounded-full bg-muted">
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
            <p class="pt-2 text-xs leading-5 text-muted-foreground">
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
