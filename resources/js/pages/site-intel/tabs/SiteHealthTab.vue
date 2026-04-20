<script setup lang="ts">
import { Activity, LoaderCircle } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useSiteHealth } from '../composables/useSiteHealth';

const { t } = useI18n();
const { form, loading, error, result, canCheck, check } = useSiteHealth(t);

const scoreBadgeClass = computed(() => {
    const level = result.value?.score.level;
    if (level === 'high') {
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
</script>

<template>
    <section class="sticky top-0 z-10 shrink-0 rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
        <div class="flex items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <Activity class="h-4 w-4 text-cyan-400" />
                    <span>{{ t('siteIntel.siteHealth.title') }}</span>
                    <span class="group relative inline-flex">
                        <span
                            class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                            :aria-label="t('siteIntel.help.label')"
                        >
                            ?
                        </span>
                        <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                            {{ t('siteIntel.siteHealth.help.overview') }}
                        </span>
                    </span>
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ t('siteIntel.siteHealth.description') }}
                </p>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-end gap-3">
            <label class="block min-w-0 flex-1">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('siteIntel.siteHealth.target') }}</span>
                <input
                    v-model="form.target"
                    type="text"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    :placeholder="t('siteIntel.siteHealth.placeholder')"
                    @keydown.enter.prevent="check"
                />
            </label>

            <button
                :disabled="loading || !canCheck"
                class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                @click="check"
            >
                <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                <span>{{ loading ? t('siteIntel.siteHealth.checking') : t('siteIntel.siteHealth.check') }}</span>
            </button>
        </div>

        <p v-if="error" class="mt-3 text-sm text-destructive">{{ error }}</p>
    </section>

    <section class="flex min-h-0 flex-1 flex-col rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
        <div v-if="!result" class="rounded-md border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
            {{ t('siteIntel.siteHealth.empty') }}
        </div>

        <div v-else class="telegram-scroll min-h-0 flex-1 overflow-y-auto space-y-3 pr-1">
            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="text-xs text-muted-foreground">{{ t('siteIntel.common.checkedAt') }}</p>
                    <p class="mt-1 text-sm font-semibold">{{ formatDateTime(result.checkedAt) }}</p>
                </div>
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="text-xs text-muted-foreground">{{ t('siteIntel.siteHealth.finalStatus') }}</p>
                    <p class="mt-1 text-xl font-semibold">{{ result.http.finalStatus || '-' }}</p>
                </div>
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="text-xs text-muted-foreground">{{ t('siteIntel.siteHealth.redirects') }}</p>
                    <p class="mt-1 text-xl font-semibold">{{ result.http.totalRedirects }}</p>
                </div>
                <div class="rounded-lg border p-3" :class="scoreBadgeClass">
                    <p class="text-xs">{{ t('siteIntel.siteHealth.score') }}</p>
                    <p class="mt-1 text-xl font-semibold">{{ result.score.value }}</p>
                </div>
            </div>

            <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                <p class="font-semibold">{{ t('siteIntel.siteHealth.finalUrl') }}</p>
                <p class="mt-1 break-all text-muted-foreground">{{ result.http.finalUrl }}</p>
            </div>

            <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                <div class="mb-2 flex items-center gap-2">
                    <p class="font-semibold">{{ t('siteIntel.siteHealth.dns') }}</p>
                    <span class="group relative inline-flex">
                        <span
                            class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                            :aria-label="t('siteIntel.help.label')"
                        >
                            ?
                        </span>
                        <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                            {{ t('siteIntel.siteHealth.help.dns') }}
                        </span>
                    </span>
                </div>
                <p>{{ t('siteIntel.siteHealth.aRecords') }}: {{ result.dns.a.join(', ') || '-' }}</p>
                <p class="mt-1">{{ t('siteIntel.siteHealth.aaaaRecords') }}: {{ result.dns.aaaa.join(', ') || '-' }}</p>
            </div>

            <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                <div class="mb-2 flex items-center gap-2">
                    <p class="font-semibold">{{ t('siteIntel.siteHealth.ssl') }}</p>
                    <span class="group relative inline-flex">
                        <span
                            class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                            :aria-label="t('siteIntel.help.label')"
                        >
                            ?
                        </span>
                        <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                            {{ t('siteIntel.siteHealth.help.ssl') }}
                        </span>
                    </span>
                </div>
                <p>{{ t('siteIntel.siteHealth.sslAvailable') }}: {{ result.ssl.available ? t('siteIntel.common.yes') : t('siteIntel.common.no') }}</p>
                <p class="mt-1">{{ t('siteIntel.siteHealth.sslIssuer') }}: {{ result.ssl.issuer || '-' }}</p>
                <p class="mt-1">{{ t('siteIntel.siteHealth.sslSubject') }}: {{ result.ssl.subject || '-' }}</p>
                <p class="mt-1">{{ t('siteIntel.siteHealth.sslValidTo') }}: {{ formatDateTime(result.ssl.validTo) }}</p>
            </div>

            <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                <div class="mb-2 flex items-center gap-2">
                    <p class="font-semibold">{{ t('siteIntel.siteHealth.securityHeaders') }}</p>
                    <span class="group relative inline-flex">
                        <span
                            class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                            :aria-label="t('siteIntel.help.label')"
                        >
                            ?
                        </span>
                        <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                            {{ t('siteIntel.siteHealth.help.securityHeaders') }}
                        </span>
                    </span>
                </div>
                <div class="space-y-1">
                    <p v-for="(headerInfo, headerName) in result.headers" :key="headerName">
                        <span class="font-medium">{{ headerLabel(headerName) }}</span>:
                        <span v-if="headerInfo.present" class="text-emerald-300">{{ t('siteIntel.common.present') }}</span>
                        <span v-else class="text-rose-300">{{ t('siteIntel.common.missing') }}</span>
                    </p>
                </div>
            </div>

            <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                <div class="mb-2 flex items-center gap-2">
                    <p class="font-semibold">{{ t('siteIntel.siteHealth.httpChain') }}</p>
                    <span class="group relative inline-flex">
                        <span
                            class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                            :aria-label="t('siteIntel.help.label')"
                        >
                            ?
                        </span>
                        <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                            {{ t('siteIntel.siteHealth.help.httpChain') }}
                        </span>
                    </span>
                </div>
                <div class="space-y-2">
                    <div
                        v-for="(step, index) in result.http.chain"
                        :key="`${step.url}-${index}`"
                        class="rounded-md border border-border/50 bg-background/40 p-2"
                    >
                        <p class="break-all font-medium">{{ step.url }}</p>
                        <p class="mt-1 text-muted-foreground">
                            HTTP: {{ step.status || '-' }}, {{ t('siteIntel.siteHealth.responseTime') }}: {{ step.responseTimeMs }} ms
                        </p>
                        <p v-if="step.location" class="mt-1 break-all text-muted-foreground">
                            {{ t('siteIntel.siteHealth.location') }}: {{ step.location }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs">
                <p class="mb-2 font-semibold">{{ t('siteIntel.siteHealth.healthSignals') }}</p>
                <p v-if="result.score.signals.length === 0" class="text-emerald-300">{{ t('siteIntel.siteHealth.noHealthSignals') }}</p>
                <ul v-else class="list-disc space-y-1 pl-4 text-muted-foreground">
                    <li v-for="signal in result.score.signals" :key="signal">{{ signalLabel(signal) }}</li>
                </ul>
            </div>
        </div>
    </section>
</template>
