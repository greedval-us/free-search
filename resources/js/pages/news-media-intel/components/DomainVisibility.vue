<script setup lang="ts">
import InfoCard from '@/components/ui/InfoCard.vue';
import MetricCard from '@/components/ui/MetricCard.vue';
import { useI18n } from '@/composables/useI18n';
import { resolveArticleUrl } from '../articleUrl';
import type { NewsAnalytics } from '../types';
withDefaults(
    defineProps<{
        visibility: NewsAnalytics['visibility'];
        available?: boolean;
    }>(),
    { available: true }
);
const { t, locale } = useI18n();
const percent = (value: number) =>
    `${new Intl.NumberFormat(locale.value, { maximumFractionDigits: 2 }).format(value)}%`;
</script>
<template>
    <InfoCard :title="t('newsMediaIntel.analytics.visibilityTitle')">
        <p class="mb-3 text-xs leading-5 text-muted-foreground">
            {{ t('newsMediaIntel.analytics.visibilityHelp') }}
        </p>
        <p v-if="!visibility.domain" class="intel-empty">
            {{ t('newsMediaIntel.analytics.noDomain') }}
        </p>
        <p v-else-if="!available" class="intel-empty">
            {{ t('newsMediaIntel.analytics.visibilityUnavailable') }}
        </p>
        <template v-else>
            <p class="mb-3 text-sm font-medium break-all">
                {{ visibility.domain }}
            </p>
            <div class="grid gap-2 sm:grid-cols-3">
                <MetricCard
                    :title="t('newsMediaIntel.analytics.domainMatches')"
                    :value="visibility.domainMatches"
                /><MetricCard
                    :title="t('newsMediaIntel.analytics.domainShare')"
                    :value="percent(visibility.share)"
                /><MetricCard
                    :title="t('newsMediaIntel.analytics.bestPosition')"
                    :value="visibility.bestObservedPosition ?? '—'"
                />
            </div>
            <p class="mt-2 text-xs text-muted-foreground">
                {{
                    t('newsMediaIntel.analytics.generalSample', {
                        count: visibility.generalResults,
                    })
                }}
            </p>
            <p
                v-if="!visibility.pages.length"
                class="mt-3 text-xs text-muted-foreground"
            >
                {{ t('newsMediaIntel.analytics.domainNotFound') }}
            </p>
            <ol v-else class="mt-3 space-y-2">
                <li
                    v-for="page in visibility.pages"
                    :key="page.url"
                    class="rounded-lg border border-border/70 p-2 text-xs"
                >
                    <span class="mr-2 text-muted-foreground">{{
                        page.position === null ? '—' : `#${page.position}`
                    }}</span
                    ><a
                        v-if="resolveArticleUrl(page.url)"
                        :href="resolveArticleUrl(page.url)!"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="break-words text-primary hover:underline"
                        >{{ page.title || page.url }}</a
                    ><span v-else>{{ page.title }}</span>
                    <p class="mt-1 break-all text-muted-foreground">
                        {{ page.url }}
                    </p>
                </li>
            </ol>
        </template>
    </InfoCard>
</template>
