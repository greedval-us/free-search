<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    BarChart3,
    Download,
    ExternalLink,
    FileText,
    LoaderCircle,
    MailSearch,
    Search,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue';
import ModuleTabsLayout from '@/components/ui/ModuleTabsLayout.vue';
import { useI18n } from '@/composables/useI18n';
import {
    getRepeatQueryParams,
    isRepeatAutorunEnabled,
    readRepeatQueryParam,
} from '@/composables/useRepeatQuery';
import EmailEntityGraph from './email-intel/components/EmailEntityGraph.vue';
import { useDomainMailPosture } from './email-intel/composables/useDomainMailPosture';
import { useEmailBulkLookup } from './email-intel/composables/useEmailBulkLookup';
import { useEmailIntelLookup } from './email-intel/composables/useEmailIntelLookup';

type EmailIntelTabValue = 'search' | 'analytics' | 'bulk' | 'domain';

const EMAIL_INTEL_TABS = [
    { key: 'search', labelKey: 'emailIntel.tabs.search', icon: Search },
    {
        key: 'analytics',
        labelKey: 'emailIntel.tabs.analytics',
        icon: BarChart3,
    },
    { key: 'bulk', labelKey: 'emailIntel.tabs.bulk', icon: MailSearch },
    { key: 'domain', labelKey: 'emailIntel.tabs.domain', icon: BarChart3 },
] as const;

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
const bulkLookupState = useEmailBulkLookup(t, locale);
const domainLookupState = useDomainMailPosture(t, locale);
const activeTab = ref<EmailIntelTabValue>('search');
const {
    canLookup: canBulkLookup,
    emails: bulkEmails,
    error: bulkError,
    loading: bulkLoading,
    lookup: bulkLookup,
    reset: resetBulk,
    result: bulkResult,
} = bulkLookupState;
const {
    canLookup: canDomainLookup,
    domain: domainForm,
    error: domainError,
    loading: domainLoading,
    lookup: domainLookup,
    reset: resetDomain,
    result: domainResult,
} = domainLookupState;

const pageTitle = computed(() => t('emailIntel.headTitle'));
const panelTitle = computed(() => {
    if (activeTab.value === 'bulk') {
        return t('emailIntel.bulk.title');
    }

    if (activeTab.value === 'domain') {
        return t('emailIntel.domain.title');
    }

    return t(
        activeTab.value === 'search'
            ? 'emailIntel.lookup.title'
            : 'emailIntel.analytics.title'
    );
});
const panelDescription = computed(() => {
    if (activeTab.value === 'bulk') {
        return t('emailIntel.bulk.description');
    }

    if (activeTab.value === 'domain') {
        return t('emailIntel.domain.description');
    }

    return t(
        activeTab.value === 'search'
            ? 'emailIntel.lookup.description'
            : 'emailIntel.analytics.description'
    );
});
const activeLookup = computed(() =>
    activeTab.value === 'search' ? searchLookup : analyticsLookup
);
const form = computed(() => activeLookup.value.form);
const loading = computed(() => activeLookup.value.loading.value);
const error = computed(() => activeLookup.value.error.value);
const result = computed(() => activeLookup.value.result.value);
const canLookup = computed(() => activeLookup.value.canLookup.value);
const analyticsResult = computed(() => analyticsLookup.result.value);
const formatDateTime = (value: string): string =>
    new Date(value).toLocaleString();

const signalLabel = (type: string, fallback: string): string => {
    const key = `emailIntel.signal.${type}`;
    const translated = t(key);

    return translated === key ? fallback : translated;
};

const scoreClass = (score: number): string => {
    if (score >= 80) {
        return 'text-emerald-300';
    }

    if (score >= 50) {
        return 'text-amber-300';
    }

    return 'text-rose-300';
};

const reportUrl = computed(() => {
    const email =
        analyticsResult.value?.target.email ??
        analyticsLookup.form.email.trim();
    const query = new URLSearchParams({ email, locale: locale.value });

    return `/email-intel/report?${query.toString()}`;
});

const lookup = () => activeLookup.value.lookup();

const switchTab = (tab: EmailIntelTabValue) => {
    if (activeTab.value === tab) {
        return;
    }

    activeTab.value = tab;
    searchLookup.reset();
    analyticsLookup.reset();
    resetBulk();
    resetDomain();
};

const openReport = () => {
    window.open(reportUrl.value, '_blank', 'noopener,noreferrer');
};

const downloadReport = () => {
    window.open(
        `${reportUrl.value}&download=1`,
        '_blank',
        'noopener,noreferrer'
    );
};

onMounted(() => {
    const params = getRepeatQueryParams();

    if (!params) {
        return;
    }

    const requestedTab = readRepeatQueryParam(params, ['tab']);
    const tab =
        requestedTab === 'search' ||
        requestedTab === 'analytics' ||
        requestedTab === 'bulk' ||
        requestedTab === 'domain'
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

    <ModuleTabsLayout
        :active-tab="activeTab"
        :tabs="EMAIL_INTEL_TABS"
        @update:active-tab="switchTab($event as EmailIntelTabValue)"
    >
        <IntelSearchPanel>
            <div class="flex items-center justify-between gap-3">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-sm font-semibold">
                        <MailSearch class="h-4 w-4 text-cyan-400" />
                        <span>{{ panelTitle }}</span>
                        <HelpTooltip
                            :label="t('emailIntel.help.label')"
                            :text="t('emailIntel.help.overview')"
                        />
                    </div>
                    <p class="text-xs text-muted-foreground">
                        {{ panelDescription }}
                    </p>
                </div>
            </div>

            <div
                v-if="activeTab === 'search' || activeTab === 'analytics'"
                class="mt-3 flex flex-wrap items-end gap-3"
            >
                <label class="block min-w-0 flex-1">
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                        >{{ t('emailIntel.lookup.email') }}</span
                    >
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
                    <span>{{
                        loading
                            ? t('emailIntel.lookup.checking')
                            : t('emailIntel.lookup.check')
                    }}</span>
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

            <div
                v-else-if="activeTab === 'bulk'"
                class="mt-3 flex flex-wrap items-end gap-3"
            >
                <label class="block min-w-0 flex-1">
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                        >{{ t('emailIntel.bulk.emails') }}</span
                    >
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
                    <LoaderCircle
                        v-if="bulkLoading"
                        class="h-4 w-4 animate-spin"
                    />
                    <span>{{
                        bulkLoading
                            ? t('emailIntel.lookup.checking')
                            : t('emailIntel.bulk.check')
                    }}</span>
                </button>
            </div>

            <div v-else class="mt-3 flex flex-wrap items-end gap-3">
                <label class="block min-w-0 flex-1">
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                        >{{ t('emailIntel.domain.domain') }}</span
                    >
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
                    <LoaderCircle
                        v-if="domainLoading"
                        class="h-4 w-4 animate-spin"
                    />
                    <span>{{
                        domainLoading
                            ? t('emailIntel.lookup.checking')
                            : t('emailIntel.domain.check')
                    }}</span>
                </button>
            </div>

            <p v-if="error" class="mt-3 text-sm text-destructive">
                {{ error }}
            </p>
            <p v-if="bulkError" class="mt-3 text-sm text-destructive">
                {{ bulkError }}
            </p>
            <p v-if="domainError" class="mt-3 text-sm text-destructive">
                {{ domainError }}
            </p>
        </IntelSearchPanel>

        <IntelResultPanel>
            <div
                v-if="activeTab === 'bulk' && bulkResult"
                class="intel-scroll min-h-0 flex-1 overflow-y-auto pr-1"
            >
                <div
                    class="overflow-hidden rounded-lg border border-border/70 bg-background/60"
                >
                    <table class="w-full text-left text-xs">
                        <thead class="bg-background/80 text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2">
                                    {{ t('emailIntel.bulk.email') }}
                                </th>
                                <th class="px-3 py-2">
                                    {{ t('emailIntel.lookup.riskScore') }}
                                </th>
                                <th class="px-3 py-2">
                                    {{ t('emailIntel.analytics.provider') }}
                                </th>
                                <th class="px-3 py-2">MX</th>
                                <th class="px-3 py-2">SPF</th>
                                <th class="px-3 py-2">DMARC</th>
                                <th class="px-3 py-2">
                                    {{
                                        t('emailIntel.analytics.deliverability')
                                    }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in bulkResult.items"
                                :key="item.email"
                                class="border-t border-border/60"
                            >
                                <td
                                    class="px-3 py-2 [overflow-wrap:anywhere] break-words"
                                >
                                    {{ item.email }}
                                </td>
                                <td class="px-3 py-2">
                                    {{
                                        item.ok
                                            ? `${item.riskScore} / ${item.riskLevel}`
                                            : item.error
                                    }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ item.provider || '-' }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ item.mxCount ?? '-' }}
                                </td>
                                <td class="px-3 py-2">
                                    {{
                                        item.hasSpf
                                            ? t('common.yes')
                                            : t('common.no')
                                    }}
                                </td>
                                <td class="px-3 py-2">
                                    {{
                                        item.hasDmarc
                                            ? t('common.yes')
                                            : t('common.no')
                                    }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ item.deliverabilityScore ?? '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div
                v-else-if="activeTab === 'domain' && domainResult"
                class="intel-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1"
            >
                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    <div
                        class="rounded-lg border border-border/70 bg-background/60 p-3"
                    >
                        <p class="text-xs text-muted-foreground">
                            {{ t('emailIntel.domain.domain') }}
                        </p>
                        <p class="mt-1 text-sm font-semibold">
                            {{ domainResult.domain }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg border border-border/70 bg-background/60 p-3"
                    >
                        <p class="text-xs text-muted-foreground">
                            {{ t('emailIntel.analytics.deliverability') }}
                        </p>
                        <p
                            class="mt-1 text-xl font-semibold"
                            :class="
                                scoreClass(domainResult.deliverability.score)
                            "
                        >
                            {{ domainResult.deliverability.score }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg border border-border/70 bg-background/60 p-3"
                    >
                        <p class="text-xs text-muted-foreground">
                            {{ t('emailIntel.analytics.mailSecurity') }}
                        </p>
                        <p
                            class="mt-1 text-xl font-semibold"
                            :class="
                                scoreClass(domainResult.scores.mailSecurity)
                            "
                        >
                            {{ domainResult.scores.mailSecurity }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg border border-border/70 bg-background/60 p-3"
                    >
                        <p class="text-xs text-muted-foreground">
                            {{ t('emailIntel.lookup.mxRecords') }}
                        </p>
                        <p class="mt-1 text-xl font-semibold">
                            {{ domainResult.dns.mx.length }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 xl:grid-cols-2">
                    <div
                        class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs"
                    >
                        <p class="mb-2 font-semibold">
                            {{ t('emailIntel.analytics.deliverabilityHints') }}
                        </p>
                        <div class="space-y-2">
                            <p
                                v-for="hint in domainResult.deliverability
                                    .hints"
                                :key="hint.key"
                                class="text-muted-foreground"
                            >
                                {{
                                    hint.passed
                                        ? t('common.ok')
                                        : t('common.fail')
                                }}
                                -
                                {{ t(`emailIntel.deliverability.${hint.key}`) }}
                            </p>
                        </div>
                    </div>
                    <div
                        class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs"
                    >
                        <p class="mb-2 font-semibold">
                            {{ t('emailIntel.analytics.provider') }}
                        </p>
                        <p class="text-sm font-semibold">
                            {{ domainResult.provider.name }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            SPF: {{ domainResult.spf.strictness }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            DMARC: {{ domainResult.dmarc.policy || '-' }} /
                            {{ domainResult.dmarc.strength }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            HTTP: {{ domainResult.webSnapshot.status ?? '-' }}
                        </p>
                    </div>
                </div>
            </div>

            <div v-else-if="!result" class="intel-empty">
                {{ t('emailIntel.lookup.empty') }}
            </div>

            <div
                v-else-if="activeTab === 'search'"
                class="intel-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1"
            >
                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
                    <div
                        class="rounded-lg border border-border/70 bg-background/60 p-3"
                    >
                        <p class="text-xs text-muted-foreground">
                            {{ t('emailIntel.lookup.checkedAt') }}
                        </p>
                        <p class="mt-1 text-sm font-semibold">
                            {{ formatDateTime(result.checkedAt) }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg border border-border/70 bg-background/60 p-3"
                    >
                        <div class="flex items-center gap-2">
                            <p class="text-xs text-muted-foreground">
                                {{ t('emailIntel.lookup.riskScore') }}
                            </p>
                            <HelpTooltip
                                :label="t('emailIntel.help.label')"
                                :text="t('emailIntel.help.provider')"
                            />
                        </div>
                        <p class="text-sm font-semibold">
                            {{ result.analytics.provider.name }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            {{ t('emailIntel.analytics.type') }}:
                            {{ result.analytics.provider.type }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            {{ t('emailIntel.analytics.confidence') }}:
                            {{ result.analytics.provider.confidence }}%
                        </p>
                        <p
                            class="mt-1 [overflow-wrap:anywhere] break-words text-muted-foreground"
                        >
                            MX:
                            {{
                                result.analytics.provider.mxHosts.join(', ') ||
                                '-'
                            }}
                        </p>
                    </div>

                    <div
                        class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs"
                    >
                        <div class="mb-2 flex items-center gap-2">
                            <p class="font-semibold">
                                {{ t('emailIntel.analytics.localPart') }}
                            </p>
                            <HelpTooltip
                                :label="t('emailIntel.help.label')"
                                :text="t('emailIntel.help.localPart')"
                            />
                        </div>
                        <p class="text-muted-foreground">
                            {{ t('emailIntel.analytics.length') }}:
                            {{ result.analytics.localPart.length }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            {{ t('emailIntel.analytics.entropy') }}:
                            {{ result.analytics.localPart.entropy }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            {{ t('emailIntel.analytics.tokens') }}:
                            {{
                                result.analytics.localPart.tokens.join(', ') ||
                                '-'
                            }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            {{ t('emailIntel.analytics.plusAddressing') }}:
                            {{
                                result.analytics.localPart.hasPlusAddressing
                                    ? t('common.yes')
                                    : t('common.no')
                            }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            {{ t('emailIntel.analytics.looksRandom') }}:
                            {{
                                result.analytics.localPart.looksRandom
                                    ? t('common.yes')
                                    : t('common.no')
                            }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 xl:grid-cols-2">
                    <div
                        class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs"
                    >
                        <div class="mb-2 flex items-center gap-2">
                            <p class="font-semibold">
                                {{ t('emailIntel.analytics.spf') }}
                            </p>
                            <HelpTooltip
                                :label="t('emailIntel.help.label')"
                                :text="t('emailIntel.help.spf')"
                            />
                        </div>
                        <p class="text-muted-foreground">
                            {{ t('emailIntel.analytics.strictness') }}:
                            {{ result.analytics.spf.strictness }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            all: {{ result.analytics.spf.all || '-' }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            include:
                            {{
                                result.analytics.spf.includes.join(', ') || '-'
                            }}
                        </p>
                        <p
                            class="mt-1 [overflow-wrap:anywhere] break-words text-muted-foreground"
                        >
                            {{ result.analytics.spf.record || '-' }}
                        </p>
                    </div>

                    <div
                        class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs"
                    >
                        <div class="mb-2 flex items-center gap-2">
                            <p class="font-semibold">
                                {{ t('emailIntel.analytics.dmarc') }}
                            </p>
                            <HelpTooltip
                                :label="t('emailIntel.help.label')"
                                :text="t('emailIntel.help.dmarc')"
                            />
                        </div>
                        <p class="text-muted-foreground">
                            {{ t('emailIntel.analytics.policy') }}:
                            {{ result.analytics.dmarc.policy || '-' }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            {{ t('emailIntel.analytics.strength') }}:
                            {{ result.analytics.dmarc.strength }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            rua:
                            {{ result.analytics.dmarc.rua.join(', ') || '-' }}
                        </p>
                        <p
                            class="mt-1 [overflow-wrap:anywhere] break-words text-muted-foreground"
                        >
                            {{ result.analytics.dmarc.record || '-' }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 xl:grid-cols-2">
                    <div
                        class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs"
                    >
                        <div class="mb-2 flex items-center gap-2">
                            <p class="font-semibold">
                                {{ t('emailIntel.analytics.riskBreakdown') }}
                            </p>
                            <HelpTooltip
                                :label="t('emailIntel.help.label')"
                                :text="t('emailIntel.help.riskBreakdown')"
                            />
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="item in result.analytics.riskBreakdown
                                    .items"
                                :key="`${item.type}-${item.level}`"
                                class="flex items-center justify-between gap-2 rounded-md border border-border/70 bg-background/70 px-2 py-1.5"
                            >
                                <span class="text-muted-foreground">{{
                                    signalLabel(item.type, item.message)
                                }}</span>
                                <span class="font-semibold"
                                    >+{{ item.points }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs"
                    >
                        <p class="mb-2 font-semibold">
                            {{ t('emailIntel.analytics.dmarcReports') }}
                        </p>
                        <p
                            v-if="
                                result.analytics.dmarcReports.destinations
                                    .length === 0
                            "
                            class="text-muted-foreground"
                        >
                            -
                        </p>
                        <div v-else class="space-y-2">
                            <div
                                v-for="destination in result.analytics
                                    .dmarcReports.destinations"
                                :key="`${destination.kind}-${destination.mailbox}`"
                                class="rounded-md border border-border/70 bg-background/70 p-2 text-muted-foreground"
                            >
                                <p class="[overflow-wrap:anywhere] break-words">
                                    {{ destination.kind }}:
                                    {{ destination.mailbox }}
                                </p>
                                <p class="mt-1">
                                    {{ t('emailIntel.analytics.external') }}:
                                    {{
                                        destination.external
                                            ? t('common.yes')
                                            : t('common.no')
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs"
                >
                    <p class="mb-2 font-semibold">
                        {{ t('emailIntel.analytics.spfIncludes') }}
                    </p>
                    <p
                        v-if="result.analytics.spfExpandedIncludes.length === 0"
                        class="text-muted-foreground"
                    >
                        -
                    </p>
                    <div v-else class="grid gap-2 xl:grid-cols-2">
                        <div
                            v-for="include in result.analytics
                                .spfExpandedIncludes"
                            :key="include.domain"
                            class="rounded-md border border-border/70 bg-background/70 p-2 text-muted-foreground"
                        >
                            <p class="font-semibold text-foreground">
                                {{ include.domain }}
                            </p>
                            <p class="mt-1">
                                {{ t('emailIntel.analytics.resolved') }}:
                                {{
                                    include.resolved
                                        ? t('common.yes')
                                        : t('common.no')
                                }}
                            </p>
                            <p class="mt-1">
                                {{ t('emailIntel.analytics.strictness') }}:
                                {{ include.strictness }}
                            </p>
                            <p class="mt-1">
                                ip4: {{ include.ip4.join(', ') || '-' }}
                            </p>
                            <p class="mt-1">
                                include:
                                {{ include.includes.join(', ') || '-' }}
                            </p>
                            <p
                                class="mt-1 [overflow-wrap:anywhere] break-words"
                            >
                                {{ include.record || '-' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs"
                >
                    <div class="mb-2 flex items-center gap-2">
                        <p class="font-semibold">
                            {{ t('emailIntel.analytics.searchLinks') }}
                        </p>
                        <HelpTooltip
                            :label="t('emailIntel.help.label')"
                            :text="t('emailIntel.help.searchLinks')"
                        />
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

                <div
                    class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs"
                >
                    <div class="mb-2 flex items-center gap-2">
                        <p class="font-semibold">
                            {{ t('emailIntel.analytics.recommendations') }}
                        </p>
                        <HelpTooltip
                            :label="t('emailIntel.help.label')"
                            :text="t('emailIntel.help.recommendations')"
                        />
                    </div>
                    <div class="space-y-2">
                        <div
                            v-for="item in result.analytics.recommendations"
                            :key="item.key"
                            class="rounded-md border border-border/70 bg-background/70 p-2 text-muted-foreground"
                        >
                            <p class="font-semibold text-foreground">
                                {{ t(`emailIntel.recommendation.${item.key}`) }}
                            </p>
                            <p class="mt-1">
                                {{ t('emailIntel.analytics.priority') }}:
                                {{ item.priority }},
                                {{ t('emailIntel.analytics.impact') }}: +{{
                                    item.impact
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-3 xl:grid-cols-2">
                    <div
                        class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs"
                    >
                        <p class="mb-2 font-semibold">
                            {{ t('emailIntel.analytics.webSnapshot') }}
                        </p>
                        <p class="text-muted-foreground">
                            {{ result.analytics.webSnapshot.url }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            HTTP:
                            {{ result.analytics.webSnapshot.status ?? '-' }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            {{ t('emailIntel.analytics.durationMs') }}:
                            {{ result.analytics.webSnapshot.durationMs }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            HSTS:
                            {{
                                result.analytics.webSnapshot.securityHeaders
                                    .strictTransportSecurity
                                    ? t('common.yes')
                                    : t('common.no')
                            }}, CSP:
                            {{
                                result.analytics.webSnapshot.securityHeaders
                                    .contentSecurityPolicy
                                    ? t('common.yes')
                                    : t('common.no')
                            }}
                        </p>
                    </div>

                    <div
                        class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs"
                    >
                        <p class="mb-2 font-semibold">
                            {{ t('emailIntel.analytics.similarDomains') }}
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="item in result.analytics.similarDomains"
                                :key="item.domain"
                                class="rounded-md border border-border px-2 py-1 text-muted-foreground"
                            >
                                {{ item.domain }} ({{
                                    t(
                                        `emailIntel.similarDomainReason.${item.reason}`
                                    )
                                }})
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs"
                >
                    <div class="mb-2 flex items-center gap-2">
                        <p class="font-semibold">
                            {{ t('emailIntel.analytics.graph') }}
                        </p>
                        <HelpTooltip
                            :label="t('emailIntel.help.label')"
                            :text="t('emailIntel.help.graph')"
                        />
                    </div>
                    <EmailEntityGraph
                        :nodes="result.analytics.graph.nodes"
                        :edges="result.analytics.graph.edges"
                    />
                </div>
            </div>
        </IntelResultPanel>
    </ModuleTabsLayout>
</template>
