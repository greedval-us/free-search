<script setup lang="ts">
import { Globe, LoaderCircle } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
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

const signalLabel = (signal: string) => {
    const key = `siteIntel.domainLite.signal.${signal}`;
    const translated = t(key);

    return translated === key ? signal : translated;
};
</script>

<template>
    <section class="sticky top-0 z-10 shrink-0 rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
        <div class="flex items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <Globe class="h-4 w-4 text-cyan-400" />
                    <span>{{ t('siteIntel.domainLite.title') }}</span>
                    <span class="group relative inline-flex">
                        <span
                            class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                            :aria-label="t('siteIntel.help.label')"
                        >
                            ?
                        </span>
                        <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                            {{ t('siteIntel.domainLite.help.overview') }}
                        </span>
                    </span>
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
                </div>
            </div>

            <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                <div class="mb-2 flex items-center gap-2">
                    <p class="font-semibold">{{ t('siteIntel.domainLite.dnsSummary') }}</p>
                    <span class="group relative inline-flex">
                        <span
                            class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                            :aria-label="t('siteIntel.help.label')"
                        >
                            ?
                        </span>
                        <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                            {{ t('siteIntel.domainLite.help.dns') }}
                        </span>
                    </span>
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
                    <span class="group relative inline-flex">
                        <span
                            class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                            :aria-label="t('siteIntel.help.label')"
                        >
                            ?
                        </span>
                        <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                            {{ t('siteIntel.domainLite.help.whois') }}
                        </span>
                    </span>
                </div>
                <p>{{ t('siteIntel.domainLite.whoisServer') }}: {{ result.whois.server || '-' }}</p>
                <p class="mt-1">{{ t('siteIntel.domainLite.createdAt') }}: {{ formatDateTime(result.whois.createdAt) }}</p>
                <p class="mt-1">{{ t('siteIntel.domainLite.updatedAt') }}: {{ formatDateTime(result.whois.updatedAt) }}</p>
                <p class="mt-1">{{ t('siteIntel.domainLite.expiresAt') }}: {{ formatDateTime(result.whois.expiresAt) }}</p>
                <p class="mt-1">{{ t('siteIntel.domainLite.registrar') }}: {{ result.whois.registrar || '-' }}</p>
                <p class="mt-1">{{ t('siteIntel.domainLite.country') }}: {{ result.whois.country || '-' }}</p>
            </div>

            <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                <div class="mb-2 flex items-center gap-2">
                    <p class="font-semibold">{{ t('siteIntel.domainLite.riskSignals') }}</p>
                    <span class="group relative inline-flex">
                        <span
                            class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                            :aria-label="t('siteIntel.help.label')"
                        >
                            ?
                        </span>
                        <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                            {{ t('siteIntel.domainLite.help.risk') }}
                        </span>
                    </span>
                </div>
                <p v-if="result.risk.signals.length === 0" class="text-emerald-300">{{ t('siteIntel.domainLite.noRiskSignals') }}</p>
                <ul v-else class="list-disc space-y-1 pl-4 text-muted-foreground">
                    <li v-for="signal in result.risk.signals" :key="signal">{{ signalLabel(signal) }}</li>
                </ul>
            </div>

            <div v-if="result.whois.sample" class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                <p class="mb-2 font-semibold">{{ t('siteIntel.domainLite.whoisSample') }}</p>
                <pre class="overflow-x-auto whitespace-pre-wrap text-[11px] text-muted-foreground">{{ result.whois.sample }}</pre>
            </div>
        </div>
    </section>
</template>
