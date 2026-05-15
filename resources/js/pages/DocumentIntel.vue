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
    documentsSummary: {
        total: number;
        failed: number;
        withAuthor: number;
        withSoftware: number;
        withDates: number;
        types: Array<{ extension: string; count: number }>;
        risk: {
            averageScore: number;
            level: 'low' | 'medium' | 'high';
            topReasons: Array<{ key: string; count: number }>;
        };
    };
    documents: Array<{
        url: string;
        extension: string;
        sizeBytes: number | null;
        contentType: string | null;
        lastModified: string | null;
        author: string | null;
        software: string | null;
        createdAt: string | null;
        modifiedAt: string | null;
        artifacts: {
            emails: string[];
            usernames: string[];
            paths: string[];
        };
        risk: {
            score: number;
            level: 'low' | 'medium' | 'high';
            reasons: string[];
        };
        error: string | null;
    }>;
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

const formatBytes = (value: number | null) => {
    if (value === null || value <= 0) {
        return '-';
    }

    if (value < 1024) {
        return `${value} B`;
    }

    if (value < 1024 * 1024) {
        return `${(value / 1024).toFixed(1)} KB`;
    }

    return `${(value / (1024 * 1024)).toFixed(1)} MB`;
};

const documentErrorLabel = (value: string | null) => {
    if (!value) {
        return '-';
    }

    const key = `documentIntel.error.${value}`;
    const translated = t(key);

    return translated === key ? value : translated;
};

const riskLabel = (value: 'low' | 'medium' | 'high') => {
    const key = `documentIntel.risk.${value}`;
    const translated = t(key);

    return translated === key ? value : translated;
};

const riskReasonLabel = (value: string) => {
    const key = `documentIntel.riskReason.${value}`;
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

                <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                    <p class="mb-2 font-semibold">{{ t('documentIntel.documents.title') }}</p>
                    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
                        <div class="rounded border border-border/60 bg-background/40 p-2">
                            <p class="text-muted-foreground">{{ t('documentIntel.documents.total') }}</p>
                            <p class="mt-1 text-sm font-semibold">{{ result.documentsSummary.total }}</p>
                        </div>
                        <div class="rounded border border-border/60 bg-background/40 p-2">
                            <p class="text-muted-foreground">{{ t('documentIntel.documents.failed') }}</p>
                            <p class="mt-1 text-sm font-semibold">{{ result.documentsSummary.failed }}</p>
                        </div>
                        <div class="rounded border border-border/60 bg-background/40 p-2">
                            <p class="text-muted-foreground">{{ t('documentIntel.documents.withAuthor') }}</p>
                            <p class="mt-1 text-sm font-semibold">{{ result.documentsSummary.withAuthor }}</p>
                        </div>
                        <div class="rounded border border-border/60 bg-background/40 p-2">
                            <p class="text-muted-foreground">{{ t('documentIntel.documents.withSoftware') }}</p>
                            <p class="mt-1 text-sm font-semibold">{{ result.documentsSummary.withSoftware }}</p>
                        </div>
                        <div class="rounded border border-border/60 bg-background/40 p-2">
                            <p class="text-muted-foreground">{{ t('documentIntel.documents.withDates') }}</p>
                            <p class="mt-1 text-sm font-semibold">{{ result.documentsSummary.withDates }}</p>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2 text-[11px] text-muted-foreground">
                        <span v-for="type in result.documentsSummary.types" :key="type.extension" class="rounded border border-border/60 px-2 py-0.5">
                            {{ type.extension.toUpperCase() }}: {{ type.count }}
                        </span>
                    </div>
                    <div class="mt-3 rounded border border-border/60 bg-background/40 p-2">
                        <p>{{ t('documentIntel.documents.riskAverage') }}: <span class="font-semibold">{{ result.documentsSummary.risk.averageScore }}</span></p>
                        <p>{{ t('documentIntel.documents.riskLevel') }}: <span class="font-semibold">{{ riskLabel(result.documentsSummary.risk.level) }}</span></p>
                        <p class="mt-1">{{ t('documentIntel.documents.topRiskReasons') }}:</p>
                        <ul class="list-disc pl-4 text-muted-foreground">
                            <li v-for="row in result.documentsSummary.risk.topReasons" :key="row.key">{{ riskReasonLabel(row.key) }} ({{ row.count }})</li>
                        </ul>
                    </div>
                </div>

                <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                    <p class="mb-2 font-semibold">{{ t('documentIntel.documents.list') }}</p>
                    <p v-if="result.documents.length === 0" class="text-muted-foreground">{{ t('documentIntel.documents.empty') }}</p>
                    <div v-else class="space-y-2">
                        <article v-for="(doc, idx) in result.documents" :key="`${doc.url}-${idx}`" class="rounded border border-border/60 bg-background/40 p-2">
                            <a :href="doc.url" target="_blank" rel="noopener noreferrer" class="block break-words text-cyan-300 hover:underline">{{ doc.url }}</a>
                            <div class="mt-1 grid gap-1 sm:grid-cols-2 xl:grid-cols-4 text-muted-foreground">
                                <p>{{ t('documentIntel.documents.extension') }}: {{ doc.extension || '-' }}</p>
                                <p>{{ t('documentIntel.documents.size') }}: {{ formatBytes(doc.sizeBytes) }}</p>
                                <p>{{ t('documentIntel.documents.author') }}: {{ doc.author || '-' }}</p>
                                <p>{{ t('documentIntel.documents.software') }}: {{ doc.software || '-' }}</p>
                                <p>{{ t('documentIntel.documents.createdAt') }}: {{ doc.createdAt || '-' }}</p>
                                <p>{{ t('documentIntel.documents.modifiedAt') }}: {{ doc.modifiedAt || '-' }}</p>
                                <p>{{ t('documentIntel.documents.lastModified') }}: {{ doc.lastModified || '-' }}</p>
                                <p>{{ t('documentIntel.documents.error') }}: {{ documentErrorLabel(doc.error) }}</p>
                                <p>{{ t('documentIntel.documents.riskScore') }}: {{ doc.risk.score }}</p>
                                <p>{{ t('documentIntel.documents.riskLevel') }}: {{ riskLabel(doc.risk.level) }}</p>
                            </div>
                            <div class="mt-2 text-muted-foreground">
                                <p>{{ t('documentIntel.documents.exposedEmails') }}: {{ doc.artifacts.emails.join(', ') || '-' }}</p>
                                <p>{{ t('documentIntel.documents.exposedUsernames') }}: {{ doc.artifacts.usernames.join(', ') || '-' }}</p>
                                <p>{{ t('documentIntel.documents.exposedPaths') }}: {{ doc.artifacts.paths.join(', ') || '-' }}</p>
                                <p>{{ t('documentIntel.documents.riskReasons') }}: {{ doc.risk.reasons.map(riskReasonLabel).join(', ') || '-' }}</p>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
