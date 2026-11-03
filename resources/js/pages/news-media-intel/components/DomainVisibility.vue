<script setup lang="ts">
import InfoCard from '@/components/ui/InfoCard.vue';
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
    <InfoCard
        :title="t('newsMediaIntel.analytics.visibilityTitle')"
        :help-label="t('newsMediaIntel.analytics.visibilityTitle')"
        :help-text="t('newsMediaIntel.analytics.visibilityHelp')"
    >
        <p
            v-if="!visibility.domain"
            class="text-sm leading-6 text-muted-foreground"
        >
            {{ t('newsMediaIntel.analytics.noDomain') }}
        </p>
        <p
            v-else-if="!available"
            class="text-sm leading-6 text-muted-foreground"
        >
            {{ t('newsMediaIntel.analytics.visibilityUnavailable') }}
        </p>
        <template v-else>
            <p class="mb-2 text-sm font-medium break-all">
                {{ visibility.domain }}
            </p>
            <dl class="grid gap-2 sm:grid-cols-3">
                <div>
                    <dt class="text-xs leading-5 text-muted-foreground">
                        {{ t('newsMediaIntel.analytics.domainMatches') }}
                    </dt>
                    <dd class="text-lg font-semibold tabular-nums">
                        {{ visibility.domainMatches }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs leading-5 text-muted-foreground">
                        {{ t('newsMediaIntel.analytics.domainShare') }}
                    </dt>
                    <dd class="text-lg font-semibold tabular-nums">
                        {{ percent(visibility.share) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs leading-5 text-muted-foreground">
                        {{ t('newsMediaIntel.analytics.bestPosition') }}
                    </dt>
                    <dd class="text-lg font-semibold tabular-nums">
                        {{ visibility.bestObservedPosition ?? '—' }}
                    </dd>
                </div>
            </dl>
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
            <ol v-else class="mt-2 divide-y divide-border/60">
                <li
                    v-for="page in visibility.pages"
                    :key="page.url"
                    class="py-2 text-sm leading-5"
                >
                    <span class="mr-2 text-muted-foreground">{{
                        page.position === null ? '—' : `#${page.position}`
                    }}</span
                    ><a
                        v-if="resolveArticleUrl(page.url)"
                        :href="resolveArticleUrl(page.url)!"
                        :title="page.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="rounded-sm break-words text-primary hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        >{{ page.title || page.url }}</a
                    ><span v-else>{{ page.title }}</span>
                    <p
                        class="mt-1 truncate text-xs text-muted-foreground"
                        :title="page.url"
                    >
                        {{ page.url }}
                    </p>
                </li>
            </ol>
        </template>
    </InfoCard>
</template>
