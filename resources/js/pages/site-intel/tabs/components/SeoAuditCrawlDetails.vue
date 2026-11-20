<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import type { SiteIntelSeoAuditResult } from '../../types';
import SeoAuditDetailCard from './SeoAuditDetailCard.vue';

defineProps<{ result: SiteIntelSeoAuditResult }>();
const { t } = useI18n();
</script>

<template>
    <div class="grid gap-2 xl:grid-cols-3">
        <SeoAuditDetailCard :title="t('siteIntel.seoAudit.crawlTitle')">
            <p>
                {{ t('siteIntel.seoAudit.crawlScanned') }}:
                <span class="text-muted-foreground"
                    >{{ result.crawl.scanned }}/{{ result.crawl.limit }}</span
                >
            </p>
            <p>
                {{ t('siteIntel.seoAudit.crawlPagesWithHreflang') }}:
                <span class="text-muted-foreground">{{
                    result.crawl.hreflangAudit.pagesWithHreflang
                }}</span>
            </p>
        </SeoAuditDetailCard>
        <SeoAuditDetailCard
            :title="t('siteIntel.seoAudit.canonicalAuditTitle')"
        >
            <p>
                {{ t('siteIntel.seoAudit.canonicalMissing') }}:
                <span class="text-muted-foreground">{{
                    result.crawl.canonicalAudit.missing.length
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.canonicalCrossDomain') }}:
                <span class="text-muted-foreground">{{
                    result.crawl.canonicalAudit.crossDomain.length
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.canonicalInvalid') }}:
                <span class="text-muted-foreground">{{
                    result.crawl.canonicalAudit.invalid.length
                }}</span>
            </p>
        </SeoAuditDetailCard>
        <SeoAuditDetailCard :title="t('siteIntel.seoAudit.sitemapAuditTitle')">
            <p>
                {{ t('siteIntel.seoAudit.sitemapSampled') }}:
                <span class="text-muted-foreground">{{
                    result.sitemapAudit.sampled
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.sitemapNon200') }}:
                <span class="text-muted-foreground">{{
                    result.sitemapAudit.non200.length
                }}</span>
            </p>
        </SeoAuditDetailCard>
    </div>

    <div class="grid gap-2 xl:grid-cols-3">
        <SeoAuditDetailCard
            :title="t('siteIntel.seoAudit.contentQualityTitle')"
            :hint="t('siteIntel.seoAudit.help.contentQuality')"
        >
            <p>
                {{ t('siteIntel.seoAudit.wordCount') }}:
                <span class="text-muted-foreground">{{
                    result.quality.content.wordCount
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.textHtmlRatio') }}:
                <span class="text-muted-foreground"
                    >{{ result.quality.content.textToHtmlRatio }}%</span
                >
            </p>
            <p>
                {{ t('siteIntel.seoAudit.thinContent') }}:
                <span class="text-muted-foreground">{{
                    result.quality.content.thinContent
                        ? t('siteIntel.common.yes')
                        : t('siteIntel.common.no')
                }}</span>
            </p>
        </SeoAuditDetailCard>
        <SeoAuditDetailCard
            :title="t('siteIntel.seoAudit.accessibilityTitle')"
            :hint="t('siteIntel.seoAudit.help.accessibility')"
        >
            <p>
                {{ t('siteIntel.seoAudit.imagesWithoutAlt') }}:
                <span class="text-muted-foreground"
                    >{{ result.quality.accessibility.imagesWithoutAlt }}/{{
                        result.quality.accessibility.imagesTotal
                    }}</span
                >
            </p>
            <p>
                {{ t('siteIntel.seoAudit.headingOrderBroken') }}:
                <span class="text-muted-foreground">{{
                    result.quality.accessibility.headingOrderBroken
                        ? t('siteIntel.common.yes')
                        : t('siteIntel.common.no')
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.htmlIssues') }}:
                <span class="text-muted-foreground">{{
                    result.quality.htmlValidation.issueCount
                }}</span>
            </p>
        </SeoAuditDetailCard>
        <SeoAuditDetailCard
            :title="t('siteIntel.seoAudit.linkGraphTitle')"
            :hint="t('siteIntel.seoAudit.help.linkGraph')"
        >
            <p>
                {{ t('siteIntel.seoAudit.internalOutlinks') }}:
                <span class="text-muted-foreground">{{
                    result.quality.linkGraph.internalOutlinks
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.externalOutlinks') }}:
                <span class="text-muted-foreground">{{
                    result.quality.linkGraph.externalOutlinks
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.orphanRisk') }}:
                <span class="text-muted-foreground">{{
                    result.quality.linkGraph.orphanRisk
                        ? t('siteIntel.common.yes')
                        : t('siteIntel.common.no')
                }}</span>
            </p>
        </SeoAuditDetailCard>
    </div>

    <div class="grid gap-2 xl:grid-cols-2">
        <SeoAuditDetailCard
            :title="t('siteIntel.seoAudit.internationalTitle')"
            :hint="t('siteIntel.seoAudit.help.international')"
        >
            <p>
                {{ t('siteIntel.seoAudit.hreflangPages') }}:
                <span class="text-muted-foreground">{{
                    result.international.pagesWithHreflang
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.hreflangMissingXDefault') }}:
                <span class="text-muted-foreground">{{
                    result.international.missingXDefault.length
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.hreflangMissingReciprocal') }}:
                <span class="text-muted-foreground">{{
                    result.international.missingReciprocal.length
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.hreflangClusters') }}:
                <span class="text-muted-foreground">{{
                    result.international.clusters.length
                }}</span>
            </p>
        </SeoAuditDetailCard>
        <SeoAuditDetailCard
            :title="t('siteIntel.seoAudit.crawlBudgetTitle')"
            :hint="t('siteIntel.seoAudit.help.crawlBudget')"
        >
            <p>
                {{ t('siteIntel.seoAudit.crawlBudgetSource') }}:
                <span class="text-muted-foreground">{{
                    result.crawlBudget.source
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.crawlBudgetBotHits') }}:
                <span class="text-muted-foreground">{{
                    result.crawlBudget.botHits
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.statusBuckets') }}:
                <span class="text-muted-foreground"
                    >{{ result.crawlBudget.statusBuckets['2xx'] }}/{{
                        result.crawlBudget.statusBuckets['3xx']
                    }}/{{ result.crawlBudget.statusBuckets['4xx'] }}/{{
                        result.crawlBudget.statusBuckets['5xx']
                    }}</span
                >
            </p>
        </SeoAuditDetailCard>
    </div>
</template>
