<script setup lang="ts">
import { Globe } from 'lucide-vue-next';
import { onMounted } from 'vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import InfoCard from '@/components/ui/InfoCard.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import IntelSearchForm from '@/components/ui/IntelSearchForm.vue';
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue';
import KeyValueList from '@/components/ui/KeyValueList.vue';
import MetricCard from '@/components/ui/MetricCard.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import { useI18n } from '@/composables/useI18n';
import {
    getRepeatQueryParams,
    isRepeatAutorunEnabled,
    readRepeatQueryParam,
} from '@/composables/useRepeatQuery';
import { useDomainLite } from '../composables/useDomainLite';

const { t } = useI18n();
const { form, loading, error, result, canLookup, lookup } = useDomainLite(t);

const formatDateTime = (value: string | null) => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleString();
};

const formatDays = (value: number | null) => {
    if (value === null || Number.isNaN(value)) {
        return '-';
    }

    return value.toString();
};

const signalLabel = (signal: string) => {
    const key = `siteIntel.domainLite.signal.${signal}`;
    const translated = t(key);

    return translated === key ? signal : translated;
};

onMounted(() => {
    const params = getRepeatQueryParams();

    if (!params) {
        return;
    }

    const tab = readRepeatQueryParam(params, ['tab']);

    if (tab !== '' && tab !== 'domainLite') {
        return;
    }

    const domain = readRepeatQueryParam(params, ['domain']);

    if (domain !== '') {
        form.domain = domain;
    }

    if (isRepeatAutorunEnabled(params) && canLookup.value) {
        void lookup();
    }
});
</script>

<template>
    <IntelSearchPanel>
        <PageHeader
            :icon="Globe"
            :title="t('siteIntel.domainLite.title')"
            :description="t('siteIntel.domainLite.description')"
            :help-label="t('siteIntel.help.label')"
            :help-text="t('siteIntel.domainLite.help.overview')"
        />

        <IntelSearchForm
            v-model="form.domain"
            :label="t('siteIntel.domainLite.domain')"
            :placeholder="t('siteIntel.domainLite.placeholder')"
            :button-text="t('siteIntel.domainLite.check')"
            :loading-text="t('siteIntel.domainLite.checking')"
            :loading="loading"
            :disabled="!canLookup"
            :error="error"
            @submit="lookup"
        />
    </IntelSearchPanel>

    <IntelResultPanel>
        <EmptyState v-if="!result" :text="t('siteIntel.domainLite.empty')" />

        <div
            v-else
            class="intel-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1"
        >
            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                <MetricCard
                    :title="t('siteIntel.common.checkedAt')"
                    :value="formatDateTime(result.checkedAt)"
                />
                <MetricCard
                    :title="t('siteIntel.domainLite.whois')"
                    :value="
                        result.whois.available
                            ? t('siteIntel.common.available')
                            : t('siteIntel.common.unavailable')
                    "
                />
                <MetricCard
                    :title="t('siteIntel.domainLite.mxCount')"
                    :value="result.dns.mx.length"
                />
                <MetricCard
                    :title="t('siteIntel.domainLite.riskScore')"
                    :value="result.risk.score"
                    :caption="t('siteIntel.domainLite.riskScoreHint')"
                    :tone="
                        result.risk.level === 'low'
                            ? 'positive'
                            : result.risk.level === 'medium'
                              ? 'warning'
                              : 'danger'
                    "
                />
            </div>

            <InfoCard
                :title="t('siteIntel.domainLite.dnsSummary')"
                :help-label="t('siteIntel.help.label')"
                :help-text="t('siteIntel.domainLite.help.dns')"
            >
                <KeyValueList
                    :items="[
                        {
                            label: t('siteIntel.domainLite.aRecords'),
                            value: result.dns.a.join(', ') || '-',
                        },
                        {
                            label: t('siteIntel.domainLite.aaaaRecords'),
                            value: result.dns.aaaa.join(', ') || '-',
                        },
                        {
                            label: t('siteIntel.domainLite.nsRecords'),
                            value: result.dns.ns.join(', ') || '-',
                        },
                        {
                            label: t('siteIntel.domainLite.mxRecords'),
                            value:
                                result.dns.mx
                                    .map(
                                        (record) =>
                                            `${record.host} (${record.priority})`
                                    )
                                    .join(', ') || '-',
                        },
                        {
                            label: t('siteIntel.domainLite.spf'),
                            value: result.dns.emailSecurity.hasSpf
                                ? t('siteIntel.common.yes')
                                : t('siteIntel.common.no'),
                        },
                        {
                            label: t('siteIntel.domainLite.dmarc'),
                            value: result.dns.emailSecurity.hasDmarc
                                ? t('siteIntel.common.yes')
                                : t('siteIntel.common.no'),
                        },
                    ]"
                />
            </InfoCard>

            <InfoCard
                :title="t('siteIntel.domainLite.whois')"
                :help-label="t('siteIntel.help.label')"
                :help-text="t('siteIntel.domainLite.help.whois')"
            >
                <KeyValueList
                    :items="[
                        {
                            label: t('siteIntel.domainLite.whoisServer'),
                            value: result.whois.server || '-',
                        },
                        {
                            label: t('siteIntel.domainLite.createdAt'),
                            value: formatDateTime(result.whois.createdAt),
                        },
                        {
                            label: t('siteIntel.domainLite.updatedAt'),
                            value: formatDateTime(result.whois.updatedAt),
                        },
                        {
                            label: t('siteIntel.domainLite.expiresAt'),
                            value: formatDateTime(result.whois.expiresAt),
                        },
                        {
                            label: t('siteIntel.domainLite.domainAgeDays'),
                            value: formatDays(
                                result.whois.timing.domainAgeDays
                            ),
                        },
                        {
                            label: t('siteIntel.domainLite.daysToExpiry'),
                            value: formatDays(result.whois.timing.daysToExpiry),
                        },
                        {
                            label: t('siteIntel.domainLite.registrar'),
                            value: result.whois.registrar || '-',
                        },
                        {
                            label: t('siteIntel.domainLite.country'),
                            value: result.whois.country || '-',
                        },
                    ]"
                />
            </InfoCard>

            <InfoCard :title="t('siteIntel.domainLite.emailSecurity')">
                <KeyValueList
                    :items="[
                        {
                            label: t('siteIntel.domainLite.spf'),
                            value: result.dns.emailSecurity.hasSpf
                                ? t('siteIntel.common.yes')
                                : t('siteIntel.common.no'),
                        },
                        {
                            label: t('siteIntel.domainLite.spfPolicy'),
                            value:
                                result.dns.emailSecurity.spfPolicy
                                    .allQualifier ?? '-',
                        },
                        {
                            label: t('siteIntel.domainLite.spfIncludeCount'),
                            value: result.dns.emailSecurity.spfPolicy
                                .includeCount,
                        },
                        {
                            label: t('siteIntel.domainLite.dmarcPolicy'),
                            value:
                                result.dns.emailSecurity.dmarcPolicy.policy ??
                                '-',
                        },
                        {
                            label: t('siteIntel.domainLite.dmarcPct'),
                            value:
                                result.dns.emailSecurity.dmarcPolicy
                                    .percentage ?? '-',
                        },
                        {
                            label: t('siteIntel.domainLite.dnssec'),
                            value: `${
                                result.dns.dnssec.enabled
                                    ? t('siteIntel.common.yes')
                                    : t('siteIntel.common.no')
                            } (DNSKEY: ${result.dns.dnssec.dnskeyCount})`,
                        },
                    ]"
                />
            </InfoCard>

            <InfoCard
                :title="t('siteIntel.domainLite.riskSignals')"
                :help-label="t('siteIntel.help.label')"
                :help-text="t('siteIntel.domainLite.help.risk')"
            >
                <p
                    v-if="result.risk.signals.length === 0"
                    class="intel-status-positive"
                >
                    {{ t('siteIntel.domainLite.noRiskSignals') }}
                </p>
                <ul
                    v-else
                    class="list-disc space-y-1 pl-4 text-muted-foreground"
                >
                    <li v-for="signal in result.risk.signals" :key="signal">
                        {{ signalLabel(signal) }}
                    </li>
                </ul>
            </InfoCard>

            <InfoCard :title="t('siteIntel.domainLite.riskBreakdown')">
                <p
                    v-if="result.risk.breakdown.length === 0"
                    class="intel-status-positive"
                >
                    {{ t('siteIntel.domainLite.noRiskBreakdown') }}
                </p>
                <div v-else class="space-y-2">
                    <div
                        v-for="entry in result.risk.breakdown"
                        :key="`${entry.key}-${entry.points}`"
                        class="intel-list-card"
                    >
                        <p class="font-medium">{{ signalLabel(entry.key) }}</p>
                        <p class="text-muted-foreground">
                            {{ t('siteIntel.domainLite.riskImpactPoints') }}:
                            +{{ entry.points }}
                        </p>
                    </div>
                </div>
            </InfoCard>

            <InfoCard
                v-if="result.whois.sample"
                :title="t('siteIntel.domainLite.whoisSample')"
            >
                <pre
                    class="overflow-x-auto text-[11px] whitespace-pre-wrap text-muted-foreground"
                    >{{ result.whois.sample }}</pre
                >
            </InfoCard>
        </div>
    </IntelResultPanel>
</template>
