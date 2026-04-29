<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { BarChart3, ExternalLink, LoaderCircle, MailSearch, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useEmailIntelLookup } from './email-intel/composables/useEmailIntelLookup';

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
const { form, loading, error, result, canLookup, lookup } = useEmailIntelLookup(t, locale);
const activeTab = ref<'search' | 'analytics'>('search');

const pageTitle = computed(() => t('emailIntel.headTitle'));

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
</script>

<template>
    <Head :title="pageTitle" />

    <div class="flex h-full min-h-0 flex-1 flex-col gap-4 overflow-hidden rounded-xl p-4">
        <div class="flex items-center justify-center gap-1 rounded-lg bg-slate-800/80 p-1">
            <button
                type="button"
                @click="activeTab = 'search'"
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
                @click="activeTab = 'analytics'"
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
        </div>

        <section class="sticky top-0 z-10 shrink-0 rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
            <div class="flex items-center justify-between gap-3">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-sm font-semibold">
                        <MailSearch class="h-4 w-4 text-cyan-400" />
                        <span>{{ t('emailIntel.lookup.title') }}</span>
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
                    <p class="text-xs text-muted-foreground">{{ t('emailIntel.lookup.description') }}</p>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-end gap-3">
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
            </div>

            <p v-if="error" class="mt-3 text-sm text-destructive">{{ error }}</p>
        </section>

        <section class="flex min-h-0 flex-1 flex-col rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
            <div v-if="!result" class="rounded-md border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
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
            </div>
        </section>
    </div>
</template>
