<script setup lang="ts">
import {
    BarChart3,
    ChevronDown,
    ChevronUp,
    Download,
    ExternalLink,
    FileText,
    RefreshCw,
    Tags,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import MetricCard from '@/components/ui/MetricCard.vue';
import SectionCard from '@/components/ui/SectionCard.vue';
import { useI18n } from '@/composables/useI18n';
import { apiRequest } from '@/lib/api';
import type { MastodonAccount, MastodonAnalyticsPayload, MastodonHashtag } from '../types';

type AnalyticsMode = 'account' | 'hashtag';

const { t, locale } = useI18n();

const form = ref({
    mode: 'account' as AnalyticsMode,
    target: '',
    limit: 10,
    pages: 3,
    dateFrom: '',
    dateTo: '',
    resolve: true,
});

const loading = ref(false);
const error = ref<string | null>(null);
const result = ref<MastodonAnalyticsPayload | null>(null);
const panelCollapsed = ref(false);

const canRun = computed(() => form.value.target.trim().length > 0);
const canUseReportActions = computed(() => result.value !== null && canRun.value);
const isAccountMode = computed(() => form.value.mode === 'account');
const accountProfile = computed(() =>
    result.value?.meta.mode === 'account' && result.value.profile
        ? (result.value.profile as MastodonAccount)
        : null
);
const hashtagProfile = computed(() =>
    result.value?.meta.mode === 'hashtag' && result.value.profile
        ? (result.value.profile as MastodonHashtag)
        : null
);

const fmt = (value: number) => new Intl.NumberFormat().format(value ?? 0);
const formatDate = (value: string) =>
    value ? new Date(value).toLocaleString() : '-';

const clampNumber = (value: number, min: number, max: number, fallback: number) => {
    if (!Number.isFinite(value)) {
        return fallback;
    }

    return Math.min(max, Math.max(min, Math.trunc(value)));
};

const runAnalytics = async () => {
    if (!canRun.value) {
        return;
    }

    loading.value = true;
    error.value = null;

    const response = await apiRequest<MastodonAnalyticsPayload>(
        '/mastodon/analytics/summary',
        {
            query: {
                mode: form.value.mode,
                target: form.value.target,
                limit: form.value.limit,
                pages: form.value.pages,
                dateFrom: form.value.dateFrom || undefined,
                dateTo: form.value.dateTo || undefined,
                resolve: form.value.resolve ? 'true' : 'false',
                locale: locale.value,
            },
        }
    );

    loading.value = false;

    if (!response.ok) {
        error.value = response.message ?? t('mastodon.analytics.errors.requestFailed');

        return;
    }

    result.value = response.data;
};

const reportUrl = computed(() => {
    const query = new URLSearchParams({
        mode: form.value.mode,
        target: form.value.target.trim(),
        limit: String(form.value.limit),
        pages: String(form.value.pages),
        resolve: form.value.resolve ? '1' : '0',
        locale: locale.value,
    });

    if (form.value.dateFrom) {
        query.set('dateFrom', form.value.dateFrom);
    }

    if (form.value.dateTo) {
        query.set('dateTo', form.value.dateTo);
    }

    return `/mastodon/analytics/report?${query.toString()}`;
});

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
</script>

<template>
    <section class="intel-panel-strong sticky top-0 z-10 shrink-0">
        <div class="flex items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <BarChart3 class="h-4 w-4 text-cyan-400" />
                    <span>{{ t('mastodon.analytics.title') }}</span>
                    <HelpTooltip
                        :label="t('mastodon.help.label')"
                        :text="t('mastodon.analytics.hint')"
                    />
                </div>
                <p class="text-xs text-muted-foreground">
                    {{
                        panelCollapsed
                            ? t('mastodon.analytics.collapsed')
                            : t('mastodon.analytics.hint')
                    }}
                </p>
            </div>

            <button
                type="button"
                class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-input text-sm text-foreground transition hover:bg-accent"
                @click="panelCollapsed = !panelCollapsed"
            >
                <ChevronDown v-if="panelCollapsed" class="h-4 w-4" />
                <ChevronUp v-else class="h-4 w-4" />
            </button>
        </div>

        <div v-if="!panelCollapsed" class="mt-3 space-y-2.5">
            <div class="grid gap-2.5 md:grid-cols-2 xl:grid-cols-12">
                <label class="block min-w-0 xl:col-span-2">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                        {{ t('mastodon.analytics.mode') }}
                    </span>
                    <select
                        v-model="form.mode"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="account">
                            {{ t('mastodon.analytics.modes.account') }}
                        </option>
                        <option value="hashtag">
                            {{ t('mastodon.analytics.modes.hashtag') }}
                        </option>
                    </select>
                </label>

                <label class="block min-w-0 xl:col-span-4">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                        {{ t('mastodon.analytics.target') }}
                    </span>
                    <input
                        v-model="form.target"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="
                            isAccountMode
                                ? t('mastodon.analytics.accountPlaceholder')
                                : t('mastodon.analytics.hashtagPlaceholder')
                        "
                    />
                </label>

                <label class="block min-w-0 xl:col-span-2">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                        {{ t('mastodon.analytics.limit') }}
                    </span>
                    <input
                        v-model.number="form.limit"
                        type="number"
                        min="1"
                        max="20"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        @change="form.limit = clampNumber(form.limit, 1, 20, 10)"
                    />
                </label>

                <label class="block min-w-0 xl:col-span-2">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                        {{ t('mastodon.analytics.pages') }}
                    </span>
                    <input
                        v-model.number="form.pages"
                        type="number"
                        min="1"
                        max="5"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        @change="form.pages = clampNumber(form.pages, 1, 5, 3)"
                    />
                </label>

                <label class="block min-w-0 xl:col-span-1">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                        {{ t('mastodon.analytics.dateFrom') }}
                    </span>
                    <input
                        v-model="form.dateFrom"
                        type="date"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    />
                </label>

                <label class="block min-w-0 xl:col-span-1">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                        {{ t('mastodon.analytics.dateTo') }}
                    </span>
                    <input
                        v-model="form.dateTo"
                        type="date"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    />
                </label>
            </div>

            <div
                class="flex flex-wrap items-end justify-between gap-2.5 rounded-md border border-border/70 bg-background/60 p-2.5"
            >
                <label class="block min-w-0">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                        {{ t('mastodon.search.resolveRemote') }}
                    </span>
                    <span
                        class="flex h-10 items-center gap-3 rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <input v-model="form.resolve" type="checkbox" class="h-4 w-4" />
                        <span>{{ t('mastodon.search.resolveRemote') }}</span>
                    </span>
                </label>

                <div class="flex w-full flex-wrap justify-end gap-2 md:w-auto">
                    <button
                        type="button"
                        :disabled="loading || !canRun"
                        class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                        @click="runAnalytics"
                    >
                        <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
                        {{
                            loading
                                ? t('mastodon.analytics.loading')
                                : t('mastodon.analytics.refresh')
                        }}
                    </button>

                    <button
                        type="button"
                        :disabled="!canUseReportActions"
                        class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="openReport"
                    >
                        <FileText class="h-4 w-4" />
                        {{ t('mastodon.analytics.report') }}
                    </button>

                    <button
                        type="button"
                        :disabled="!canUseReportActions"
                        class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                        @click="downloadReport"
                    >
                        <Download class="h-4 w-4" />
                        {{ t('mastodon.analytics.downloadReport') }}
                    </button>
                </div>
            </div>

            <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
        </div>
    </section>

    <IntelResultPanel>
        <div class="intel-scroll min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
            <div
                v-if="loading && !result"
                class="rounded-xl border border-sidebar-border/80 bg-card/70 p-6 text-center text-sm text-muted-foreground shadow-xl backdrop-blur"
            >
                {{ t('mastodon.analytics.loading') }}
            </div>

            <div
                v-else-if="!result"
                class="rounded-xl border border-sidebar-border/80 bg-card/70 p-6 text-center text-sm text-muted-foreground shadow-xl backdrop-blur"
            >
                <p>{{ t('mastodon.analytics.empty') }}</p>
                <button
                    type="button"
                    :disabled="!canRun || loading"
                    class="mt-4 inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                    @click="runAnalytics"
                >
                    <RefreshCw class="h-4 w-4" />
                    {{ t('mastodon.analytics.refresh') }}
                </button>
            </div>

            <template v-else>
                <SectionCard v-if="accountProfile" :title="t('mastodon.analytics.accountProfile')">
                    <div class="flex flex-col gap-4 md:flex-row">
                        <img
                            v-if="accountProfile.avatar"
                            :src="accountProfile.avatar"
                            :alt="accountProfile.displayName || accountProfile.acct"
                            class="h-20 w-20 rounded-full object-cover"
                            loading="lazy"
                        />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-semibold">
                                    {{ accountProfile.displayName || accountProfile.username }}
                                </h2>
                                <a
                                    v-if="accountProfile.url"
                                    :href="accountProfile.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 rounded-md border border-input px-2 py-1 text-xs text-primary hover:bg-accent"
                                >
                                    <ExternalLink class="h-3 w-3" />
                                    {{ t('mastodon.common.openProfile') }}
                                </a>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                @{{ accountProfile.acct }} ·
                                {{ accountProfile.instanceDomain || '-' }} ·
                                {{ formatDate(accountProfile.createdAt) }}
                            </p>
                            <p class="mt-2 text-xs leading-relaxed text-muted-foreground">
                                {{ accountProfile.note || t('mastodon.search.noBio') }}
                            </p>
                        </div>
                    </div>
                </SectionCard>

                <SectionCard v-if="hashtagProfile" :title="t('mastodon.analytics.hashtagProfile')">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-base font-semibold">#{{ hashtagProfile.name }}</h2>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ t('mastodon.metrics.historyPoints') }}:
                                {{ hashtagProfile.history.length }}
                            </p>
                        </div>
                        <a
                            v-if="hashtagProfile.url"
                            :href="hashtagProfile.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 rounded-md border border-input px-2 py-1 text-xs text-primary hover:bg-accent"
                        >
                            <ExternalLink class="h-3 w-3" />
                            {{ t('mastodon.common.openTag') }}
                        </a>
                    </div>
                    <div
                        v-if="hashtagProfile.history.length > 0"
                        class="mt-3 grid gap-2 md:grid-cols-2 xl:grid-cols-4"
                    >
                        <div
                            v-for="point in hashtagProfile.history"
                            :key="`${hashtagProfile.name}-${point.day}`"
                            class="rounded-md border border-border/70 bg-card/60 p-2 text-xs"
                        >
                            <div class="font-medium text-foreground">{{ point.day }}</div>
                            <div class="mt-1 text-muted-foreground">
                                {{ t('mastodon.metrics.uses') }}: {{ point.uses }}
                            </div>
                            <div class="text-muted-foreground">
                                {{ t('mastodon.metrics.accounts') }}: {{ point.accounts }}
                            </div>
                        </div>
                    </div>
                </SectionCard>

                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-6">
                    <MetricCard :title="t('mastodon.analytics.metrics.posts')" :value="fmt(result.summary.postsCount)" />
                    <MetricCard :title="t('mastodon.analytics.metrics.accounts')" :value="fmt(result.summary.uniqueAccountsCount)" />
                    <MetricCard :title="t('mastodon.analytics.metrics.instances')" :value="fmt(result.summary.uniqueInstancesCount)" />
                    <MetricCard :title="t('mastodon.analytics.metrics.languages')" :value="fmt(result.summary.uniqueLanguagesCount)" />
                    <MetricCard :title="t('mastodon.analytics.metrics.mediaPosts')" :value="fmt(result.summary.postsWithMediaCount)" />
                    <MetricCard :title="t('mastodon.analytics.metrics.linkPosts')" :value="fmt(result.summary.postsWithLinksCount)" />
                </div>

                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-6">
                    <MetricCard :title="t('mastodon.analytics.metrics.replyPosts')" :value="fmt(result.summary.replyPostsCount)" />
                    <MetricCard :title="t('mastodon.analytics.metrics.boostPosts')" :value="fmt(result.summary.boostPostsCount)" />
                    <MetricCard :title="t('mastodon.analytics.metrics.sensitivePosts')" :value="fmt(result.summary.sensitivePostsCount)" />
                    <MetricCard :title="t('mastodon.analytics.metrics.repliesTotal')" :value="fmt(result.summary.totalReplies)" />
                    <MetricCard :title="t('mastodon.analytics.metrics.reblogsTotal')" :value="fmt(result.summary.totalReblogs)" />
                    <MetricCard :title="t('mastodon.analytics.metrics.favouritesTotal')" :value="fmt(result.summary.totalFavourites)" />
                </div>

                <SectionCard :title="t('mastodon.analytics.sample')">
                    <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-md border border-border/70 bg-background/70 p-3 text-xs">
                            <div class="text-muted-foreground">{{ t('mastodon.analytics.sampleTarget') }}</div>
                            <div class="mt-1 font-semibold text-foreground">{{ result.meta.resolvedTarget }}</div>
                        </div>
                        <div class="rounded-md border border-border/70 bg-background/70 p-3 text-xs">
                            <div class="text-muted-foreground">{{ t('mastodon.analytics.sampledPosts') }}</div>
                            <div class="mt-1 font-semibold text-foreground">{{ fmt(result.meta.sampledPosts) }}</div>
                        </div>
                        <div class="rounded-md border border-border/70 bg-background/70 p-3 text-xs">
                            <div class="text-muted-foreground">{{ t('mastodon.analytics.pagesRequested') }}</div>
                            <div class="mt-1 font-semibold text-foreground">{{ fmt(result.meta.pagesRequested) }}</div>
                        </div>
                        <div class="rounded-md border border-border/70 bg-background/70 p-3 text-xs">
                            <div class="text-muted-foreground">{{ t('mastodon.analytics.pagesLoaded') }}</div>
                            <div class="mt-1 font-semibold text-foreground">{{ fmt(result.meta.pagesLoaded) }}</div>
                        </div>
                    </div>
                </SectionCard>

                <section class="grid gap-4 xl:grid-cols-2">
                    <SectionCard :title="t('mastodon.analytics.timeline')">
                        <div v-if="result.timeline.length > 0" class="space-y-2">
                            <div
                                v-for="point in result.timeline"
                                :key="point.day"
                                class="rounded-md border border-border/70 bg-background/70 p-3 text-xs"
                            >
                                <div class="font-medium text-foreground">{{ point.day }}</div>
                                <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-muted-foreground">
                                    <span>{{ t('mastodon.analytics.metrics.posts') }}: {{ point.posts }}</span>
                                    <span>{{ t('mastodon.analytics.metrics.mediaPosts') }}: {{ point.postsWithMedia }}</span>
                                    <span>{{ t('mastodon.analytics.metrics.linkPosts') }}: {{ point.postsWithLinks }}</span>
                                    <span>{{ t('mastodon.metrics.replies') }}: {{ point.replies }}</span>
                                    <span>{{ t('mastodon.metrics.reblogs') }}: {{ point.reblogs }}</span>
                                    <span>{{ t('mastodon.metrics.favourites') }}: {{ point.favourites }}</span>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-xs text-muted-foreground">
                            {{ t('mastodon.analytics.noTimeline') }}
                        </p>
                    </SectionCard>

                    <SectionCard :title="t('mastodon.analytics.topPosts')">
                        <div v-if="result.topPosts.length > 0" class="space-y-2">
                            <article
                                v-for="status in result.topPosts"
                                :key="status.id"
                                class="rounded-md border border-border/70 bg-background/70 p-3"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="truncate text-xs font-semibold text-foreground">
                                            {{ status.account.displayName || status.account.acct }}
                                        </div>
                                        <div class="truncate text-[11px] text-muted-foreground">
                                            @{{ status.account.acct }} · {{ formatDate(status.createdAt) }}
                                        </div>
                                    </div>
                                    <a
                                        v-if="status.url"
                                        :href="status.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1 rounded-md border border-input px-2 py-1 text-[11px] text-primary hover:bg-accent"
                                    >
                                        <ExternalLink class="h-3 w-3" />
                                        {{ t('mastodon.common.open') }}
                                    </a>
                                </div>
                                <p class="mt-2 text-xs leading-relaxed text-foreground">
                                    {{ status.content || t('mastodon.search.noText') }}
                                </p>
                                <div class="mt-2 flex flex-wrap gap-3 text-[11px] text-muted-foreground">
                                    <span>{{ t('mastodon.metrics.replies') }}: {{ status.repliesCount }}</span>
                                    <span>{{ t('mastodon.metrics.reblogs') }}: {{ status.reblogsCount }}</span>
                                    <span>{{ t('mastodon.metrics.favourites') }}: {{ status.favouritesCount }}</span>
                                </div>
                            </article>
                        </div>
                        <p v-else class="text-xs text-muted-foreground">
                            {{ t('mastodon.analytics.noTopPosts') }}
                        </p>
                    </SectionCard>
                </section>

                <section class="grid gap-4 xl:grid-cols-2">
                    <SectionCard :title="t('mastodon.analytics.topDomains')">
                        <div v-if="result.topDomains.length > 0" class="flex flex-wrap gap-2">
                            <span
                                v-for="domain in result.topDomains"
                                :key="domain.domain"
                                class="rounded-full border border-input px-2 py-1 text-xs"
                            >
                                {{ domain.domain }} · {{ domain.count }}
                            </span>
                        </div>
                        <p v-else class="text-xs text-muted-foreground">
                            {{ t('mastodon.analytics.noDomains') }}
                        </p>
                    </SectionCard>

                    <SectionCard :title="t('mastodon.analytics.topTags')">
                        <div v-if="result.topTags.length > 0" class="flex flex-wrap gap-2">
                            <span
                                v-for="tag in result.topTags"
                                :key="tag.tag"
                                class="inline-flex items-center gap-1 rounded-full border border-input px-2 py-1 text-xs"
                            >
                                <Tags class="h-3 w-3" />
                                #{{ tag.tag }} · {{ tag.count }}
                            </span>
                        </div>
                        <p v-else class="text-xs text-muted-foreground">
                            {{ t('mastodon.analytics.noTags') }}
                        </p>
                    </SectionCard>
                </section>

                <section class="grid gap-4 xl:grid-cols-3">
                    <SectionCard :title="t('mastodon.analytics.topAccounts')">
                        <div v-if="result.topAccounts.length > 0" class="space-y-2">
                            <div
                                v-for="account in result.topAccounts"
                                :key="account.id"
                                class="rounded-md border border-border/70 bg-background/70 p-3 text-xs"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="truncate font-medium text-foreground">
                                            {{ account.displayName || account.username }}
                                        </div>
                                        <div class="truncate text-muted-foreground">
                                            @{{ account.acct }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-foreground">{{ account.count }}</div>
                                        <div class="text-muted-foreground">{{ t('mastodon.analytics.usesInSample') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-xs text-muted-foreground">
                            {{ t('mastodon.analytics.noAccounts') }}
                        </p>
                    </SectionCard>

                    <SectionCard :title="t('mastodon.analytics.topMentions')">
                        <div v-if="result.topMentions.length > 0" class="space-y-2">
                            <div
                                v-for="mention in result.topMentions"
                                :key="mention.acct"
                                class="rounded-md border border-border/70 bg-background/70 p-3 text-xs"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="truncate font-medium text-foreground">
                                            @{{ mention.acct }}
                                        </div>
                                        <a
                                            v-if="mention.url"
                                            :href="mention.url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="mt-1 inline-flex items-center gap-1 text-primary hover:underline"
                                        >
                                            <ExternalLink class="h-3 w-3" />
                                            {{ t('mastodon.common.openProfile') }}
                                        </a>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-foreground">{{ mention.count }}</div>
                                        <div class="text-muted-foreground">{{ t('mastodon.analytics.mentionsInSample') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-xs text-muted-foreground">
                            {{ t('mastodon.analytics.noMentions') }}
                        </p>
                    </SectionCard>

                    <SectionCard :title="t('mastodon.analytics.topLanguages')">
                        <div v-if="result.topLanguages.length > 0" class="space-y-2">
                            <div
                                v-for="language in result.topLanguages"
                                :key="language.language"
                                class="rounded-md border border-border/70 bg-background/70 p-3 text-xs"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div class="font-medium text-foreground">
                                        {{ language.language || '-' }}
                                    </div>
                                    <div class="font-semibold text-foreground">
                                        {{ language.count }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-xs text-muted-foreground">
                            {{ t('mastodon.analytics.noLanguages') }}
                        </p>
                    </SectionCard>
                </section>
            </template>
        </div>
    </IntelResultPanel>
</template>
