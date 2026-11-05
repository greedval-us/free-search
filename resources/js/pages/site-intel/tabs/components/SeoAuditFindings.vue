<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import type { SiteIntelSeoAuditResult } from '../../types';
import SeoAuditDetailCard from './SeoAuditDetailCard.vue';

const props = defineProps<{ result: SiteIntelSeoAuditResult }>();
const { t } = useI18n();

const signalLabel = (signal: string) => {
    const key = `siteIntel.seoAudit.signal.${signal}`;
    const translated = t(key);

    return translated === key ? signal : translated;
};

const recommendationBars = computed(() => {
    const items = props.result.recommendations ?? [];
    const total = items.length || 1;

    return ['critical', 'medium', 'low'].map((key) => {
        const value = items.filter((item) => item.priority === key).length;

        return { key, value, percent: Math.round((value / total) * 100) };
    });
});

const crawlStatusBars = computed(() => {
    const buckets = props.result.crawlBudget.statusBuckets;
    const values = (['2xx', '3xx', '4xx', '5xx'] as const).map((key) => ({
        key,
        value: buckets ? buckets[key] : 0,
    }));
    const max = Math.max(1, ...values.map((item) => item.value));

    return values.map((item) => ({
        ...item,
        percent: Math.round((item.value / max) * 100),
    }));
});
</script>

<template>
    <div class="grid gap-2 xl:grid-cols-2">
        <div class="rounded-lg border border-border/70 bg-background/60 p-3">
            <p class="mb-2 font-semibold">
                {{ t('siteIntel.seoAudit.recommendationsTitle') }}: severity
            </p>
            <div class="space-y-2">
                <div v-for="item in recommendationBars" :key="item.key">
                    <div
                        class="mb-1 flex items-center justify-between text-[11px] text-muted-foreground"
                    >
                        <span>{{
                            t(`siteIntel.seoAudit.priority.${item.key}`)
                        }}</span>
                        <span>{{ item.value }}</span>
                    </div>
                    <div class="h-2 rounded bg-muted/50">
                        <div
                            class="h-2 rounded bg-sky-400/80"
                            :style="{ width: `${item.percent}%` }"
                        />
                    </div>
                </div>
            </div>
        </div>
        <div class="rounded-lg border border-border/70 bg-background/60 p-3">
            <p class="mb-2 font-semibold">
                {{ t('siteIntel.seoAudit.crawlBudgetTitle') }}:
                {{ t('siteIntel.seoAudit.statusBuckets') }}
            </p>
            <div class="space-y-2">
                <div v-for="item in crawlStatusBars" :key="item.key">
                    <div
                        class="mb-1 flex items-center justify-between text-[11px] text-muted-foreground"
                    >
                        <span>{{ item.key }}</span>
                        <span>{{ item.value }}</span>
                    </div>
                    <div class="h-2 rounded bg-muted/50">
                        <div
                            class="h-2 rounded bg-emerald-400/80"
                            :style="{ width: `${item.percent}%` }"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-2 xl:grid-cols-3">
        <SeoAuditDetailCard
            :title="t('siteIntel.seoAudit.crawlFilesTitle')"
            :hint="t('siteIntel.seoAudit.help.crawlFiles')"
        >
            <p>
                {{ t('siteIntel.seoAudit.robotsTxt') }}:
                <span class="text-muted-foreground"
                    >{{
                        result.robots.available
                            ? t('siteIntel.common.available')
                            : t('siteIntel.common.missing')
                    }}
                    ({{ result.robots.status }})</span
                >
            </p>
            <p>
                {{ t('siteIntel.seoAudit.sitemapXml') }}:
                <span class="text-muted-foreground"
                    >{{
                        result.sitemap.available
                            ? t('siteIntel.common.available')
                            : t('siteIntel.common.missing')
                    }}
                    ({{ result.sitemap.status }})</span
                >
            </p>
            <p>
                {{ t('siteIntel.seoAudit.sitemapEntries') }}:
                <span class="text-muted-foreground">{{
                    result.sitemap.entries
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.robotsWildcard') }}:
                <span class="text-muted-foreground">{{
                    result.robots.rules.hasWildcardGroup
                        ? t('siteIntel.common.yes')
                        : t('siteIntel.common.no')
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.robotsCrawlDelay') }}:
                <span class="text-muted-foreground">{{
                    result.robots.rules.hasCrawlDelay
                        ? t('siteIntel.common.yes')
                        : t('siteIntel.common.no')
                }}</span>
            </p>
        </SeoAuditDetailCard>
        <SeoAuditDetailCard :title="t('siteIntel.seoAudit.securityTitle')">
            <p>
                HTTPS:
                <span class="text-muted-foreground">{{
                    result.security.https
                        ? t('siteIntel.common.yes')
                        : t('siteIntel.common.no')
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.mixedContent') }}:
                <span class="text-muted-foreground">{{
                    result.security.mixedContent
                        ? t('siteIntel.common.yes')
                        : t('siteIntel.common.no')
                }}</span>
            </p>
            <p>
                CSP:
                <span class="text-muted-foreground">{{
                    result.security.hasCsp
                        ? t('siteIntel.common.yes')
                        : t('siteIntel.common.no')
                }}</span>
            </p>
            <p>
                HSTS:
                <span class="text-muted-foreground">{{
                    result.security.hasHsts
                        ? t('siteIntel.common.yes')
                        : t('siteIntel.common.no')
                }}</span>
            </p>
        </SeoAuditDetailCard>
        <SeoAuditDetailCard
            :title="t('siteIntel.seoAudit.technicalFlags')"
            :hint="t('siteIntel.seoAudit.help.technicalFlags')"
        >
            <p>
                {{ t('siteIntel.seoAudit.mobileFriendly') }}:
                <span class="text-muted-foreground">{{
                    result.mobileFriendly.isResponsive
                        ? t('siteIntel.common.yes')
                        : t('siteIntel.common.no')
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.pagination') }}:
                <span class="text-muted-foreground">{{
                    result.pagination.isPaginated
                        ? t('siteIntel.common.yes')
                        : t('siteIntel.common.no')
                }}</span>
            </p>
            <p>
                {{ t('siteIntel.seoAudit.soft404') }}:
                <span class="text-muted-foreground">{{
                    result.soft404.detected
                        ? t('siteIntel.common.yes')
                        : t('siteIntel.common.no')
                }}</span>
            </p>
        </SeoAuditDetailCard>
    </div>

    <SeoAuditDetailCard
        :title="t('siteIntel.seoAudit.recommendationsTitle')"
        :hint="t('siteIntel.seoAudit.help.recommendations')"
    >
        <p
            v-if="result.score.signals.length > 0"
            class="mb-2 text-muted-foreground"
        >
            {{ t('siteIntel.seoAudit.detectedSignals') }}:
            <span v-for="(signal, idx) in result.score.signals" :key="signal">
                {{ idx > 0 ? ', ' : '' }}{{ signalLabel(signal) }}
            </span>
        </p>
        <ul class="list-disc space-y-1 pl-4 text-muted-foreground">
            <li
                v-for="(item, index) in result.recommendations"
                :key="`${item.priority}-${index}`"
            >
                [{{ t(`siteIntel.seoAudit.priority.${item.priority}`) }}]
                {{ t(`siteIntel.seoAudit.recommendation.${item.key}`) }}
            </li>
        </ul>
    </SeoAuditDetailCard>
</template>
