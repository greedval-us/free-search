<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { FileSearch, LoaderCircle } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import { useI18n } from '@/composables/useI18n';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Document Intel',
                href: '/document-intel',
            },
        ],
    },
});

type DocumentIntelResult = {
    query: string;
    domain: string | null;
    checkedAt: string;
    signals: string[];
    recommendations: string[];
    documentPivots: Array<{ label: string; url: string }>;
    domainIntel: {
        available: boolean;
        dns: null | { aCount: number; mxCount: number; hasSpf: boolean; hasDmarc: boolean };
        whois: null | { available: boolean; registrar: string | null; createdAt: string | null; expiresAt: string | null };
    };
};

const { t, locale } = useI18n();
const pageTitle = computed(() => t('documentIntel.headTitle'));

const form = reactive({ query: '' });
const loading = ref(false);
const error = ref<string | null>(null);
const result = ref<DocumentIntelResult | null>(null);

const canLookup = computed(() => form.query.trim().length >= 2);

const formatDateTime = (value: string | null) => {
    if (!value) return '-';

    return new Date(value).toLocaleString();
};

const signalLabel = (value: string) => {
    const key = `documentIntel.signal.${value}`;
    const translated = t(key);

    return translated === key ? value : translated;
};

const recommendationLabel = (value: string) => {
    const key = `documentIntel.recommendation.${value}`;
    const translated = t(key);

    return translated === key ? value : translated;
};

const linkLabel = (value: string) => {
    const key = `documentIntel.links.${value}`;
    const translated = t(key);

    return translated === key ? value : translated;
};

const lookup = async () => {
    if (!canLookup.value) {
        error.value = t('documentIntel.errors.queryRequired');

        return;
    }

    loading.value = true;
    error.value = null;
    result.value = null;

    try {
        const query = new URLSearchParams({ query: form.query.trim(), locale: locale.value });
        const response = await fetch(`/document-intel/lookup?${query.toString()}`, {
            method: 'GET',
            headers: { Accept: 'application/json' },
        });

        const payload = await response.json();

        if (!response.ok || !payload?.ok) {
            error.value = payload?.message ?? t('documentIntel.errors.lookupFailed');

            return;
        }

        result.value = payload.data as DocumentIntelResult;
    } catch (exception) {
        error.value = exception instanceof Error ? exception.message : t('documentIntel.errors.lookupFailed');
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <Head :title="pageTitle" />

    <div class="flex h-full min-h-0 flex-1 flex-col gap-4 overflow-hidden rounded-xl p-4">
        <section class="sticky top-0 z-10 shrink-0 rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
            <div class="flex items-center justify-between gap-3">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-sm font-semibold">
                        <FileSearch class="h-4 w-4 text-cyan-400" />
                        <span>{{ t('documentIntel.title') }}</span>
                        <span class="group relative inline-flex">
                            <span class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground" :aria-label="t('documentIntel.help.label')">?</span>
                            <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">{{ t('documentIntel.help.overview') }}</span>
                        </span>
                    </div>
                    <p class="text-xs text-muted-foreground">{{ t('documentIntel.description') }}</p>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-end gap-3">
                <label class="block min-w-0 flex-1">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('documentIntel.query') }}</span>
                    <input v-model="form.query" type="text" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" :placeholder="t('documentIntel.placeholder')" @keydown.enter.prevent="lookup" />
                </label>
                <button :disabled="loading || !canLookup" class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60" @click="lookup">
                    <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                    <span>{{ loading ? t('documentIntel.searching') : t('documentIntel.search') }}</span>
                </button>
            </div>

            <p v-if="error" class="mt-3 text-sm text-destructive">{{ error }}</p>
        </section>

        <section class="flex min-h-0 flex-1 flex-col rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
            <div v-if="!result" class="rounded-md border border-dashed border-border p-6 text-center text-sm text-muted-foreground">{{ t('documentIntel.empty') }}</div>

            <div v-else class="telegram-scroll min-h-0 flex-1 overflow-y-auto space-y-3 pr-1">
                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('documentIntel.checkedAt') }}</p>
                        <p class="mt-1 text-sm font-semibold">{{ formatDateTime(result.checkedAt) }}</p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('documentIntel.detectedDomain') }}</p>
                        <p class="mt-1 text-sm font-semibold">{{ result.domain ?? '-' }}</p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('documentIntel.signalCount') }}</p>
                        <p class="mt-1 text-xl font-semibold">{{ result.signals.length }}</p>
                    </div>
                    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                        <p class="text-xs text-muted-foreground">{{ t('documentIntel.pivotCount') }}</p>
                        <p class="mt-1 text-xl font-semibold">{{ result.documentPivots.length }}</p>
                    </div>
                </div>

                <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                    <p class="mb-2 font-semibold">{{ t('documentIntel.signals') }}</p>
                    <ul class="list-disc space-y-1 pl-4 text-muted-foreground">
                        <li v-for="signal in result.signals" :key="signal">{{ signalLabel(signal) }}</li>
                    </ul>
                </div>

                <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                    <p class="mb-2 font-semibold">{{ t('documentIntel.recommendations') }}</p>
                    <ul class="list-disc space-y-1 pl-4 text-muted-foreground">
                        <li v-for="recommendation in result.recommendations" :key="recommendation">{{ recommendationLabel(recommendation) }}</li>
                    </ul>
                </div>

                <div v-if="result.domainIntel.available" class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs space-y-1">
                    <p class="mb-2 font-semibold">{{ t('documentIntel.domainIntel') }}</p>
                    <p>DNS: A {{ result.domainIntel.dns?.aCount ?? 0 }}, MX {{ result.domainIntel.dns?.mxCount ?? 0 }}</p>
                    <p>SPF: {{ result.domainIntel.dns?.hasSpf ? t('common.yes') : t('common.no') }}, DMARC: {{ result.domainIntel.dns?.hasDmarc ? t('common.yes') : t('common.no') }}</p>
                    <p>WHOIS: {{ result.domainIntel.whois?.available ? t('documentIntel.available') : t('documentIntel.unavailable') }}</p>
                    <p>{{ t('documentIntel.registrar') }}: {{ result.domainIntel.whois?.registrar ?? '-' }}</p>
                    <p>{{ t('documentIntel.createdAt') }}: {{ formatDateTime(result.domainIntel.whois?.createdAt ?? null) }}</p>
                    <p>{{ t('documentIntel.expiresAt') }}: {{ formatDateTime(result.domainIntel.whois?.expiresAt ?? null) }}</p>
                </div>

                <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                    <p class="mb-2 font-semibold">{{ t('documentIntel.pivots') }}</p>
                    <p class="mb-2 text-muted-foreground">{{ t('documentIntel.help.pivots') }}</p>
                    <div class="space-y-1">
                        <a v-for="pivot in result.documentPivots" :key="pivot.url" class="block break-words text-cyan-300 hover:underline" :href="pivot.url" target="_blank" rel="noopener noreferrer">{{ linkLabel(pivot.label) }}</a>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
