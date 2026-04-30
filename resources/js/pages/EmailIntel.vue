<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { BarChart3, Download, ExternalLink, FileText, LoaderCircle, MailSearch, Search } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { getRepeatQueryParams, isRepeatAutorunEnabled, readRepeatQueryParam } from '@/composables/useRepeatQuery';
import EmailEntityGraph from './email-intel/components/EmailEntityGraph.vue';
import { useEmailIntelLookup } from './email-intel/composables/useEmailIntelLookup';
import type { DomainMailPostureResult, EmailBulkIntelResult } from './email-intel/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Email Intel',
                href: '/email-intel',
            },
        ],
    },
});

const { t, locale } = useI18n();
const searchLookup = useEmailIntelLookup(t, locale);
const analyticsLookup = useEmailIntelLookup(t, locale);
const activeTab = ref<'search' | 'analytics' | 'bulk' | 'domain'>('search');
const bulkEmails = ref('');
const bulkLoading = ref(false);
const bulkError = ref<string | null>(null);
const bulkResult = ref<EmailBulkIntelResult | null>(null);
const domainForm = ref('');
const domainLoading = ref(false);
const domainError = ref<string | null>(null);
const domainResult = ref<DomainMailPostureResult | null>(null);

const pageTitle = computed(() => t('emailIntel.headTitle'));
const panelTitle = computed(() => {
    if (activeTab.value === 'bulk') return t('emailIntel.bulk.title');
    if (activeTab.value === 'domain') return t('emailIntel.domain.title');

    return t(activeTab.value === 'search' ? 'emailIntel.lookup.title' : 'emailIntel.analytics.title');
});
const panelDescription = computed(() => {
    if (activeTab.value === 'bulk') return t('emailIntel.bulk.description');
    if (activeTab.value === 'domain') return t('emailIntel.domain.description');

    return t(activeTab.value === 'search' ? 'emailIntel.lookup.description' : 'emailIntel.analytics.description');
});
const activeLookup = computed(() => (activeTab.value === 'search' ? searchLookup : analyticsLookup));
const form = computed(() => activeLookup.value.form);
const loading = computed(() => activeLookup.value.loading.value);
const error = computed(() => activeLookup.value.error.value);
const result = computed(() => activeLookup.value.result.value);
const canLookup = computed(() => activeLookup.value.canLookup.value);
const analyticsResult = computed(() => analyticsLookup.result.value);
const canBulkLookup = computed(() => bulkEmails.value.trim().length > 0);
const canDomainLookup = computed(() => /^(?!-)(?:[a-z0-9-]{1,63}\.)+[a-z]{2,63}$/i.test(domainForm.value.trim()));

const formatDateTime = (value: string): string => new Date(value).toLocaleString();

const riskLevelLabel = (level: string): string => {
    const key = `emailIntel.riskLevel.${level}`;
    const translated = t(key);

    return translated === key ? level : translated;
};

const signalLabel = (type: string, fallback: string): string => {
    const key = `emailIntel.signal.${type}`;
    const translated = t(key);

    return translated === key ? fallback : translated;
};

const signalClass = (level: string): string => {
    if (level === 'high') return 'border-rose-500/30 bg-rose-500/10 text-rose-200';
    if (level === 'medium') return 'border-amber-500/30 bg-amber-500/10 text-amber-200';
    if (level === 'low') return 'border-sky-500/30 bg-sky-500/10 text-sky-200';
    if (level === 'positive') return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200';

    return 'border-border bg-background/70 text-muted-foreground';
};

const scoreClass = (score: number): string => {
    if (score >= 80) return 'text-emerald-300';
    if (score >= 50) return 'text-amber-300';

    return 'text-rose-300';
};

const reportUrl = computed(() => {
    const email = analyticsResult.value?.target.email ?? analyticsLookup.form.email.trim();
    const query = new URLSearchParams({ email, locale: locale.value });

    return `/email-intel/report?${query.toString()}`;
});

const lookup = () => activeLookup.value.lookup();

const resetBulk = () => {
    bulkEmails.value = '';
    bulkError.value = null;
    bulkResult.value = null;
    bulkLoading.value = false;
};

const resetDomain = () => {
    domainForm.value = '';
    domainError.value = null;
    domainResult.value = null;
    domainLoading.value = false;
};

const switchTab = (tab: 'search' | 'analytics' | 'bulk' | 'domain') => {
    if (activeTab.value === tab) {
        return;
    }

    activeTab.value = tab;
    searchLookup.reset();
    analyticsLookup.reset();
    resetBulk();
    resetDomain();
};

const bulkLookup = async () => {
    if (!canBulkLookup.value) {
        bulkError.value = t('emailIntel.errors.emailRequired');
        return;
    }

    bulkLoading.value = true;
    bulkError.value = null;
    bulkResult.value = null;

    try {
        const query = new URLSearchParams({ emails: bulkEmails.value, locale: locale.value });
        const response = await fetch(`/email-intel/bulk?${query.toString()}`, { headers: { Accept: 'application/json' } });
        const payload = await response.json();

        if (!response.ok || !payload?.ok) {
            bulkError.value = payload?.message ?? t('emailIntel.errors.lookupFailed');
            return;
        }

        bulkResult.value = payload.data as EmailBulkIntelResult;
    } catch (exception) {
        bulkError.value = exception instanceof Error ? exception.message : t('emailIntel.errors.lookupFailed');
    } finally {
        bulkLoading.value = false;
    }
};

const domainLookup = async () => {
    if (!canDomainLookup.value) {
        domainError.value = t('emailIntel.errors.domainRequired');
        return;
    }

    domainLoading.value = true;
    domainError.value = null;
    domainResult.value = null;

    try {
        const query = new URLSearchParams({ domain: domainForm.value.trim(), locale: locale.value });
        const response = await fetch(`/email-intel/domain-posture?${query.toString()}`, { headers: { Accept: 'application/json' } });
        const payload = await response.json();

        if (!response.ok || !payload?.ok) {
            domainError.value = payload?.message ?? t('emailIntel.errors.lookupFailed');
            return;
        }

        domainResult.value = payload.data as DomainMailPostureResult;
    } catch (exception) {
        domainError.value = exception instanceof Error ? exception.message : t('emailIntel.errors.lookupFailed');
    } finally {
        domainLoading.value = false;
    }
};

const openReport = () => {
    window.open(reportUrl.value, '_blank', 'noopener,noreferrer');
};

const downloadReport = () => {
    window.open(`${reportUrl.value}&download=1`, '_blank', 'noopener,noreferrer');
};

onMounted(() => {
    const params = getRepeatQueryParams();
    if (!params) {
        return;
    }

    const requestedTab = readRepeatQueryParam(params, ['tab']);
    const tab = requestedTab === 'search' || requestedTab === 'analytics' || requestedTab === 'bulk' || requestedTab === 'domain'
        ? requestedTab
        : activeTab.value;

    activeTab.value = tab;

    const email = readRepeatQueryParam(params, ['email']);
    if (email !== '') {
        searchLookup.form.email = email;
        analyticsLookup.form.email = email;
    }

    const emails = readRepeatQueryParam(params, ['emails']);
    if (emails !== '') {
        bulkEmails.value = emails;
    }

    const domain = readRepeatQueryParam(params, ['domain']);
    if (domain !== '') {
        domainForm.value = domain;
    }

    if (!isRepeatAutorunEnabled(params)) {
        return;
    }

    if (tab === 'search' && searchLookup.canLookup.value) {
        void searchLookup.lookup();
        return;
    }

    if (tab === 'analytics' && analyticsLookup.canLookup.value) {
        void analyticsLookup.lookup();
        return;
    }

    if (tab === 'bulk' && canBulkLookup.value) {
        void bulkLookup();
        return;
    }

    if (tab === 'domain' && canDomainLookup.value) {
        void domainLookup();
    }
});
</script>

<template>
    <Head :title="pageTitle" />

    <div class="flex h-full min-h-0 flex-1 flex-col gap-4 overflow-hidden rounded-xl p-4">
        <div class="flex items-center justify-center gap-1 rounded-lg bg-slate-800/80 p-1">
            <button
                type="button"
                @click="switchTab('search')"
                :class="[
                    'flex items-center rounded-md px-3 py-1.5 text-xs font-medium transition-all',
                    activeTab === 'search'
                        ? 'bg-slate-700/80 text-cyan-300 shadow-sm'
                        : 'text-slate-400 hover:bg-slate-700/50 hover:text-slate-200',
                ]"
            >
                <Search class="mr-1.5 h-3.5 w-3.5" />
                {{ t('emailIntel.tabs.search') }}
            </button>
            <button
                type="button"
                @click="switchTab('analytics')"
                :class="[
                    'flex items-center rounded-md px-3 py-1.5 text-xs font-medium transition-all',
                    activeTab === 'analytics'
                        ? 'bg-slate-700/80 text-cyan-300 shadow-sm'
                        : 'text-slate-400 hover:bg-slate-700/50 hover:text-slate-200',
                ]"
            >
                <BarChart3 class="mr-1.5 h-3.5 w-3.5" />
                {{ t('emailIntel.tabs.analytics') }}
            </button>
            <button
                type="button"
                @click="switchTab('bulk')"
                :class="[
                    'flex items-center rounded-md px-3 py-1.5 text-xs font-medium transition-all',
                    activeTab === 'bulk'
                        ? 'bg-slate-700/80 text-cyan-300 shadow-sm'
                        : 'text-slate-400 hover:bg-slate-700/50 hover:text-slate-200',
                ]"
            >
                <MailSearch class="mr-1.5 h-3.5 w-3.5" />
                {{ t('emailIntel.tabs.bulk') }}
            </button>
            <button
                type="button"
                @click="switchTab('domain')"
                :class="[
                    'flex items-center rounded-md px-3 py-1.5 text-xs font-medium transition-all',
                    activeTab === 'domain'
                        ? 'bg-slate-700/80 text-cyan-300 shadow-sm'
                        : 'text-slate-400 hover:bg-slate-700/50 hover:text-slate-200',
                ]"
            >
                <BarChart3 class="mr-1.5 h-3.5 w-3.5" />
                {{ t('emailIntel.tabs.domain') }}
            </button>
        </div>

        <section class="sticky top-0 z-10 shrink-0 rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
            <div class="flex items-center justify-between gap-3">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-sm font-semibold">
                        <MailSearch class="h-4 w-4 text-cyan-400" />
                        <span>{{ panelTitle }}</span>
                        <span class="group relative inline-flex">
                            <span
                                class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                :aria-label="t('emailIntel.help.label')"
                            >
                                ?
                            </span>
                            <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                {{ t('emailIntel.help.overview') }}
                            </span>
                        </span>
                    </div>
                    <p class="text-xs text-muted-foreground">{{ panelDescription }}</p>
                </div>
            </div>

            <div v-if="activeTab === 'search' || activeTab === 'analytics'" class="mt-3 flex flex-wrap items-end gap-3">
                <label class="block min-w-0 flex-1">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('emailIntel.lookup.email') }}</span>
                    <input
                        v-model="form.email"
                        type="email"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('emailIntel.lookup.placeholder')"
                        @keydown.enter.prevent="lookup"
                    />
                </label>

                <button
                    :disabled="loading || !canLookup"
                    class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                    @click="lookup"
                >
                    <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                    <span>{{ loading ? t('emailIntel.lookup.checking') : t('emailIntel.lookup.check') }}</span>
                </button>

                <button
                    v-if="activeTab === 'analytics'"
                    type="button"
                    :disabled="!result"
                    class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                    @click="openReport"
                >
                    <FileText class="h-4 w-4" />
                    {{ t('emailIntel.analytics.report') }}
                </button>

                <button
                    v-if="activeTab === 'analytics'"
                    type="button"
                    :disabled="!result"
                    class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                    @click="downloadReport"
                >
                    <Download class="h-4 w-4" />
                    {{ t('emailIntel.analytics.downloadReport') }}
                </button>
            </div>

            <div v-else-if="activeTab === 'bulk'" class="mt-3 flex flex-wrap items-end gap-3">
                <label class="block min-w-0 flex-1">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('emailIntel.bulk.emails') }}</span>
                    <textarea
                        v-model="bulkEmails"
                        rows="3"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        :placeholder="t('emailIntel.bulk.placeholder')"
                    />
                </label>

                <button
                    :disabled="bulkLoading || !canBulkLookup"
                    class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                    @click="bulkLookup"
                >
                    <LoaderCircle v-if="bulkLoading" class="h-4 w-4 animate-spin" />
                    <span>{{ bulkLoading ? t('emailIntel.lookup.checking') : t('emailIntel.bulk.check') }}</span>
                </button>
            </div>

            <div v-else class="mt-3 flex flex-wrap items-end gap-3">
                <label class="block min-w-0 flex-1">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('emailIntel.domain.domain') }}</span>
                    <input
                        v-model="domainForm"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('emailIntel.domain.placeholder')"
                        @keydown.enter.prevent="domainLookup"
                    />
                </label>

                <button
                    :disabled="domainLoading || !canDomainLookup"
                    class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                    @click="domainLookup"
                >
                    <LoaderCircle v-if="domainLoading" class="h-4 w-4 animate-spin" />
                    <span>{{ domainLoading ? t('emailIntel.lookup.checking') : t('emailIntel.domain.check') }}</span>
                </button>
            </div>

            <p v-if="error" class="mt-3 text-sm text-destructive">{{ error }}</p>
            <p v-if="bulkError" class="mt-3 text-sm text-destructive">{{ bulkError }}</p>
            <p v-if="domainError" class="mt-3 text-sm text-destructive">{{ domainError }}</p>
        </section>

        <section class="flex min-h-0 flex-1 flex-col rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
            <div v-if="activeTab === 'bulk' && bulkResult" class="telegram-scroll min-h-0 flex-1 overflow-y-auto pr-1">
                <div class="overflow-hidden rounded-lg border border-border/70 bg-background/60">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-background/80 text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2">{{ t('emailIntel.bulk.email') }}</th>
                                <th class="px-3 py-2">{{ t('emailIntel.lookup.riskScore') }}</th>
                                <th class="px-3 py-2">{{ t('emailIntel.analytics.provider') }}</th>
                                <th class="px-3 py-2">MX</th>
                                <th class="px-3 py-2">SPF</th>
                                <th class="px-3 py-2">DMARC</th>
                                <th class="px-3 py-2">{{ t('emailIntel.analytics.deliverability') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in bulkResult.items" :key="item.email" class="border-t border-border/60">
                                <td class="break-words px-3 py-2 [overflow-wrap:anywhere]">{{ item.email }}</td>
                                <td class="px-3 py-2">{{ item.ok ? `${item.riskScore} / ${item.riskLevel}` : item.error }}</td>
                                <td class="px-3 py-2">{{ item.provider || '-' }}</td>
                                <td class="px-3 py-2">{{ item.mxCount ?? '-' }}</td>
                                <td class="px-3 py-2">{{ item.hasSpf ? t('common.yes') : t('common.no') }}</td>
                                <td class="px-3 py-2">{{ item.hasDmarc ? t('common.yes') : t('common.no') }}</td>
                                <td class="px-3 py-2">{{ item.deliverabilityScore ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else-if="activeTab === 'domain' && domainResult" class="telegram-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('emailIntel.domain.domain') }}</p>
                        <p class="mt-1 text-sm font-semibold">{{ domainResult.domain }}</p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('emailIntel.analytics.deliverability') }}</p>
                        <p class="mt-1 text-xl font-semibold" :class="scoreClass(domainResult.deliverability.score)">{{ domainResult.deliverability.score }}</p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('emailIntel.analytics.mailSecurity') }}</p>
                        <p class="mt-1 text-xl font-semibold" :class="scoreClass(domainResult.scores.mailSecurity)">{{ domainResult.scores.mailSecurity }}</p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('emailIntel.lookup.mxRecords') }}</p>
                        <p class="mt-1 text-xl font-semibold">{{ domainResult.dns.mx.length }}</p>
                    </div>
                </div>

                <div class="grid gap-3 xl:grid-cols-2">
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                        <p class="mb-2 font-semibold">{{ t('emailIntel.analytics.deliverabilityHints') }}</p>
                        <div class="space-y-2">
                            <p v-for="hint in domainResult.deliverability.hints" :key="hint.key" class="text-muted-foreground">
                                {{ hint.passed ? t('common.ok') : t('common.fail') }} - {{ t(`emailIntel.deliverability.${hint.key}`) }}
                            </p>
                        </div>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                        <p class="mb-2 font-semibold">{{ t('emailIntel.analytics.provider') }}</p>
                        <p class="text-sm font-semibold">{{ domainResult.provider.name }}</p>
                        <p class="mt-1 text-muted-foreground">SPF: {{ domainResult.spf.strictness }}</p>
                        <p class="mt-1 text-muted-foreground">DMARC: {{ domainResult.dmarc.policy || '-' }} / {{ domainResult.dmarc.strength }}</p>
                        <p class="mt-1 text-muted-foreground">HTTP: {{ domainResult.webSnapshot.status ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <div v-else-if="!result" class="rounded-md border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                {{ t('emailIntel.lookup.empty') }}
            </div>

            <div v-else-if="activeTab === 'search'" class="telegram-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('emailIntel.lookup.checkedAt') }}</p>
                        <p class="mt-1 text-sm font-semibold">{{ formatDateTime(result.checkedAt) }}</p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <div class="flex items-center gap-2">
                            <p class="text-xs text-muted-foreground">{{ t('emailIntel.lookup.riskScore') }}</p>
                            <span class="group relative inline-flex">
                                <span
                                    class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                    :aria-label="t('emailIntel.help.label')"
                                >
                                    ?
                                </span>
                                <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                    {{ t('emailIntel.help.riskScore') }}
                                </span>
                            </span>
                        </div>
                        <p class="mt-1 text-xl font-semibold">{{ result.riskScore }}</p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('emailIntel.lookup.riskLevel') }}</p>
                        <p class="mt-1 text-sm font-semibold uppercase">{{ riskLevelLabel(result.riskLevel) }}</p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('emailIntel.lookup.mxRecords') }}</p>
                        <p class="mt-1 text-xl font-semibold">{{ result.dns.mx.length }}</p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('emailIntel.lookup.emailSecurity') }}</p>
                        <p class="mt-1 text-sm font-semibold">
                            SPF {{ result.dns.emailSecurity.hasSpf ? t('common.yes') : t('common.no') }},
                            DMARC {{ result.dns.emailSecurity.hasDmarc ? t('common.yes') : t('common.no') }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 xl:grid-cols-2">
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                        <div class="mb-2 flex items-center gap-2">
                            <p class="font-semibold">{{ t('emailIntel.lookup.target') }}</p>
                            <span class="group relative inline-flex">
                                <span
                                    class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                    :aria-label="t('emailIntel.help.label')"
                                >
                                    ?
                                </span>
                                <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                    {{ t('emailIntel.help.target') }}
                                </span>
                            </span>
                        </div>
                        <p class="break-words [overflow-wrap:anywhere] text-muted-foreground">{{ result.target.email }}</p>
                        <p class="mt-1 text-muted-foreground">{{ t('emailIntel.lookup.domain') }}: {{ result.target.domain }}</p>
                        <p class="mt-1 text-muted-foreground">{{ t('emailIntel.lookup.localPart') }}: {{ result.target.localPart }}</p>
                        <p class="mt-1 break-words [overflow-wrap:anywhere] text-muted-foreground">SHA-256: {{ result.target.sha256 }}</p>
                    </div>

                    <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                        <div class="mb-2 flex items-center gap-2">
                            <p class="font-semibold">{{ t('emailIntel.lookup.profile') }}</p>
                            <span class="group relative inline-flex">
                                <span
                                    class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                    :aria-label="t('emailIntel.help.label')"
                                >
                                    ?
                                </span>
                                <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                    {{ t('emailIntel.help.profile') }}
                                </span>
                            </span>
                        </div>
                        <p class="text-muted-foreground">{{ t('emailIntel.lookup.freeProvider') }}: {{ result.profile.isFreeProvider ? t('common.yes') : t('common.no') }}</p>
                        <p class="mt-1 text-muted-foreground">{{ t('emailIntel.lookup.disposable') }}: {{ result.profile.isDisposable ? t('common.yes') : t('common.no') }}</p>
                        <p class="mt-1 text-muted-foreground">{{ t('emailIntel.lookup.roleAccount') }}: {{ result.profile.isRoleAccount ? t('common.yes') : t('common.no') }}</p>
                        <p class="mt-1 break-words [overflow-wrap:anywhere] text-muted-foreground">Gravatar MD5: {{ result.profile.gravatarHash }}</p>
                    </div>
                </div>

                <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                    <div class="mb-2 flex items-center gap-2">
                        <p class="font-semibold">{{ t('emailIntel.lookup.signals') }}</p>
                        <span class="group relative inline-flex">
                            <span
                                class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                :aria-label="t('emailIntel.help.label')"
                            >
                                ?
                            </span>
                            <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                {{ t('emailIntel.help.signals') }}
                            </span>
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="signal in result.signals"
                            :key="signal.type"
                            :class="['rounded-full border px-2 py-1', signalClass(signal.level)]"
                        >
                            {{ signalLabel(signal.type, signal.message) }}
                        </span>
                    </div>
                </div>

                <div class="grid gap-3 xl:grid-cols-2">
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                        <p class="mb-2 font-semibold">{{ t('emailIntel.lookup.mxRecords') }}</p>
                        <p v-if="result.dns.mx.length === 0" class="text-muted-foreground">-</p>
                        <div v-else class="space-y-1">
                            <p v-for="record in result.dns.mx" :key="`${record.host}-${record.priority}`" class="text-muted-foreground">
                                {{ record.priority }} - {{ record.host }}
                            </p>
                        </div>
                    </div>

                    <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                        <div class="mb-2 flex items-center gap-2">
                            <p class="font-semibold">{{ t('emailIntel.lookup.dns') }}</p>
                            <span class="group relative inline-flex">
                                <span
                                    class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                    :aria-label="t('emailIntel.help.label')"
                                >
                                    ?
                                </span>
                                <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                    {{ t('emailIntel.help.dns') }}
                                </span>
                            </span>
                        </div>
                        <p class="text-muted-foreground">A: {{ result.dns.a.join(', ') || '-' }}</p>
                        <p class="mt-1 text-muted-foreground">AAAA: {{ result.dns.aaaa.join(', ') || '-' }}</p>
                        <p class="mt-1 break-words [overflow-wrap:anywhere] text-muted-foreground">DMARC: {{ result.dns.dmarc.join(' | ') || '-' }}</p>
                    </div>
                </div>
            </div>

            <div v-else class="telegram-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('emailIntel.analytics.overallScore') }}</p>
                        <p class="mt-1 text-xl font-semibold" :class="scoreClass(result.analytics.scores.overall)">
                            {{ result.analytics.scores.overall }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('emailIntel.analytics.mailSecurity') }}</p>
                        <p class="mt-1 text-xl font-semibold" :class="scoreClass(result.analytics.scores.mailSecurity)">
                            {{ result.analytics.scores.mailSecurity }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('emailIntel.analytics.domainHealth') }}</p>
                        <p class="mt-1 text-xl font-semibold" :class="scoreClass(result.analytics.scores.domainHealth)">
                            {{ result.analytics.scores.domainHealth }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('emailIntel.analytics.identityConfidence') }}</p>
                        <p class="mt-1 text-xl font-semibold" :class="scoreClass(result.analytics.scores.identityConfidence)">
                            {{ result.analytics.scores.identityConfidence }}
                        </p>
                    </div>
                </div>

                <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <p class="font-semibold">{{ t('emailIntel.analytics.deliverabilityHints') }}</p>
                        <span class="rounded-full border border-border px-2 py-1 text-muted-foreground">
                            {{ t('emailIntel.analytics.deliverability') }}: {{ result.analytics.deliverability.score }} / {{ result.analytics.deliverability.status }}
                        </span>
                    </div>
                    <div class="grid gap-2 md:grid-cols-2">
                        <p
                            v-for="hint in result.analytics.deliverability.hints"
                            :key="hint.key"
                            class="rounded-md border border-border/70 bg-background/70 p-2 text-muted-foreground"
                        >
                            {{ hint.passed ? t('common.ok') : t('common.fail') }} - {{ t(`emailIntel.deliverability.${hint.key}`) }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 xl:grid-cols-2">
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                        <div class="mb-2 flex items-center gap-2">
                            <p class="font-semibold">{{ t('emailIntel.analytics.provider') }}</p>
                            <span class="group relative inline-flex">
                                <span
                                    class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                    :aria-label="t('emailIntel.help.label')"
                                >
                                    ?
                                </span>
                                <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                    {{ t('emailIntel.help.provider') }}
                                </span>
                            </span>
                        </div>
                        <p class="text-sm font-semibold">{{ result.analytics.provider.name }}</p>
                        <p class="mt-1 text-muted-foreground">{{ t('emailIntel.analytics.type') }}: {{ result.analytics.provider.type }}</p>
                        <p class="mt-1 text-muted-foreground">{{ t('emailIntel.analytics.confidence') }}: {{ result.analytics.provider.confidence }}%</p>
                        <p class="mt-1 break-words [overflow-wrap:anywhere] text-muted-foreground">
                            MX: {{ result.analytics.provider.mxHosts.join(', ') || '-' }}
                        </p>
                    </div>

                    <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                        <div class="mb-2 flex items-center gap-2">
                            <p class="font-semibold">{{ t('emailIntel.analytics.localPart') }}</p>
                            <span class="group relative inline-flex">
                                <span
                                    class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                    :aria-label="t('emailIntel.help.label')"
                                >
                                    ?
                                </span>
                                <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                    {{ t('emailIntel.help.localPart') }}
                                </span>
                            </span>
                        </div>
                        <p class="text-muted-foreground">{{ t('emailIntel.analytics.length') }}: {{ result.analytics.localPart.length }}</p>
                        <p class="mt-1 text-muted-foreground">{{ t('emailIntel.analytics.entropy') }}: {{ result.analytics.localPart.entropy }}</p>
                        <p class="mt-1 text-muted-foreground">{{ t('emailIntel.analytics.tokens') }}: {{ result.analytics.localPart.tokens.join(', ') || '-' }}</p>
                        <p class="mt-1 text-muted-foreground">{{ t('emailIntel.analytics.plusAddressing') }}: {{ result.analytics.localPart.hasPlusAddressing ? t('common.yes') : t('common.no') }}</p>
                        <p class="mt-1 text-muted-foreground">{{ t('emailIntel.analytics.looksRandom') }}: {{ result.analytics.localPart.looksRandom ? t('common.yes') : t('common.no') }}</p>
                    </div>
                </div>

                <div class="grid gap-3 xl:grid-cols-2">
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                        <div class="mb-2 flex items-center gap-2">
                            <p class="font-semibold">{{ t('emailIntel.analytics.spf') }}</p>
                            <span class="group relative inline-flex">
                                <span
                                    class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                    :aria-label="t('emailIntel.help.label')"
                                >
                                    ?
                                </span>
                                <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                    {{ t('emailIntel.help.spf') }}
                                </span>
                            </span>
                        </div>
                        <p class="text-muted-foreground">{{ t('emailIntel.analytics.strictness') }}: {{ result.analytics.spf.strictness }}</p>
                        <p class="mt-1 text-muted-foreground">all: {{ result.analytics.spf.all || '-' }}</p>
                        <p class="mt-1 text-muted-foreground">include: {{ result.analytics.spf.includes.join(', ') || '-' }}</p>
                        <p class="mt-1 break-words [overflow-wrap:anywhere] text-muted-foreground">{{ result.analytics.spf.record || '-' }}</p>
                    </div>

                    <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                        <div class="mb-2 flex items-center gap-2">
                            <p class="font-semibold">{{ t('emailIntel.analytics.dmarc') }}</p>
                            <span class="group relative inline-flex">
                                <span
                                    class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                    :aria-label="t('emailIntel.help.label')"
                                >
                                    ?
                                </span>
                                <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                    {{ t('emailIntel.help.dmarc') }}
                                </span>
                            </span>
                        </div>
                        <p class="text-muted-foreground">{{ t('emailIntel.analytics.policy') }}: {{ result.analytics.dmarc.policy || '-' }}</p>
                        <p class="mt-1 text-muted-foreground">{{ t('emailIntel.analytics.strength') }}: {{ result.analytics.dmarc.strength }}</p>
                        <p class="mt-1 text-muted-foreground">rua: {{ result.analytics.dmarc.rua.join(', ') || '-' }}</p>
                        <p class="mt-1 break-words [overflow-wrap:anywhere] text-muted-foreground">{{ result.analytics.dmarc.record || '-' }}</p>
                    </div>
                </div>

                <div class="grid gap-3 xl:grid-cols-2">
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                        <div class="mb-2 flex items-center gap-2">
                            <p class="font-semibold">{{ t('emailIntel.analytics.riskBreakdown') }}</p>
                            <span class="group relative inline-flex">
                                <span
                                    class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                    :aria-label="t('emailIntel.help.label')"
                                >
                                    ?
                                </span>
                                <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                    {{ t('emailIntel.help.riskBreakdown') }}
                                </span>
                            </span>
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="item in result.analytics.riskBreakdown.items"
                                :key="`${item.type}-${item.level}`"
                                class="flex items-center justify-between gap-2 rounded-md border border-border/70 bg-background/70 px-2 py-1.5"
                            >
                                <span class="text-muted-foreground">{{ signalLabel(item.type, item.message) }}</span>
                                <span class="font-semibold">+{{ item.points }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                        <p class="mb-2 font-semibold">{{ t('emailIntel.analytics.dmarcReports') }}</p>
                        <p v-if="result.analytics.dmarcReports.destinations.length === 0" class="text-muted-foreground">-</p>
                        <div v-else class="space-y-2">
                            <div
                                v-for="destination in result.analytics.dmarcReports.destinations"
                                :key="`${destination.kind}-${destination.mailbox}`"
                                class="rounded-md border border-border/70 bg-background/70 p-2 text-muted-foreground"
                            >
                                <p class="break-words [overflow-wrap:anywhere]">
                                    {{ destination.kind }}: {{ destination.mailbox }}
                                </p>
                                <p class="mt-1">
                                    {{ t('emailIntel.analytics.external') }}:
                                    {{ destination.external ? t('common.yes') : t('common.no') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                    <p class="mb-2 font-semibold">{{ t('emailIntel.analytics.spfIncludes') }}</p>
                    <p v-if="result.analytics.spfExpandedIncludes.length === 0" class="text-muted-foreground">-</p>
                    <div v-else class="grid gap-2 xl:grid-cols-2">
                        <div
                            v-for="include in result.analytics.spfExpandedIncludes"
                            :key="include.domain"
                            class="rounded-md border border-border/70 bg-background/70 p-2 text-muted-foreground"
                        >
                            <p class="font-semibold text-foreground">{{ include.domain }}</p>
                            <p class="mt-1">{{ t('emailIntel.analytics.resolved') }}: {{ include.resolved ? t('common.yes') : t('common.no') }}</p>
                            <p class="mt-1">{{ t('emailIntel.analytics.strictness') }}: {{ include.strictness }}</p>
                            <p class="mt-1">ip4: {{ include.ip4.join(', ') || '-' }}</p>
                            <p class="mt-1">include: {{ include.includes.join(', ') || '-' }}</p>
                            <p class="mt-1 break-words [overflow-wrap:anywhere]">{{ include.record || '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                    <div class="mb-2 flex items-center gap-2">
                        <p class="font-semibold">{{ t('emailIntel.analytics.searchLinks') }}</p>
                        <span class="group relative inline-flex">
                            <span
                                class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                :aria-label="t('emailIntel.help.label')"
                            >
                                ?
                            </span>
                            <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                {{ t('emailIntel.help.searchLinks') }}
                            </span>
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a
                            v-for="link in result.analytics.searchLinks"
                            :key="link.url"
                            :href="link.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 rounded-md border border-border px-2 py-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                        >
                            <ExternalLink class="h-3.5 w-3.5" />
                            {{ link.label }}
                        </a>
                    </div>
                </div>

                <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                    <div class="mb-2 flex items-center gap-2">
                        <p class="font-semibold">{{ t('emailIntel.analytics.recommendations') }}</p>
                        <span class="group relative inline-flex">
                            <span
                                class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                :aria-label="t('emailIntel.help.label')"
                            >
                                ?
                            </span>
                            <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                {{ t('emailIntel.help.recommendations') }}
                            </span>
                        </span>
                    </div>
                    <div class="space-y-2">
                        <div
                            v-for="item in result.analytics.recommendations"
                            :key="item.key"
                            class="rounded-md border border-border/70 bg-background/70 p-2 text-muted-foreground"
                        >
                            <p class="font-semibold text-foreground">{{ t(`emailIntel.recommendation.${item.key}`) }}</p>
                            <p class="mt-1">{{ t('emailIntel.analytics.priority') }}: {{ item.priority }}, {{ t('emailIntel.analytics.impact') }}: +{{ item.impact }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-3 xl:grid-cols-2">
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                        <p class="mb-2 font-semibold">{{ t('emailIntel.analytics.webSnapshot') }}</p>
                        <p class="text-muted-foreground">{{ result.analytics.webSnapshot.url }}</p>
                        <p class="mt-1 text-muted-foreground">HTTP: {{ result.analytics.webSnapshot.status ?? '-' }}</p>
                        <p class="mt-1 text-muted-foreground">{{ t('emailIntel.analytics.durationMs') }}: {{ result.analytics.webSnapshot.durationMs }}</p>
                        <p class="mt-1 text-muted-foreground">
                            HSTS: {{ result.analytics.webSnapshot.securityHeaders.strictTransportSecurity ? t('common.yes') : t('common.no') }},
                            CSP: {{ result.analytics.webSnapshot.securityHeaders.contentSecurityPolicy ? t('common.yes') : t('common.no') }}
                        </p>
                    </div>

                    <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                        <p class="mb-2 font-semibold">{{ t('emailIntel.analytics.similarDomains') }}</p>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="item in result.analytics.similarDomains"
                                :key="item.domain"
                                class="rounded-md border border-border px-2 py-1 text-muted-foreground"
                            >
                                {{ item.domain }} ({{ t(`emailIntel.similarDomainReason.${item.reason}`) }})
                            </span>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                    <div class="mb-2 flex items-center gap-2">
                        <p class="font-semibold">{{ t('emailIntel.analytics.graph') }}</p>
                        <span class="group relative inline-flex">
                            <span
                                class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                                :aria-label="t('emailIntel.help.label')"
                            >
                                ?
                            </span>
                            <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                                {{ t('emailIntel.help.graph') }}
                            </span>
                        </span>
                    </div>
                    <EmailEntityGraph
                        :nodes="result.analytics.graph.nodes"
                        :edges="result.analytics.graph.edges"
                    />
                </div>
            </div>
        </section>
    </div>
</template>
