<script setup lang="ts">
import { LoaderCircle, SearchCheck } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useSeoAudit } from '../composables/useSeoAudit';

const { t } = useI18n();
const { form, loading, error, result, canAnalyze, canUseReportActions, analyze, openReport, downloadReport } = useSeoAudit(t);

const scoreClass = computed(() => {
    const level = result.value?.score.level;
    if (level === 'high') {
        return 'text-emerald-300';
    }
    if (level === 'medium') {
        return 'text-amber-300';
    }

    return 'text-rose-300';
});

const signalLabel = (signal: string) => {
    const key = `siteIntel.seoAudit.signal.${signal}`;
    const translated = t(key);

    return translated === key ? signal : translated;
};
</script>

<template>
    <section class="sticky top-0 z-10 shrink-0 rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
        <div class="space-y-1">
            <div class="flex items-center gap-2 text-sm font-semibold">
                <SearchCheck class="h-4 w-4 text-cyan-400" />
                <span>{{ t('siteIntel.seoAudit.title') }}</span>
                <span class="group relative inline-flex">
                    <span
                        class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                        :aria-label="t('siteIntel.help.label')"
                    >
                        ?
                    </span>
                    <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                        {{ t('siteIntel.seoAudit.help.overview') }}
                    </span>
                </span>
            </div>
            <p class="text-xs text-muted-foreground">{{ t('siteIntel.seoAudit.description') }}</p>
        </div>

        <div class="mt-3 flex flex-wrap items-end gap-3">
            <label class="block min-w-0 flex-1">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('siteIntel.seoAudit.target') }}</span>
                <input
                    v-model="form.target"
                    type="text"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    :placeholder="t('siteIntel.seoAudit.placeholder')"
                    @keydown.enter.prevent="analyze"
                />
            </label>
            <label class="block w-44">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('siteIntel.seoAudit.crawlLimit') }}</span>
                <select v-model.number="form.crawlLimit" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                    <option :value="5">5</option>
                    <option :value="8">8</option>
                    <option :value="12">12</option>
                    <option :value="20">20</option>
                </select>
            </label>

            <button
                :disabled="loading || !canAnalyze"
                class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                @click="analyze"
            >
                <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                <span>{{ loading ? t('siteIntel.seoAudit.analyzing') : t('siteIntel.seoAudit.analyze') }}</span>
            </button>
            <button
                type="button"
                :disabled="!canUseReportActions"
                class="inline-flex h-10 cursor-pointer items-center rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                @click="openReport"
            >
                {{ t('siteIntel.seoAudit.report') }}
            </button>
            <button
                type="button"
                :disabled="!canUseReportActions"
                class="inline-flex h-10 cursor-pointer items-center rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                @click="downloadReport"
            >
                {{ t('siteIntel.seoAudit.downloadReport') }}
            </button>
        </div>

        <p v-if="error" class="mt-3 text-sm text-destructive">{{ error }}</p>
    </section>

    <section class="flex min-h-0 flex-1 flex-col rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
        <div v-if="!result" class="rounded-md border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
            {{ t('siteIntel.seoAudit.empty') }}
        </div>

        <div v-else class="telegram-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1 text-xs">
            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="text-muted-foreground">{{ t('siteIntel.seoAudit.score') }}</p>
                    <p class="mt-1 text-xl font-semibold" :class="scoreClass">{{ result.score.value }}</p>
                </div>
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="text-muted-foreground">{{ t('siteIntel.seoAudit.httpStatus') }}</p>
                    <p class="mt-1 text-xl font-semibold">{{ result.status.httpCode || '-' }}</p>
                </div>
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="text-muted-foreground">{{ t('siteIntel.seoAudit.ttfb') }}</p>
                    <p class="mt-1 text-xl font-semibold">{{ result.performance.ttfbMsApprox }} ms</p>
                </div>
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="text-muted-foreground">{{ t('siteIntel.common.checkedAt') }}</p>
                    <p class="mt-1 text-sm font-semibold">{{ new Date(result.checkedAt).toLocaleString() }}</p>
                </div>
            </div>

            <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                <p class="font-semibold">{{ t('siteIntel.seoAudit.targetSummary') }}</p>
                <p class="mt-1">{{ t('siteIntel.seoAudit.finalUrl') }}: <span class="break-all text-muted-foreground">{{ result.target.finalUrl }}</span></p>
                <p class="mt-1">{{ t('siteIntel.seoAudit.host') }}: <span class="text-muted-foreground">{{ result.target.host }}</span></p>
            </div>

            <div class="grid gap-2 xl:grid-cols-2">
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <div class="mb-2 flex items-center gap-2">
                        <p class="font-semibold">{{ t('siteIntel.seoAudit.metaTitle') }}</p>
                        <span class="group relative inline-flex">
                            <span class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground" :aria-label="t('siteIntel.help.label')">?</span>
                            <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">{{ t('siteIntel.seoAudit.help.meta') }}</span>
                        </span>
                    </div>
                    <p>{{ t('siteIntel.seoAudit.titleLength') }}: <span class="text-muted-foreground">{{ result.meta.titleLength }}</span></p>
                    <p>{{ t('siteIntel.seoAudit.descriptionLength') }}: <span class="text-muted-foreground">{{ result.meta.descriptionLength }}</span></p>
                    <p>{{ t('siteIntel.seoAudit.canonical') }}: <span class="break-all text-muted-foreground">{{ result.meta.canonical || '-' }}</span></p>
                    <p>{{ t('siteIntel.seoAudit.metaRobots') }}: <span class="text-muted-foreground">{{ result.meta.robots || '-' }}</span></p>
                </div>
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <div class="mb-2 flex items-center gap-2">
                        <p class="font-semibold">{{ t('siteIntel.seoAudit.indexabilityTitle') }}</p>
                        <span class="group relative inline-flex">
                            <span class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground" :aria-label="t('siteIntel.help.label')">?</span>
                            <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">{{ t('siteIntel.seoAudit.help.indexability') }}</span>
                        </span>
                    </div>
                    <p>{{ t('siteIntel.seoAudit.indexable') }}: <span class="text-muted-foreground">{{ result.indexability.indexable ? t('siteIntel.common.yes') : t('siteIntel.common.no') }}</span></p>
                    <p>{{ t('siteIntel.seoAudit.xRobotsTag') }}: <span class="text-muted-foreground">{{ result.indexability.xRobotsTag || '-' }}</span></p>
                    <p>{{ t('siteIntel.seoAudit.reason') }}: <span class="text-muted-foreground">{{ result.indexability.reason }}</span></p>
                </div>
            </div>

            <div class="grid gap-2 xl:grid-cols-3">
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="mb-2 font-semibold">{{ t('siteIntel.seoAudit.headingsTitle') }}</p>
                    <p>H1: <span class="text-muted-foreground">{{ result.headings.h1 }}</span></p>
                    <p>H2: <span class="text-muted-foreground">{{ result.headings.h2 }}</span></p>
                    <p>H3: <span class="text-muted-foreground">{{ result.headings.h3 }}</span></p>
                </div>
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="mb-2 font-semibold">{{ t('siteIntel.seoAudit.linksTitle') }}</p>
                    <p>{{ t('siteIntel.seoAudit.internalLinks') }}: <span class="text-muted-foreground">{{ result.links.internal }}</span></p>
                    <p>{{ t('siteIntel.seoAudit.externalLinks') }}: <span class="text-muted-foreground">{{ result.links.external }}</span></p>
                    <p>{{ t('siteIntel.seoAudit.nofollowLinks') }}: <span class="text-muted-foreground">{{ result.links.nofollow }}</span></p>
                </div>
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <div class="mb-2 flex items-center gap-2">
                        <p class="font-semibold">{{ t('siteIntel.seoAudit.performanceTitle') }}</p>
                        <span class="group relative inline-flex">
                            <span class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground" :aria-label="t('siteIntel.help.label')">?</span>
                            <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">{{ t('siteIntel.seoAudit.help.performance') }}</span>
                        </span>
                    </div>
                    <p>{{ t('siteIntel.seoAudit.pageSize') }}: <span class="text-muted-foreground">{{ result.performance.pageSizeKb }} KB</span></p>
                    <p>{{ t('siteIntel.seoAudit.resourcesCount') }}: <span class="text-muted-foreground">{{ result.performance.resourceCount }}</span></p>
                </div>
            </div>

            <div class="grid gap-2 xl:grid-cols-2">
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <div class="mb-2 flex items-center gap-2">
                        <p class="font-semibold">{{ t('siteIntel.seoAudit.crawlFilesTitle') }}</p>
                        <span class="group relative inline-flex">
                            <span class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground" :aria-label="t('siteIntel.help.label')">?</span>
                            <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">{{ t('siteIntel.seoAudit.help.crawlFiles') }}</span>
                        </span>
                    </div>
                    <p>{{ t('siteIntel.seoAudit.robotsTxt') }}: <span class="text-muted-foreground">{{ result.robots.available ? t('siteIntel.common.available') : t('siteIntel.common.missing') }} ({{ result.robots.status }})</span></p>
                    <p>{{ t('siteIntel.seoAudit.sitemapXml') }}: <span class="text-muted-foreground">{{ result.sitemap.available ? t('siteIntel.common.available') : t('siteIntel.common.missing') }} ({{ result.sitemap.status }})</span></p>
                    <p>{{ t('siteIntel.seoAudit.sitemapEntries') }}: <span class="text-muted-foreground">{{ result.sitemap.entries }}</span></p>
                </div>
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="mb-2 font-semibold">{{ t('siteIntel.seoAudit.securityTitle') }}</p>
                    <p>HTTPS: <span class="text-muted-foreground">{{ result.security.https ? t('siteIntel.common.yes') : t('siteIntel.common.no') }}</span></p>
                    <p>{{ t('siteIntel.seoAudit.mixedContent') }}: <span class="text-muted-foreground">{{ result.security.mixedContent ? t('siteIntel.common.yes') : t('siteIntel.common.no') }}</span></p>
                    <p>CSP: <span class="text-muted-foreground">{{ result.security.hasCsp ? t('siteIntel.common.yes') : t('siteIntel.common.no') }}</span></p>
                    <p>HSTS: <span class="text-muted-foreground">{{ result.security.hasHsts ? t('siteIntel.common.yes') : t('siteIntel.common.no') }}</span></p>
                </div>
            </div>

            <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                <div class="mb-2 flex items-center gap-2">
                    <p class="font-semibold">{{ t('siteIntel.seoAudit.recommendationsTitle') }}</p>
                    <span class="group relative inline-flex">
                        <span class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground" :aria-label="t('siteIntel.help.label')">?</span>
                        <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">{{ t('siteIntel.seoAudit.help.recommendations') }}</span>
                    </span>
                </div>
                <p v-if="result.score.signals.length > 0" class="mb-2 text-muted-foreground">
                    {{ t('siteIntel.seoAudit.detectedSignals') }}:
                    <span v-for="(signal, idx) in result.score.signals" :key="signal">
                        {{ idx > 0 ? ', ' : '' }}{{ signalLabel(signal) }}
                    </span>
                </p>
                <ul class="list-disc space-y-1 pl-4 text-muted-foreground">
                    <li v-for="(item, index) in result.recommendations" :key="`${item.priority}-${index}`">
                        [{{ t(`siteIntel.seoAudit.priority.${item.priority}`) }}] {{ t(`siteIntel.seoAudit.recommendation.${item.key}`) }}
                    </li>
                </ul>
            </div>

            <div class="grid gap-2 xl:grid-cols-3">
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="mb-2 font-semibold">{{ t('siteIntel.seoAudit.crawlTitle') }}</p>
                    <p>{{ t('siteIntel.seoAudit.crawlScanned') }}: <span class="text-muted-foreground">{{ result.crawl.scanned }}/{{ result.crawl.limit }}</span></p>
                    <p>{{ t('siteIntel.seoAudit.crawlPagesWithHreflang') }}: <span class="text-muted-foreground">{{ result.crawl.hreflangAudit.pagesWithHreflang }}</span></p>
                </div>
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="mb-2 font-semibold">{{ t('siteIntel.seoAudit.canonicalAuditTitle') }}</p>
                    <p>{{ t('siteIntel.seoAudit.canonicalMissing') }}: <span class="text-muted-foreground">{{ result.crawl.canonicalAudit.missing.length }}</span></p>
                    <p>{{ t('siteIntel.seoAudit.canonicalCrossDomain') }}: <span class="text-muted-foreground">{{ result.crawl.canonicalAudit.crossDomain.length }}</span></p>
                    <p>{{ t('siteIntel.seoAudit.canonicalInvalid') }}: <span class="text-muted-foreground">{{ result.crawl.canonicalAudit.invalid.length }}</span></p>
                </div>
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="mb-2 font-semibold">{{ t('siteIntel.seoAudit.sitemapAuditTitle') }}</p>
                    <p>{{ t('siteIntel.seoAudit.sitemapSampled') }}: <span class="text-muted-foreground">{{ result.sitemapAudit.sampled }}</span></p>
                    <p>{{ t('siteIntel.seoAudit.sitemapNon200') }}: <span class="text-muted-foreground">{{ result.sitemapAudit.non200.length }}</span></p>
                </div>
            </div>
        </div>
    </section>
</template>
