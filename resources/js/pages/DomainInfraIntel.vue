<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Globe } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import IntelSearchForm from '@/components/ui/IntelSearchForm.vue';
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue';
import MetricCard from '@/components/ui/MetricCard.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import SectionCard from '@/components/ui/SectionCard.vue';
import { useI18n } from '@/composables/useI18n';
import { apiRequest } from '@/lib/api';
import type { DomainInfraResult } from './domain-infra-intel/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Domain & Infra Intel', href: '/domain-infra-intel' }],
    },
});

const { t, locale } = useI18n();
const pageTitle = computed(() => t('domainInfraIntel.headTitle'));
const domain = ref('');
const loading = ref(false);
const error = ref<string | null>(null);
const result = ref<DomainInfraResult | null>(null);
const canSearch = computed(() => domain.value.trim().length >= 3);

const lookup = async () => {
    if (!canSearch.value) {
        error.value = t('domainInfraIntel.errors.domainRequired');
        return;
    }

    loading.value = true;
    error.value = null;
    result.value = null;

    const res = await apiRequest<DomainInfraResult>('/domain-infra-intel/lookup', {
        method: 'GET',
        query: { domain: domain.value.trim(), locale: locale.value },
    });

    if (!res.ok) {
        error.value = res.message ?? t('domainInfraIntel.errors.lookupFailed');
        loading.value = false;
        return;
    }

    result.value = res.data;
    loading.value = false;
};
</script>

<template>
    <Head :title="pageTitle" />

    <div class="flex h-full min-h-0 flex-1 flex-col gap-4 overflow-hidden rounded-xl p-4">
        <IntelSearchPanel>
            <PageHeader :icon="Globe" :title="t('domainInfraIntel.title')" :description="t('domainInfraIntel.description')" />

            <IntelSearchForm
                v-model="domain"
                :label="t('domainInfraIntel.form.domain')"
                :placeholder="t('domainInfraIntel.form.placeholder')"
                :button-text="t('domainInfraIntel.form.search')"
                :loading-text="t('domainInfraIntel.form.searching')"
                :loading="loading"
                :disabled="!canSearch"
                :error="error"
                @submit="lookup"
            />
        </IntelSearchPanel>

        <IntelResultPanel>
            <EmptyState v-if="!result" :text="t('domainInfraIntel.empty')" />

            <div v-else class="telegram-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    <MetricCard :title="t('domainInfraIntel.summary.domain')" :value="result.domain" />
                    <MetricCard :title="t('domainInfraIntel.summary.ips')" :value="result.ips.join(', ') || '-'" />
                    <MetricCard :title="t('domainInfraIntel.summary.asn')" :value="result.asn?.asn || '-'" />
                    <MetricCard :title="t('domainInfraIntel.summary.org')" :value="result.asn?.org || '-'" />
                </div>

                <SectionCard :title="t('domainInfraIntel.rdap.title')">
                    <p class="text-sm">{{ t('domainInfraIntel.rdap.statuses') }}: <span class="text-muted-foreground">{{ result.rdap?.statuses?.join(', ') || '-' }}</span></p>
                    <p class="text-sm">{{ t('domainInfraIntel.rdap.nameservers') }}: <span class="text-muted-foreground">{{ (result.rdap?.nameservers || []).map((n: any) => n.ldhName).filter(Boolean).join(', ') || '-' }}</span></p>
                    <p class="text-sm">{{ t('domainInfraIntel.rdap.neighbors') }}: <span class="text-muted-foreground">{{ result.neighbors.join(', ') || '-' }}</span></p>
                </SectionCard>

                <SectionCard :title="t('domainInfraIntel.certs.title')">
                    <div v-if="result.crtsh.length === 0" class="intel-empty">{{ t('domainInfraIntel.certs.none') }}</div>
                    <div v-else class="space-y-2">
                        <article v-for="(cert, index) in result.crtsh.slice(0, 20)" :key="`${cert.nameValue}-${index}`" class="intel-surface text-xs">
                            <p class="font-medium">{{ cert.nameValue }}</p>
                            <p class="text-muted-foreground">{{ t('domainInfraIntel.certs.issuer') }}: {{ cert.issuer || '-' }}</p>
                            <p class="text-muted-foreground">{{ cert.notBefore || '-' }} -> {{ cert.notAfter || '-' }}</p>
                        </article>
                    </div>
                </SectionCard>
            </div>
        </IntelResultPanel>
    </div>
</template>
