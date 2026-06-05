<script setup lang="ts">
import { BarChart3, Download, FileText } from 'lucide-vue-next';
import { onMounted } from 'vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import IntelSearchForm from '@/components/ui/IntelSearchForm.vue';
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue';
import InfoCard from '@/components/ui/InfoCard.vue';
import MetricCard from '@/components/ui/MetricCard.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import { useI18n } from '@/composables/useI18n';
import {
    getRepeatQueryParams,
    isRepeatAutorunEnabled,
    readRepeatQueryParam,
} from '@/composables/useRepeatQuery';
import { useSiteIntelAnalytics } from '../composables/useSiteIntelAnalytics';
import { useSiteIntelAnalyticsViewModel } from '../composables/useSiteIntelAnalyticsViewModel';
import SiteIntelMetricBars from './components/SiteIntelMetricBars.vue';

const { t } = useI18n();
const {
    form,
    loading,
    error,
    result,
    canAnalyze,
    canUseReportActions,
    analyze,
    openReport,
    downloadReport,
} = useSiteIntelAnalytics(t);
const {
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
    <IntelSearchPanel>
        <PageHeader
            :icon="BarChart3"
            :title="t('siteIntel.analytics.title')"
            :description="t('siteIntel.analytics.description')"
            :help-label="t('siteIntel.help.label')"
            :help-text="t('siteIntel.analytics.help.overview')"
        />

        <IntelSearchForm
            v-model="form.target"
            :label="t('siteIntel.analytics.target')"
            :placeholder="t('siteIntel.analytics.placeholder')"
            :button-text="t('siteIntel.analytics.analyze')"
            :loading-text="t('siteIntel.analytics.analyzing')"
            :loading="loading"
            :disabled="!canAnalyze"
            :error="error"
            @submit="analyze"
        >
            <template #actions>
                <button
                    type="button"
                    :disabled="!canUseReportActions"
                    class="intel-button-primary"
                    @click="openReport"
                >
                    <FileText class="h-4 w-4" />
                    {{ t('siteIntel.analytics.report') }}
                </button>

                <button
                    type="button"
                    :disabled="!canUseReportActions"
                    class="intel-button-secondary"
                    @click="downloadReport"
                >
                    <Download class="h-4 w-4" />
                    {{ t('siteIntel.analytics.downloadReport') }}
                </button>
            </template>
        </IntelSearchForm>
    </IntelSearchPanel>

    <IntelResultPanel>
        <EmptyState v-if="!result" :text="t('siteIntel.analytics.empty')" />

        <div
            v-else
            class="intel-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1"
        >
            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                <MetricCard
                    :title="t('siteIntel.analytics.overallScore')"
                    :value="result.overview.score.value"
                    :tone="
                        result.overview.score.level === 'high'
                            ? 'positive'
                            : result.overview.score.level === 'medium'
                              ? 'warning'
                              : 'danger'
                    "
                />
                <MetricCard
                    :title="t('siteIntel.analytics.healthScore')"
                    :value="result.overview.healthScore"
                />
                <MetricCard
                    :title="t('siteIntel.analytics.domainRiskScore')"
                    :value="result.overview.domainRiskScore"
                />
                <MetricCard
                    :title="t('siteIntel.common.checkedAt')"
                    :value="formatDateTime(result.checkedAt)"
                />
            </div>

            <InfoCard :title="t('siteIntel.analytics.targetSummary')">
                <p class="font-semibold">
                    {{ t('siteIntel.analytics.targetSummary') }}
                </p>
                <p class="mt-1">
                    {{ t('siteIntel.analytics.url') }}:
                    <span class="break-all text-muted-foreground">{{
                        result.target.url
                    }}</span>
                </p>
                <p class="mt-1">
                    {{ t('siteIntel.analytics.domain') }}:
                    <span class="text-muted-foreground">{{
                        result.target.domain
                    }}</span>
                </p>
            </InfoCard>

            <div class="grid gap-2 xl:grid-cols-3">
                <MetricCard
                    :title="t('siteIntel.analytics.headersCoverage')"
                    :value="`${result.overview.headersCoverage.present}/${result.overview.headersCoverage.total} (${result.overview.headersCoverage.percent}%)`"
                />
                <MetricCard
                    :title="t('siteIntel.analytics.redirects')"
                    :value="result.overview.redirects"
                />
                <MetricCard
                    :title="t('siteIntel.analytics.daysToDomainExpiry')"
                    :value="result.overview.daysToDomainExpiry ?? '-'"
                />
            </div>

            <InfoCard :title="t('siteIntel.analytics.dnsStats')">
                <p class="mb-2 font-semibold">
                    {{ t('siteIntel.analytics.dnsStats') }}
                </p>
                <p>
                    A: {{ result.overview.dnsStats.a }}, AAAA:
                    {{ result.overview.dnsStats.aaaa }}, NS:
                    {{ result.overview.dnsStats.ns }}, MX:
                    {{ result.overview.dnsStats.mx }}
                </p>
            </InfoCard>

            <div class="grid gap-2 xl:grid-cols-2">
                <SiteIntelMetricBars
                    :title="t('siteIntel.analytics.scoreBreakdownTitle')"
                    :items="scoreBars"
                    :hint="t('siteIntel.analytics.help.scoreBreakdown')"
                    :help-label="t('siteIntel.help.label')"
                />
                <SiteIntelMetricBars
                    :title="t('siteIntel.analytics.signalBalanceTitle')"
                    :items="signalBars"
                    :hint="t('siteIntel.analytics.help.signalBalance')"
                    :help-label="t('siteIntel.help.label')"
                />
            </div>

            <div class="grid gap-2 xl:grid-cols-2">
                <SiteIntelMetricBars
                    :title="t('siteIntel.analytics.dnsProfileTitle')"
                    :items="dnsBars"
                    :hint="t('siteIntel.analytics.help.dnsProfile')"
                    :help-label="t('siteIntel.help.label')"
                />
                <SiteIntelMetricBars
                    :title="t('siteIntel.analytics.httpLatencyTitle')"
                    :items="httpLatencyBars"
                    value-suffix=" ms"
                    :hint="t('siteIntel.analytics.help.httpLatency')"
                    :help-label="t('siteIntel.help.label')"
                />
            </div>

            <InfoCard
                :title="t('siteIntel.analytics.additionalSnapshotMetrics')"
                :help-label="t('siteIntel.help.label')"
                :help-text="t('siteIntel.analytics.help.snapshotMetrics')"
            >
                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    <p>
                        {{ t('siteIntel.analytics.snapshotHttpFinalStatus') }}:
                        <span class="text-muted-foreground">{{
                            result.siteHealth.http.finalStatus || '-'
                        }}</span>
                    </p>
                    <p>
                        {{
                            t('siteIntel.analytics.snapshotTotalResponseTime')
                        }}:
                        <span class="text-muted-foreground"
                            >{{ totalResponseTimeMs }} ms</span
                        >
                    </p>
                    <p>
                        {{ t('siteIntel.analytics.snapshotSslDaysRemaining') }}:
                        <span class="text-muted-foreground">{{
                            result.siteHealth.ssl.daysRemaining ?? '-'
                        }}</span>
                    </p>
                    <p>
                        {{ t('siteIntel.analytics.snapshotWhois') }}:
                        <span class="text-muted-foreground">{{
                            result.domainLite.whois.available
                                ? t('siteIntel.common.available')
                                : t('siteIntel.common.unavailable')
                        }}</span>
                    </p>
                    <p>
                        {{ t('siteIntel.analytics.snapshotDomainAge') }}:
                        <span class="text-muted-foreground"
                            >{{ domainAgeDays ?? '-' }}
                            {{ t('siteIntel.analytics.snapshotDays') }}</span
                        >
                    </p>
                    <p>
                        {{ t('siteIntel.analytics.snapshotEmailSecurity') }}:
                        <span class="text-muted-foreground"
                            >{{ emailSecurityScore }}/2
                            {{
                                t(
                                    'siteIntel.analytics.snapshotEmailSecurityDetails'
                                )
                            }}</span
                        >
                    </p>
                    <p>
                        {{ t('siteIntel.analytics.snapshotWhoisRegistrar') }}:
                        <span class="text-muted-foreground">{{
                            result.domainLite.whois.registrar || '-'
                        }}</span>
                    </p>
                    <p>
                        {{ t('siteIntel.analytics.snapshotWhoisCountry') }}:
                        <span class="text-muted-foreground">{{
                            result.domainLite.whois.country || '-'
                        }}</span>
                    </p>
                </div>
            </InfoCard>

            <div class="grid gap-2 xl:grid-cols-2">
                <InfoCard
                    :title="t('siteIntel.analytics.riskSignals')"
                    :help-label="t('siteIntel.help.label')"
                    :help-text="t('siteIntel.analytics.help.riskSignals')"
                >
                    <p
                        v-if="result.overview.signals.risks.length === 0"
                        class="intel-status-positive"
                    >
                        {{ t('siteIntel.analytics.noRiskSignals') }}
                    </p>
                    <ul
                        v-else
                        class="list-disc space-y-1 pl-4 text-muted-foreground"
                    >
                        <li
                            v-for="signal in result.overview.signals.risks"
                            :key="signal"
                        >
                            {{ signalLabel(signal, 'risk') }}
                        </li>
                    </ul>
                </InfoCard>

                <InfoCard
                    :title="t('siteIntel.analytics.strengthSignals')"
                    :help-label="t('siteIntel.help.label')"
                    :help-text="t('siteIntel.analytics.help.strengthSignals')"
                >
                    <p
                        v-if="result.overview.signals.strengths.length === 0"
                        class="text-muted-foreground"
                    >
                        {{ t('siteIntel.analytics.noStrengthSignals') }}
                    </p>
                    <ul
                        v-else
                        class="list-disc space-y-1 pl-4 text-muted-foreground"
                    >
                        <li
                            v-for="signal in result.overview.signals.strengths"
                            :key="signal"
                        >
                            {{ signalLabel(signal, 'strength') }}
                        </li>
                    </ul>
                </InfoCard>
            </div>

            <InfoCard
                :title="t('siteIntel.analytics.recommendations')"
                :help-label="t('siteIntel.help.label')"
                :help-text="t('siteIntel.analytics.help.recommendations')"
            >
                <ul class="space-y-2 text-muted-foreground">
                    <li
                        v-for="item in recommendationsWithImpact"
                        :key="item.key"
                        class="intel-list-card"
                    >
                        <div
                            class="mb-1 flex items-center justify-between gap-2"
                        >
                            <span class="text-[11px]">{{ item.label }}</span>
                            <span class="text-[11px] text-emerald-300">{{
                                item.impact > 0
                                    ? `~ +${item.impact} ${t('siteIntel.analytics.scoreUnit')}`
                                    : t(
                                          'siteIntel.analytics.recommendationMaintain'
                                      )
                            }}</span>
                        </div>
                        <div
                            v-if="item.impact > 0"
                            class="h-2 rounded bg-muted/50"
                        >
                            <div
                                class="h-2 rounded bg-emerald-400/80 transition-all duration-300"
                                :style="{
                                    width: `${clampPercent(item.impact * 5)}%`,
                                }"
                            />
                        </div>
                    </li>
                </ul>
            </InfoCard>
        </div>
    </IntelResultPanel>
</template>
