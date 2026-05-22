<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Building2 } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import IntelSearchForm from '@/components/ui/IntelSearchForm.vue';
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue';
import MetricCard from '@/components/ui/MetricCard.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import RiskBadge from '@/components/ui/RiskBadge.vue';
import SectionCard from '@/components/ui/SectionCard.vue';
import { getRepeatQueryParams, isRepeatAutorunEnabled, readRepeatQueryParam } from '@/composables/useRepeatQuery';
import { apiRequest } from '@/lib/api';
import { useI18n } from '@/composables/useI18n';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Company Intel',
                href: '/company-intel',
            },
        ],
    },
});

type CompanyIntelResult = {
    query: string;
    domain: string | null;
    checkedAt: string;
    summary: {
        riskLevel: 'unknown' | 'low' | 'medium' | 'high';
        riskScore: number | null;
        riskExplanation: string;
        signals: string[];
        strengths: string[];
        recommendations: string[];
    };
    domainIntel: {
        available: boolean;
        dns?: {
            aCount: number;
            nsCount: number;
            mxCount: number;
            hasSpf: boolean;
            spfStrict: boolean;
            hasDmarc: boolean;
            dmarcPolicy: string | null;
            txtCount: number;
            caaCount: number;
            dnssecEnabled: boolean;
        };
        whois?: {
            available: boolean;
            registrar: string | null;
            country: string | null;
            createdAt: string | null;
            expiresAt: string | null;
            domainAgeDays: number | null;
            daysToExpiry: number | null;
        };
    };
    osintLinks: Array<{
        label: string;
        url: string;
    }>;
};

const { t, locale } = useI18n();
const pageTitle = computed(() => t('companyIntel.headTitle'));

const form = reactive({
    query: '',
});

const loading = ref(false);
const error = ref<string | null>(null);
const result = ref<CompanyIntelResult | null>(null);

const canLookup = computed(() => form.query.trim().length >= 2);

const formatDateTime = (value: string | null) => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleString();
};

const signalLabel = (signal: string) => {
    const key = `companyIntel.signal.${signal}`;
    const translated = t(key);

    return translated === key ? signal : translated;
};

const riskLabel = (value: string | undefined) => {
    const key = `companyIntel.risk.${value ?? 'unknown'}`;
    const translated = t(key);

    return translated === key ? (value ?? 'unknown') : translated;
};

const linkLabel = (value: string) => {
    const key = `companyIntel.links.${value}`;
    const translated = t(key);

    return translated === key ? value : translated;
};

const recommendationLabel = (value: string) => {
    const key = `companyIntel.recommendation.${value}`;
    const translated = t(key);

    return translated === key ? value : translated;
};

const riskExplanationLabel = (value: string | undefined) => {
    const key = `companyIntel.riskExplanation.${value ?? 'risk_unknown_missing_domain'}`;
    const translated = t(key);

    return translated === key ? (value ?? 'risk_unknown_missing_domain') : translated;
};

const strengthLabel = (value: string) => {
    const key = `companyIntel.strength.${value}`;
    const translated = t(key);

    return translated === key ? value : translated;
};

const lookup = async () => {
    if (!canLookup.value) {
        error.value = t('companyIntel.errors.queryRequired');

        return;
    }

    loading.value = true;
    error.value = null;
    result.value = null;

    try {
        const apiResult = await apiRequest<CompanyIntelResult>('/company-intel/lookup', {
            method: 'GET',
            query: {
                query: form.query.trim(),
                locale: locale.value,
            },
        });

        if (!apiResult.ok) {
            error.value = apiResult.message || t('companyIntel.errors.lookupFailed');

            return;
        }

        result.value = apiResult.data;
    } catch (exception) {
        error.value = exception instanceof Error ? exception.message : t('companyIntel.errors.lookupFailed');
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    const params = getRepeatQueryParams();

    if (!params) {
        return;
    }

    const value = readRepeatQueryParam(params, ['query']);

    if (value !== '') {
        form.query = value;
    }

    if (isRepeatAutorunEnabled(params) && canLookup.value) {
        void lookup();
    }
});
</script>

<template>
    <Head :title="pageTitle" />

    <div class="flex h-full min-h-0 flex-1 flex-col gap-4 overflow-hidden rounded-xl p-4">
        <IntelSearchPanel>
            <div class="flex items-center justify-between gap-3">
                <PageHeader :icon="Building2" :title="t('companyIntel.title')" :description="t('companyIntel.description')" :help-label="t('companyIntel.help.label')" :help-text="t('companyIntel.help.overview')" />
            </div>

            <IntelSearchForm
                v-model="form.query"
                :label="t('companyIntel.query')"
                :placeholder="t('companyIntel.placeholder')"
                :button-text="t('companyIntel.search')"
                :loading-text="t('companyIntel.searching')"
                :loading="loading"
                :disabled="!canLookup"
                :error="error"
                @submit="lookup"
            />
        </IntelSearchPanel>

        <IntelResultPanel>
            <EmptyState v-if="!result" :text="t('companyIntel.empty')" />

            <div v-else class="telegram-scroll min-h-0 flex-1 overflow-y-auto space-y-3 pr-1">
                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    <MetricCard :title="t('companyIntel.checkedAt')" :value="formatDateTime(result.checkedAt)" />
                    <MetricCard :title="t('companyIntel.detectedDomain')" :value="result.domain ?? '-'" />
                    <RiskBadge :label="t('companyIntel.riskScore')" :level="result.summary.riskLevel" :score="result.summary.riskScore">
                        {{ riskLabel(result.summary.riskLevel) }}
                    </RiskBadge>
                    <MetricCard :title="t('companyIntel.signalCount')" :value="result.summary.signals.length" />
                </div>

                <SectionCard :title="t('companyIntel.riskScore')">
                    <p class="text-muted-foreground">{{ riskExplanationLabel(result.summary.riskExplanation) }}</p>
                </SectionCard>

                <SectionCard :title="t('companyIntel.signals')">
                    <p v-if="result.summary.signals.length === 0" class="text-emerald-300">{{ t('companyIntel.noSignals') }}</p>
                    <ul v-else class="list-disc space-y-1 pl-4 text-muted-foreground">
                        <li v-for="signal in result.summary.signals" :key="signal">{{ signalLabel(signal) }}</li>
                    </ul>
                </SectionCard>

                <SectionCard :title="t('companyIntel.strengths')">
                    <p v-if="result.summary.strengths.length === 0" class="text-muted-foreground">-</p>
                    <ul v-else class="list-disc space-y-1 pl-4 text-muted-foreground">
                        <li v-for="strength in result.summary.strengths" :key="strength">{{ strengthLabel(strength) }}</li>
                    </ul>
                </SectionCard>

                <div v-if="result.domainIntel.available" class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs space-y-1">
                    <p class="mb-2 font-semibold">{{ t('companyIntel.domainIntel') }}</p>
                    <p>{{ t('companyIntel.labels.dns') }}: A {{ result.domainIntel.dns?.aCount ?? 0 }}, NS {{ result.domainIntel.dns?.nsCount ?? 0 }}, MX {{ result.domainIntel.dns?.mxCount ?? 0 }}</p>
                    <p>TXT {{ result.domainIntel.dns?.txtCount ?? 0 }}, CAA {{ result.domainIntel.dns?.caaCount ?? 0 }}, DNSSEC {{ result.domainIntel.dns?.dnssecEnabled ? t('common.yes') : t('common.no') }}</p>
                    <p>{{ t('companyIntel.labels.mailSecurity') }}: SPF {{ result.domainIntel.dns?.hasSpf ? t('common.yes') : t('common.no') }} ({{ result.domainIntel.dns?.spfStrict ? t('common.yes') : t('common.no') }} strict), DMARC {{ result.domainIntel.dns?.hasDmarc ? t('common.yes') : t('common.no') }} (p={{ result.domainIntel.dns?.dmarcPolicy ?? '-' }})</p>
                    <p>{{ t('companyIntel.labels.whois') }}: {{ result.domainIntel.whois?.available ? t('companyIntel.available') : t('companyIntel.unavailable') }}</p>
                    <p>{{ t('companyIntel.registrar') }}: {{ result.domainIntel.whois?.registrar ?? '-' }}</p>
                    <p>{{ t('companyIntel.country') }}: {{ result.domainIntel.whois?.country ?? '-' }}</p>
                    <p>{{ t('companyIntel.createdAt') }}: {{ formatDateTime(result.domainIntel.whois?.createdAt ?? null) }}</p>
                    <p>{{ t('companyIntel.expiresAt') }}: {{ formatDateTime(result.domainIntel.whois?.expiresAt ?? null) }}</p>
                    <p>{{ t('companyIntel.domainAgeDays') }}: {{ result.domainIntel.whois?.domainAgeDays ?? '-' }}</p>
                    <p>{{ t('companyIntel.daysToExpiry') }}: {{ result.domainIntel.whois?.daysToExpiry ?? '-' }}</p>
                </div>

                <SectionCard :title="t('companyIntel.recommendations')">
                    <ul class="mb-3 list-disc space-y-1 pl-4 text-muted-foreground">
                        <li v-for="recommendation in result.summary.recommendations" :key="recommendation">{{ recommendationLabel(recommendation) }}</li>
                    </ul>
                </SectionCard>

                <SectionCard :title="t('companyIntel.osintLinks')" :description="t('companyIntel.help.links')">
                    <div class="space-y-1">
                        <a
                            v-for="link in result.osintLinks"
                            :key="link.url"
                            class="block break-words text-cyan-300 hover:underline"
                            :href="link.url"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {{ linkLabel(link.label) }}
                        </a>
                    </div>
                </SectionCard>
            </div>
        </IntelResultPanel>
    </div>
</template>
