<script setup lang="ts">
import { BarChart3, Download, FileText, LoaderCircle } from 'lucide-vue-next';
import { onMounted } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { getRepeatQueryParams, isRepeatAutorunEnabled, readRepeatQueryParam } from '@/composables/useRepeatQuery';
import { useSiteIntelAnalytics } from '../composables/useSiteIntelAnalytics';
import { useSiteIntelAnalyticsViewModel } from '../composables/useSiteIntelAnalyticsViewModel';
import SiteIntelMetricBars from './components/SiteIntelMetricBars.vue';

const { t } = useI18n();
const { form, loading, error, result, canAnalyze, canUseReportActions, analyze, openReport, downloadReport } = useSiteIntelAnalytics(t);
const {
    scoreBadgeClass,
    formatDateTime,
    signalLabel,
    clampPercent,
    scoreBars,
    dnsBars,
    httpLatencyBars,
    signalBars,
    recommendationsWithImpact,
    emailSecurityScore,
    domainAgeDays,
    totalResponseTimeMs,
} = useSiteIntelAnalyticsViewModel(result, t);

onMounted(() => {
    const params = getRepeatQueryParams();
    if (!params) {
        return;
    }

    const tab = readRepeatQueryParam(params, ['tab']);
    if (tab !== 'analytics') {
        return;
    }

    const target = readRepeatQueryParam(params, ['target']);
    if (target !== '') {
        form.target = target;
    }

    if (isRepeatAutorunEnabled(params) && canAnalyze.value) {
        void analyze();
    }
});
</script>

<template>
    <section class="sticky top-0 z-10 shrink-0 rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
        <div class="flex items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <BarChart3 class="h-4 w-4 text-cyan-400" />
                    <span>{{ t('siteIntel.analytics.title') }}</span>
                    <span class="group relative inline-flex">
                        <span
                            class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                            :aria-label="t('siteIntel.help.label')"
                        >
                            ?
                        </span>
                        <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                            {{ t('siteIntel.analytics.help.overview') }}
                        </span>
                    </span>
                </div>
                <p class="text-xs text-muted-foreground">{{ t('siteIntel.analytics.description') }}</p>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-end gap-3">
            <label class="block min-w-0 flex-1">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('siteIntel.analytics.target') }}</span>
                <input
                    v-model="form.target"
                    type="text"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    :placeholder="t('siteIntel.analytics.placeholder')"
                    @keydown.enter.prevent="analyze"
                />
            </label>

            <button
                :disabled="loading || !canAnalyze"
                class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                @click="analyze"
            >
                <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                <span>{{ loading ? t('siteIntel.analytics.analyzing') : t('siteIntel.analytics.analyze') }}</span>
            </button>

            <button
                type="button"
                :disabled="!canUseReportActions"
                class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                @click="openReport"
            >
                <FileText class="h-4 w-4" />
                {{ t('siteIntel.analytics.report') }}
            </button>

            <button
                type="button"
                :disabled="!canUseReportActions"
                class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                @click="downloadReport"
            >
                <Download class="h-4 w-4" />
                {{ t('siteIntel.analytics.downloadReport') }}
            </button>
        </div>

        <p v-if="error" class="mt-3 text-sm text-destructive">{{ error }}</p>
    </section>

    <section class="flex min-h-0 flex-1 flex-col rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
        <div v-if="!result" class="rounded-md border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
            {{ t('siteIntel.analytics.empty') }}
        </div>

        <div v-else class="telegram-scroll min-h-0 flex-1 overflow-y-auto space-y-3 pr-1">
            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg border p-3" :class="scoreBadgeClass">
                    <p class="text-xs">{{ t('siteIntel.analytics.overallScore') }}</p>
                    <p class="mt-1 text-xl font-semibold">{{ result.overview.score.value }}</p>
                </div>
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="text-xs text-muted-foreground">{{ t('siteIntel.analytics.healthScore') }}</p>
                    <p class="mt-1 text-xl font-semibold">{{ result.overview.healthScore }}</p>
                </div>
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="text-xs text-muted-foreground">{{ t('siteIntel.analytics.domainRiskScore') }}</p>
                    <p class="mt-1 text-xl font-semibold">{{ result.overview.domainRiskScore }}</p>
                </div>
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="text-xs text-muted-foreground">{{ t('siteIntel.common.checkedAt') }}</p>
                    <p class="mt-1 text-sm font-semibold">{{ formatDateTime(result.checkedAt) }}</p>
                </div>
            </div>

            <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                <p class="font-semibold">{{ t('siteIntel.analytics.targetSummary') }}</p>
                <p class="mt-1">{{ t('siteIntel.analytics.url') }}: <span class="break-all text-muted-foreground">{{ result.target.url }}</span></p>
                <p class="mt-1">{{ t('siteIntel.analytics.domain') }}: <span class="text-muted-foreground">{{ result.target.domain }}</span></p>
            </div>

            <div class="grid gap-2 xl:grid-cols-3">
                <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                    <p class="font-semibold">{{ t('siteIntel.analytics.headersCoverage') }}</p>
                    <p class="mt-1 text-muted-foreground">
                        {{ result.overview.headersCoverage.present }}/{{ result.overview.headersCoverage.total }}
                        ({{ result.overview.headersCoverage.percent }}%)
                    </p>
                </div>
                <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                    <p class="font-semibold">{{ t('siteIntel.analytics.redirects') }}</p>
                    <p class="mt-1 text-muted-foreground">{{ result.overview.redirects }}</p>
                </div>
                <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                    <p class="font-semibold">{{ t('siteIntel.analytics.daysToDomainExpiry') }}</p>
                    <p class="mt-1 text-muted-foreground">{{ result.overview.daysToDomainExpiry ?? '-' }}</p>
                </div>
            </div>

            <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                <p class="mb-2 font-semibold">{{ t('siteIntel.analytics.dnsStats') }}</p>
                <p>A: {{ result.overview.dnsStats.a }}, AAAA: {{ result.overview.dnsStats.aaaa }}, NS: {{ result.overview.dnsStats.ns }}, MX: {{ result.overview.dnsStats.mx }}</p>
            </div>

            <div class="grid gap-2 xl:grid-cols-2">
                <SiteIntelMetricBars
                    title="Score breakdown"
                    :items="scoreBars"
                    :hint="t('siteIntel.analytics.help.scoreBreakdown')"
                    :help-label="t('siteIntel.help.label')"
                />
                <SiteIntelMetricBars
                    title="Signal balance"
                    :items="signalBars"
                    :hint="t('siteIntel.analytics.help.signalBalance')"
                    :help-label="t('siteIntel.help.label')"
                />
            </div>

            <div class="grid gap-2 xl:grid-cols-2">
                <SiteIntelMetricBars
                    title="DNS profile"
                    :items="dnsBars"
                    :hint="t('siteIntel.analytics.help.dnsProfile')"
                    :help-label="t('siteIntel.help.label')"
                />
                <SiteIntelMetricBars
                    title="HTTP latency chain (ms)"
                    :items="httpLatencyBars"
                    value-suffix=" ms"
                    :hint="t('siteIntel.analytics.help.httpLatency')"
                    :help-label="t('siteIntel.help.label')"
                />
            </div>

            <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                <div class="mb-2 flex items-center gap-2">
                    <p class="font-semibold">Additional snapshot metrics</p>
                    <span class="group relative inline-flex">
                        <span
                            class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                            :aria-label="t('siteIntel.help.label')"
                        >
                            ?
                        </span>
                        <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                            {{ t('siteIntel.analytics.help.snapshotMetrics') }}
                        </span>
                    </span>
                </div>
                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    <p>HTTP final status: <span class="text-muted-foreground">{{ result.siteHealth.http.finalStatus || '-' }}</span></p>
                    <p>Total response time: <span class="text-muted-foreground">{{ totalResponseTimeMs }} ms</span></p>
                    <p>SSL days remaining: <span class="text-muted-foreground">{{ result.siteHealth.ssl.daysRemaining ?? '-' }}</span></p>
                    <p>WHOIS: <span class="text-muted-foreground">{{ result.domainLite.whois.available ? t('siteIntel.common.available') : t('siteIntel.common.unavailable') }}</span></p>
                    <p>Domain age: <span class="text-muted-foreground">{{ domainAgeDays ?? '-' }} days</span></p>
                    <p>Email security: <span class="text-muted-foreground">{{ emailSecurityScore }}/2 (SPF + DMARC)</span></p>
                    <p>WHOIS registrar: <span class="text-muted-foreground">{{ result.domainLite.whois.registrar || '-' }}</span></p>
                    <p>WHOIS country: <span class="text-muted-foreground">{{ result.domainLite.whois.country || '-' }}</span></p>
                </div>
            </div>

            <div class="grid gap-2 xl:grid-cols-2">
                <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                    <div class="mb-2 flex items-center gap-2">
                        <p class="font-semibold">{{ t('siteIntel.analytics.riskSignals') }}</p>
                        <span class="group relative inline-flex">
                            <span
                                class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                :aria-label="t('siteIntel.help.label')"
                            >
                                ?
                            </span>
                            <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                {{ t('siteIntel.analytics.help.riskSignals') }}
                            </span>
                        </span>
                    </div>
                    <p v-if="result.overview.signals.risks.length === 0" class="text-emerald-300">{{ t('siteIntel.analytics.noRiskSignals') }}</p>
                    <ul v-else class="list-disc space-y-1 pl-4 text-muted-foreground">
                        <li v-for="signal in result.overview.signals.risks" :key="signal">{{ signalLabel(signal, 'risk') }}</li>
                    </ul>
                </div>

                <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                    <div class="mb-2 flex items-center gap-2">
                        <p class="font-semibold">{{ t('siteIntel.analytics.strengthSignals') }}</p>
                        <span class="group relative inline-flex">
                            <span
                                class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                :aria-label="t('siteIntel.help.label')"
                            >
                                ?
                            </span>
                            <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                {{ t('siteIntel.analytics.help.strengthSignals') }}
                            </span>
                        </span>
                    </div>
                    <p v-if="result.overview.signals.strengths.length === 0" class="text-muted-foreground">{{ t('siteIntel.analytics.noStrengthSignals') }}</p>
                    <ul v-else class="list-disc space-y-1 pl-4 text-muted-foreground">
                        <li v-for="signal in result.overview.signals.strengths" :key="signal">{{ signalLabel(signal, 'strength') }}</li>
                    </ul>
                </div>
            </div>

            <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                <div class="mb-2 flex items-center gap-2">
                    <p class="font-semibold">{{ t('siteIntel.analytics.recommendations') }}</p>
                    <span class="group relative inline-flex">
                        <span
                            class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                            :aria-label="t('siteIntel.help.label')"
                        >
                            ?
                        </span>
                        <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                            {{ t('siteIntel.analytics.help.recommendations') }}
                        </span>
                    </span>
                </div>
                <ul class="space-y-2 text-muted-foreground">
                    <li v-for="item in recommendationsWithImpact" :key="item.key" class="rounded border border-border/60 p-2">
                        <div class="mb-1 flex items-center justify-between gap-2">
                            <span class="text-[11px]">{{ item.label }}</span>
                            <span class="text-[11px] text-emerald-300">{{ item.impact > 0 ? `~ +${item.impact} score` : 'maintain' }}</span>
                        </div>
                        <div v-if="item.impact > 0" class="h-2 rounded bg-muted/50">
                            <div class="h-2 rounded bg-emerald-400/80 transition-all duration-300" :style="{ width: `${clampPercent(item.impact * 5)}%` }" />
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</template>
