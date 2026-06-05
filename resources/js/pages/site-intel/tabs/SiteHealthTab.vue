<script setup lang="ts">
import { Activity } from 'lucide-vue-next';
import { onMounted } from 'vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import IntelSearchForm from '@/components/ui/IntelSearchForm.vue';
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue';
import InfoCard from '@/components/ui/InfoCard.vue';
import KeyValueList from '@/components/ui/KeyValueList.vue';
import MetricCard from '@/components/ui/MetricCard.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import { useI18n } from '@/composables/useI18n';
import {
    getRepeatQueryParams,
    isRepeatAutorunEnabled,
    readRepeatQueryParam,
} from '@/composables/useRepeatQuery';
import { useSiteHealth } from '../composables/useSiteHealth';

const { t } = useI18n();
const { form, loading, error, result, canCheck, check } = useSiteHealth(t);

const formatDateTime = (value: string | null) => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleString();
};

const headerLabel = (headerName: string) => {
    const key = `siteIntel.siteHealth.header.${headerName.replaceAll('-', '_')}`;
    const translated = t(key);

    return translated === key ? headerName : translated;
};

const signalLabel = (signal: string) => {
    const key = `siteIntel.siteHealth.signal.${signal}`;
    const translated = t(key);

    return translated === key ? signal : translated;
};

onMounted(() => {
    const params = getRepeatQueryParams();

    if (!params) {
        return;
    }

    const tab = readRepeatQueryParam(params, ['tab']);

    if (tab !== '' && tab !== 'siteHealth') {
        return;
    }

    const target = readRepeatQueryParam(params, ['target']);

    if (target !== '') {
        form.target = target;
    }

    if (isRepeatAutorunEnabled(params) && canCheck.value) {
        void check();
    }
});
</script>

<template>
    <IntelSearchPanel>
        <PageHeader
            :icon="Activity"
            :title="t('siteIntel.siteHealth.title')"
            :description="t('siteIntel.siteHealth.description')"
            :help-label="t('siteIntel.help.label')"
            :help-text="t('siteIntel.siteHealth.help.overview')"
        />

        <IntelSearchForm
            v-model="form.target"
            :label="t('siteIntel.siteHealth.target')"
            :placeholder="t('siteIntel.siteHealth.placeholder')"
            :button-text="t('siteIntel.siteHealth.check')"
            :loading-text="t('siteIntel.siteHealth.checking')"
            :loading="loading"
            :disabled="!canCheck"
            :error="error"
            @submit="check"
        />
    </IntelSearchPanel>

    <IntelResultPanel>
        <EmptyState v-if="!result" :text="t('siteIntel.siteHealth.empty')" />

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
                    :title="t('siteIntel.siteHealth.finalStatus')"
                    :value="result.http.finalStatus || '-'"
                />
                <MetricCard
                    :title="t('siteIntel.siteHealth.redirects')"
                    :value="result.http.totalRedirects"
                />
                <MetricCard
                    :title="t('siteIntel.siteHealth.score')"
                    :value="result.score.value"
                    :tone="
                        result.score.level === 'high'
                            ? 'positive'
                            : result.score.level === 'medium'
                              ? 'warning'
                              : 'danger'
                    "
                />
            </div>

            <InfoCard :title="t('siteIntel.siteHealth.finalUrl')">
                <p class="mt-1 break-all text-muted-foreground">
                    {{ result.http.finalUrl }}
                </p>
            </InfoCard>

            <InfoCard
                :title="t('siteIntel.siteHealth.dns')"
                :help-label="t('siteIntel.help.label')"
                :help-text="t('siteIntel.siteHealth.help.dns')"
            >
                <KeyValueList
                    :items="[
                        {
                            label: t('siteIntel.siteHealth.aRecords'),
                            value: result.dns.a.join(', ') || '-',
                        },
                        {
                            label: t('siteIntel.siteHealth.aaaaRecords'),
                            value: result.dns.aaaa.join(', ') || '-',
                        },
                    ]"
                />
            </InfoCard>

            <InfoCard
                :title="t('siteIntel.siteHealth.ssl')"
                :help-label="t('siteIntel.help.label')"
                :help-text="t('siteIntel.siteHealth.help.ssl')"
            >
                <KeyValueList
                    :items="[
                        {
                            label: t('siteIntel.siteHealth.sslAvailable'),
                            value: result.ssl.available
                                ? t('siteIntel.common.yes')
                                : t('siteIntel.common.no'),
                        },
                        {
                            label: t('siteIntel.siteHealth.sslIssuer'),
                            value: result.ssl.issuer || '-',
                        },
                        {
                            label: t('siteIntel.siteHealth.sslSubject'),
                            value: result.ssl.subject || '-',
                        },
                        {
                            label: t('siteIntel.siteHealth.sslValidTo'),
                            value: formatDateTime(result.ssl.validTo),
                        },
                    ]"
                />
            </InfoCard>

            <InfoCard
                :title="t('siteIntel.siteHealth.securityHeaders')"
                :help-label="t('siteIntel.help.label')"
                :help-text="t('siteIntel.siteHealth.help.securityHeaders')"
            >
                <div class="space-y-1">
                    <p
                        v-for="(headerInfo, headerName) in result.headers"
                        :key="headerName"
                    >
                        <span class="font-medium">{{
                            headerLabel(headerName)
                        }}</span
                        >:
                        <span
                            v-if="headerInfo.present"
                            class="intel-status-positive"
                            >{{ t('siteIntel.common.present') }}</span
                        >
                        <span v-else class="intel-status-danger">{{
                            t('siteIntel.common.missing')
                        }}</span>
                    </p>
                </div>
            </InfoCard>

            <InfoCard
                :title="t('siteIntel.siteHealth.httpChain')"
                :help-label="t('siteIntel.help.label')"
                :help-text="t('siteIntel.siteHealth.help.httpChain')"
            >
                <div class="space-y-2">
                    <div
                        v-for="(step, index) in result.http.chain"
                        :key="`${step.url}-${index}`"
                        class="intel-list-card"
                    >
                        <p class="font-medium break-all">{{ step.url }}</p>
                        <p class="mt-1 text-muted-foreground">
                            HTTP: {{ step.status || '-' }},
                            {{ t('siteIntel.siteHealth.responseTime') }}:
                            {{ step.responseTimeMs }} ms
                        </p>
                        <p
                            v-if="step.location"
                            class="mt-1 break-all text-muted-foreground"
                        >
                            {{ t('siteIntel.siteHealth.location') }}:
                            {{ step.location }}
                        </p>
                    </div>
                </div>
            </InfoCard>

            <InfoCard :title="t('siteIntel.siteHealth.healthSignals')">
                <p
                    v-if="result.score.signals.length === 0"
                    class="intel-status-positive"
                >
                    {{ t('siteIntel.siteHealth.noHealthSignals') }}
                </p>
                <ul
                    v-else
                    class="list-disc space-y-1 pl-4 text-muted-foreground"
                >
                    <li v-for="signal in result.score.signals" :key="signal">
                        {{ signalLabel(signal) }}
                    </li>
                </ul>
            </InfoCard>
        </div>
    </IntelResultPanel>
</template>
