<script setup lang="ts">
import { Globe, LoaderCircle } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import { useI18n } from '@/composables/useI18n';
import { getRepeatQueryParams, isRepeatAutorunEnabled, readRepeatQueryParam } from '@/composables/useRepeatQuery';
import { useDomainLite } from '../composables/useDomainLite';

const { t } = useI18n();
const { form, loading, error, result, canLookup, lookup } = useDomainLite(t);

const riskBadgeClass = computed(() => {
    const level = result.value?.risk.level;

    if (level === 'low') {
        return 'border-emerald-500/40 bg-emerald-500/10 text-emerald-300';
    }

    if (level === 'medium') {
        return 'border-amber-500/40 bg-amber-500/10 text-amber-300';
    }

    return 'border-rose-500/40 bg-rose-500/10 text-rose-300';
});

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
    <section class="sticky top-0 z-10 shrink-0 rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
        <div class="flex items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <Globe class="h-4 w-4 text-cyan-400" />
                    <span>{{ t('siteIntel.domainLite.title') }}</span>
                    <HelpTooltip :label="t('siteIntel.help.label')" :text="t('siteIntel.domainLite.help.overview')" />
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ t('siteIntel.domainLite.description') }}
                </p>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-end gap-3">
            <label class="block min-w-0 flex-1">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('siteIntel.domainLite.domain') }}</span>
                <input
                    v-model="form.domain"
                    type="text"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    :placeholder="t('siteIntel.domainLite.placeholder')"
                    @keydown.enter.prevent="lookup"
                />
            </label>

            <button
                :disabled="loading || !canLookup"
                class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                @click="lookup"
            >
                <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                <span>{{ loading ? t('siteIntel.domainLite.checking') : t('siteIntel.domainLite.check') }}</span>
            </button>
        </div>

        <p v-if="error" class="mt-3 text-sm text-destructive">{{ error }}</p>
    </section>

    <section class="flex min-h-0 flex-1 flex-col rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
        <div v-if="!result" class="rounded-md border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
            {{ t('siteIntel.domainLite.empty') }}
        </div>

        <div v-else class="telegram-scroll min-h-0 flex-1 overflow-y-auto space-y-3 pr-1">
            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="text-xs text-muted-foreground">{{ t('siteIntel.common.checkedAt') }}</p>
                    <p class="mt-1 text-sm font-semibold">{{ formatDateTime(result.checkedAt) }}</p>
                </div>
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="text-xs text-muted-foreground">{{ t('siteIntel.domainLite.whois') }}</p>
                    <p class="mt-1 text-sm font-semibold">
                        {{ result.whois.available ? t('siteIntel.common.available') : t('siteIntel.common.unavailable') }}
                    </p>
                </div>
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="text-xs text-muted-foreground">{{ t('siteIntel.domainLite.mxCount') }}</p>
                    <p class="mt-1 text-xl font-semibold">{{ result.dns.mx.length }}</p>
                </div>
                <div class="rounded-lg border p-3" :class="riskBadgeClass">
                    <p class="text-xs">{{ t('siteIntel.domainLite.riskScore') }}</p>
                    <p class="mt-1 text-xl font-semibold">{{ result.risk.score }}</p>
                    <p class="mt-1 text-[11px] leading-relaxed opacity-90">
                        {{ t('siteIntel.domainLite.riskScoreHint') }}
                    </p>
                </div>
            </div>

            <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                <div class="mb-2 flex items-center gap-2">
                    <p class="font-semibold">{{ t('siteIntel.domainLite.dnsSummary') }}</p>
                    <HelpTooltip :label="t('siteIntel.help.label')" :text="t('siteIntel.domainLite.help.dns')" />
                </div>
                <p>{{ t('siteIntel.domainLite.aRecords') }}: {{ result.dns.a.join(', ') || '-' }}</p>
                <p class="mt-1">{{ t('siteIntel.domainLite.aaaaRecords') }}: {{ result.dns.aaaa.join(', ') || '-' }}</p>
                <p class="mt-1">{{ t('siteIntel.domainLite.nsRecords') }}: {{ result.dns.ns.join(', ') || '-' }}</p>
                <p class="mt-1">{{ t('siteIntel.domainLite.mxRecords') }}: {{ result.dns.mx.map((record) => `${record.host} (${record.priority})`).join(', ') || '-' }}</p>
                <p class="mt-1">{{ t('siteIntel.domainLite.spf') }}: {{ result.dns.emailSecurity.hasSpf ? t('siteIntel.common.yes') : t('siteIntel.common.no') }}</p>
                <p class="mt-1">{{ t('siteIntel.domainLite.dmarc') }}: {{ result.dns.emailSecurity.hasDmarc ? t('siteIntel.common.yes') : t('siteIntel.common.no') }}</p>
            </div>

            <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                <div class="mb-2 flex items-center gap-2">
                    <p class="font-semibold">{{ t('siteIntel.domainLite.whois') }}</p>
                    <HelpTooltip :label="t('siteIntel.help.label')" :text="t('siteIntel.domainLite.help.whois')" />
                </div>
                <p>{{ t('siteIntel.domainLite.whoisServer') }}: {{ result.whois.server || '-' }}</p>
                <p class="mt-1">{{ t('siteIntel.domainLite.createdAt') }}: {{ formatDateTime(result.whois.createdAt) }}</p>
                <p class="mt-1">{{ t('siteIntel.domainLite.updatedAt') }}: {{ formatDateTime(result.whois.updatedAt) }}</p>
                <p class="mt-1">{{ t('siteIntel.domainLite.expiresAt') }}: {{ formatDateTime(result.whois.expiresAt) }}</p>
                <p class="mt-1">{{ t('siteIntel.domainLite.domainAgeDays') }}: {{ formatDays(result.whois.timing.domainAgeDays) }}</p>
                <p class="mt-1">{{ t('siteIntel.domainLite.daysToExpiry') }}: {{ formatDays(result.whois.timing.daysToExpiry) }}</p>
                <p class="mt-1">{{ t('siteIntel.domainLite.registrar') }}: {{ result.whois.registrar || '-' }}</p>
                <p class="mt-1">{{ t('siteIntel.domainLite.country') }}: {{ result.whois.country || '-' }}</p>
            </div>

            <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                <p class="mb-2 font-semibold">{{ t('siteIntel.domainLite.emailSecurity') }}</p>
                <p>{{ t('siteIntel.domainLite.spf') }}: {{ result.dns.emailSecurity.hasSpf ? t('siteIntel.common.yes') : t('siteIntel.common.no') }}</p>
                <p class="mt-1">{{ t('siteIntel.domainLite.spfPolicy') }}: {{ result.dns.emailSecurity.spfPolicy.allQualifier ?? '-' }}</p>
                <p class="mt-1">{{ t('siteIntel.domainLite.spfIncludeCount') }}: {{ result.dns.emailSecurity.spfPolicy.includeCount }}</p>
                <p class="mt-1">{{ t('siteIntel.domainLite.dmarcPolicy') }}: {{ result.dns.emailSecurity.dmarcPolicy.policy ?? '-' }}</p>
                <p class="mt-1">{{ t('siteIntel.domainLite.dmarcPct') }}: {{ result.dns.emailSecurity.dmarcPolicy.percentage ?? '-' }}</p>
                <p class="mt-1">{{ t('siteIntel.domainLite.dnssec') }}: {{ result.dns.dnssec.enabled ? t('siteIntel.common.yes') : t('siteIntel.common.no') }} (DNSKEY: {{ result.dns.dnssec.dnskeyCount }})</p>
            </div>

            <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                <div class="mb-2 flex items-center gap-2">
                    <p class="font-semibold">{{ t('siteIntel.domainLite.riskSignals') }}</p>
                    <HelpTooltip :label="t('siteIntel.help.label')" :text="t('siteIntel.domainLite.help.risk')" />
                </div>
                <p v-if="result.risk.signals.length === 0" class="text-emerald-300">{{ t('siteIntel.domainLite.noRiskSignals') }}</p>
                <ul v-else class="list-disc space-y-1 pl-4 text-muted-foreground">
                    <li v-for="signal in result.risk.signals" :key="signal">{{ signalLabel(signal) }}</li>
                </ul>
            </div>

            <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                <p class="mb-2 font-semibold">{{ t('siteIntel.domainLite.riskBreakdown') }}</p>
                <p v-if="result.risk.breakdown.length === 0" class="text-emerald-300">{{ t('siteIntel.domainLite.noRiskBreakdown') }}</p>
                <div v-else class="space-y-2">
                    <div
                        v-for="entry in result.risk.breakdown"
                        :key="`${entry.key}-${entry.points}`"
                        class="rounded border border-border/50 bg-background/40 px-2 py-1.5"
                    >
                        <p class="font-medium">{{ signalLabel(entry.key) }}</p>
                        <p class="text-muted-foreground">{{ t('siteIntel.domainLite.riskImpactPoints') }}: +{{ entry.points }}</p>
                    </div>
                </div>
            </div>

            <div v-if="result.whois.sample" class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                <p class="mb-2 font-semibold">{{ t('siteIntel.domainLite.whoisSample') }}</p>
                <pre class="overflow-x-auto whitespace-pre-wrap text-[11px] text-muted-foreground">{{ result.whois.sample }}</pre>
            </div>
        </div>
    </section>
</template>
