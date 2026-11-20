<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import type { SiteIntelSeoAuditResult } from '../../types';
import SeoAuditDetailCard from './SeoAuditDetailCard.vue';

const props = defineProps<{ result: SiteIntelSeoAuditResult }>();
const { t, locale } = useI18n();

const scoreClass = computed(() => {
    const level = props.result.score.level;

    if (level === 'high') {
        return 'text-emerald-700 dark:text-emerald-300';
    }

    if (level === 'medium') {
        return 'text-amber-700 dark:text-amber-300';
    }

    return 'text-rose-700 dark:text-rose-300';
});
</script>

<template>
    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg border border-border/70 bg-background/60 p-3">
            <p class="text-muted-foreground">
                {{ t('siteIntel.seoAudit.score') }}
            </p>
            <p class="mt-1 text-xl font-semibold" :class="scoreClass">
                {{ result.score.value }}
            </p>
        </div>
        <div class="rounded-lg border border-border/70 bg-background/60 p-3">
            <p class="text-muted-foreground">
                {{ t('siteIntel.seoAudit.httpStatus') }}
            </p>
            <p class="mt-1 text-xl font-semibold">
                {{ result.status.httpCode || '-' }}
            </p>
        </div>
        <div class="rounded-lg border border-border/70 bg-background/60 p-3">
            <p class="text-muted-foreground">
                {{ t('siteIntel.seoAudit.ttfb') }}
            </p>
            <p class="mt-1 text-xl font-semibold">
                {{ result.performance.ttfbMsApprox }} ms
            </p>
        </div>
        <div class="rounded-lg border border-border/70 bg-background/60 p-3">
            <p class="text-muted-foreground">
                {{ t('siteIntel.common.checkedAt') }}
            </p>
            <p class="mt-1 text-sm font-semibold">
                {{ new Date(result.checkedAt).toLocaleString(locale) }}
            </p>
        </div>
    </div>
    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
        <div class="mb-1 flex items-center gap-2">
            <p class="text-muted-foreground">
                {{ t('siteIntel.seoAudit.scoreProfile') }}
            </p>
            <span class="group relative inline-flex">
                <span
                    class="inline-flex h-4 w-4 cursor-help items-center justify-center rounded-full border border-border text-[10px] font-semibold text-muted-foreground outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    :aria-label="t('siteIntel.help.label')"
                    role="button"
                    tabindex="0"
                    >?</span
                >
                <span
                    class="pointer-events-none absolute top-5 left-0 z-20 hidden w-[min(20rem,calc(100vw-2rem))] rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-focus-within:block group-hover:block"
                    >{{ t('siteIntel.seoAudit.help.scoreProfile') }}</span
                >
            </span>
        </div>
        <p class="mt-1 text-sm font-semibold">
            {{ t(`siteIntel.seoAudit.profile.${result.score.profile}`) }}
        </p>
    </div>

    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
        <p class="font-semibold">
            {{ t('siteIntel.seoAudit.targetSummary') }}
        </p>
        <p class="mt-1">
            {{ t('siteIntel.seoAudit.finalUrl') }}:
            <span class="break-all text-muted-foreground">{{
                result.target.finalUrl
            }}</span>
        </p>
        <p class="mt-1">
            {{ t('siteIntel.seoAudit.host') }}:
            <span class="text-muted-foreground">{{ result.target.host }}</span>
        </p>
    </div>

    <div class="grid gap-2 xl:grid-cols-2">
        <SeoAuditDetailCard
            :title="t('siteIntel.seoAudit.metaTitle')"
            :hint="t('siteIntel.seoAudit.help.meta')"
        >
            <p>
                {{ t('siteIntel.seoAudit.titleLength') }}:
                <span class="text-muted-foreground">{{
                    result.meta.titleLength
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.descriptionLength') }}:
                <span class="text-muted-foreground">{{
                    result.meta.descriptionLength
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.canonical') }}:
                <span class="break-all text-muted-foreground">{{
                    result.meta.canonical || '-'
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.metaRobots') }}:
                <span class="text-muted-foreground">{{
                    result.meta.robots || '-'
                }}</span>
            </p>
        </SeoAuditDetailCard>
        <SeoAuditDetailCard
            :title="t('siteIntel.seoAudit.indexabilityTitle')"
            :hint="t('siteIntel.seoAudit.help.indexability')"
        >
            <p>
                {{ t('siteIntel.seoAudit.indexable') }}:
                <span class="text-muted-foreground">{{
                    result.indexability.indexable
                        ? t('siteIntel.common.yes')
                        : t('siteIntel.common.no')
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.xRobotsTag') }}:
                <span class="text-muted-foreground">{{
                    result.indexability.xRobotsTag || '-'
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.reason') }}:
                <span class="text-muted-foreground">{{
                    result.indexability.reason
                }}</span>
            </p>
        </SeoAuditDetailCard>
    </div>

    <div class="grid gap-2 xl:grid-cols-3">
        <SeoAuditDetailCard :title="t('siteIntel.seoAudit.headingsTitle')">
            <p>
                H1:
                <span class="text-muted-foreground">{{
                    result.headings.h1
                }}</span>
            </p>
            <p>
                H2:
                <span class="text-muted-foreground">{{
                    result.headings.h2
                }}</span>
            </p>
            <p>
                H3:
                <span class="text-muted-foreground">{{
                    result.headings.h3
                }}</span>
            </p>
        </SeoAuditDetailCard>
        <SeoAuditDetailCard :title="t('siteIntel.seoAudit.linksTitle')">
            <p>
                {{ t('siteIntel.seoAudit.internalLinks') }}:
                <span class="text-muted-foreground">{{
                    result.links.internal
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.externalLinks') }}:
                <span class="text-muted-foreground">{{
                    result.links.external
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.nofollowLinks') }}:
                <span class="text-muted-foreground">{{
                    result.links.nofollow
                }}</span>
            </p>
        </SeoAuditDetailCard>
        <SeoAuditDetailCard
            :title="t('siteIntel.seoAudit.performanceTitle')"
            :hint="t('siteIntel.seoAudit.help.performance')"
        >
            <p>
                {{ t('siteIntel.seoAudit.pageSize') }}:
                <span class="text-muted-foreground"
                    >{{ result.performance.pageSizeKb }} KB</span
                >
            </p>
            <p>
                {{ t('siteIntel.seoAudit.resourcesCount') }}:
                <span class="text-muted-foreground">{{
                    result.performance.resourceCount
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.renderBlocking') }}:
                <span class="text-muted-foreground">{{
                    result.performance.renderBlocking.total
                }}</span>
            </p>
        </SeoAuditDetailCard>
    </div>
</template>
