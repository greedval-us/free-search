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
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import MetricCard from '@/components/ui/MetricCard.vue';
import SectionCard from '@/components/ui/SectionCard.vue';
import { useI18n } from '@/composables/useI18n';
import BlueskyActorProfileCard from '../components/BlueskyActorProfileCard.vue';
import BlueskyCompactPostCard from '../components/BlueskyCompactPostCard.vue';
import { useBlueskyAnalytics } from '../composables/useBlueskyAnalytics';

const { t, locale } = useI18n();

const {
    limitMax,
    form,
    loading,
    error,
    result,
    panelCollapsed,
    canRun,
    canUseReportActions,
    isAccountMode,
    accountProfile,
    hashtagProfile,
    clampLimit,
    clampPages,
    runAnalytics,
    openReport,
    downloadReport,
} = useBlueskyAnalytics(t, locale);

const fmt = (value: number) => new Intl.NumberFormat().format(value ?? 0);
const formatDate = (value: string) => (value ? new Date(value).toLocaleString() : '-');
</script>

<template>
    <section class="intel-panel-strong sticky top-0 z-10 shrink-0">
        <div class="flex items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <BarChart3 class="h-4 w-4 text-cyan-400" />
                    <span>{{ t('bluesky.analytics.title') }}</span>
                    <HelpTooltip
                        :label="t('bluesky.help.label')"
                        :text="t('bluesky.analytics.hint')"
                    />
                </div>
                <p class="text-xs text-muted-foreground">
                    {{
                        panelCollapsed
                            ? t('bluesky.analytics.collapsed')
                            : t('bluesky.analytics.hint')
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
                        {{ t('bluesky.analytics.mode') }}
                    </span>
                    <select
                        v-model="form.mode"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="account">
                            {{ t('bluesky.analytics.modes.account') }}
                        </option>
                        <option value="hashtag">
                            {{ t('bluesky.analytics.modes.hashtag') }}
                        </option>
                    </select>
                </label>

                <label class="block min-w-0 xl:col-span-4">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                        {{ t('bluesky.analytics.target') }}
                    </span>
                    <input
                        v-model="form.target"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="
                            isAccountMode
                                ? t('bluesky.analytics.accountPlaceholder')
                                : t('bluesky.analytics.hashtagPlaceholder')
                        "
                    />
                </label>

                <label class="block min-w-0 xl:col-span-2">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                        {{ t('bluesky.analytics.limit') }}
                    </span>
                    <input
                        v-model.number="form.limit"
                        type="number"
                        min="1"
                        :max="limitMax"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        @change="clampLimit"
                    />
                </label>

                <label class="block min-w-0 xl:col-span-2">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                        {{ t('bluesky.analytics.pages') }}
                    </span>
                    <input
                        v-model.number="form.pages"
                        type="number"
                        min="1"
                        max="5"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        @change="clampPages"
                    />
                </label>

                <label class="block min-w-0 xl:col-span-1">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                        {{ t('bluesky.analytics.dateFrom') }}
                    </span>
                    <input
                        v-model="form.dateFrom"
                        type="date"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    />
                </label>

                <label class="block min-w-0 xl:col-span-1">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                        {{ t('bluesky.analytics.dateTo') }}
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
                        {{ t('bluesky.analytics.resolveProfile') }}
                    </span>
                    <span
                        class="flex h-10 items-center gap-3 rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <input v-model="form.resolve" type="checkbox" class="h-4 w-4" />
                        <span>{{ t('bluesky.analytics.resolveProfile') }}</span>
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
                                ? t('bluesky.analytics.loading')
                                : t('bluesky.analytics.refresh')
                        }}
                    </button>

                    <button
                        type="button"
                        :disabled="!canUseReportActions"
                        class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="openReport"
                    >
                        <FileText class="h-4 w-4" />
                        {{ t('bluesky.analytics.report') }}
                    </button>

                    <button
                        type="button"
                        :disabled="!canUseReportActions"
                        class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                        @click="downloadReport"
                    >
                        <Download class="h-4 w-4" />
                        {{ t('bluesky.analytics.downloadReport') }}
                    </button>
                </div>
            </div>

            <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
        </div>
    </section>

    <IntelResultPanel>
        <div class="intel-scroll min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
            <SectionCard v-if="!result" :title="t('bluesky.analytics.empty.title')">
                <p class="text-sm text-muted-foreground">
                    {{
                        loading
                            ? t('bluesky.analytics.loading')
                            : t('bluesky.analytics.empty.description')
                    }}
                </p>
            </SectionCard>

            <template v-else>
                <SectionCard v-if="accountProfile" :title="t('bluesky.analytics.accountProfile')">
                    <BlueskyActorProfileCard :actor="accountProfile" />
                </SectionCard>

                <SectionCard v-if="hashtagProfile" :title="t('bluesky.analytics.hashtagProfile')">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-base font-semibold">#{{ hashtagProfile.name }}</h2>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ t('bluesky.metrics.uses') }}: {{ hashtagProfile.history[0]?.uses ?? 0 }}
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
                            {{ t('bluesky.common.openSearch') }}
                        </a>
                    </div>
                </SectionCard>

                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
                    <MetricCard :title="t('bluesky.analytics.metrics.posts')" :value="fmt(result.summary.postsCount)" />
                    <MetricCard :title="t('bluesky.analytics.metrics.authors')" :value="fmt(result.summary.uniqueAuthorsCount)" />
                    <MetricCard :title="t('bluesky.analytics.metrics.languages')" :value="fmt(result.summary.uniqueLanguagesCount)" />
                    <MetricCard :title="t('bluesky.analytics.metrics.mediaPosts')" :value="fmt(result.summary.postsWithMediaCount)" />
                    <MetricCard :title="t('bluesky.analytics.metrics.linkPosts')" :value="fmt(result.summary.postsWithLinksCount)" />
                </div>

                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
                    <MetricCard :title="t('bluesky.analytics.metrics.replyPosts')" :value="fmt(result.summary.replyPostsCount)" />
                    <MetricCard :title="t('bluesky.analytics.metrics.repliesTotal')" :value="fmt(result.summary.totalReplies)" />
                    <MetricCard :title="t('bluesky.analytics.metrics.repostsTotal')" :value="fmt(result.summary.totalReposts)" />
                    <MetricCard :title="t('bluesky.analytics.metrics.likesTotal')" :value="fmt(result.summary.totalLikes)" />
                    <MetricCard :title="t('bluesky.analytics.metrics.quotesTotal')" :value="fmt(result.summary.totalQuotes)" />
                </div>

                <SectionCard :title="t('bluesky.analytics.sample')">
                    <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-md border border-border/70 bg-background/70 p-3 text-xs">
                            <div class="text-muted-foreground">{{ t('bluesky.analytics.sampleTarget') }}</div>
                            <div class="mt-1 font-semibold text-foreground">{{ result.meta.resolvedTarget }}</div>
                        </div>
                        <div class="rounded-md border border-border/70 bg-background/70 p-3 text-xs">
                            <div class="text-muted-foreground">{{ t('bluesky.analytics.sampledPosts') }}</div>
                            <div class="mt-1 font-semibold text-foreground">{{ fmt(result.meta.sampledPosts) }}</div>
                        </div>
                        <div class="rounded-md border border-border/70 bg-background/70 p-3 text-xs">
                            <div class="text-muted-foreground">{{ t('bluesky.analytics.pagesRequested') }}</div>
                            <div class="mt-1 font-semibold text-foreground">{{ fmt(result.meta.pagesRequested) }}</div>
                        </div>
                        <div class="rounded-md border border-border/70 bg-background/70 p-3 text-xs">
                            <div class="text-muted-foreground">{{ t('bluesky.analytics.pagesLoaded') }}</div>
                            <div class="mt-1 font-semibold text-foreground">{{ fmt(result.meta.pagesLoaded) }}</div>
                        </div>
                    </div>
                </SectionCard>

                <section class="grid gap-4 xl:grid-cols-2">
                    <SectionCard :title="t('bluesky.analytics.timeline')">
                        <div v-if="result.timeline.length > 0" class="space-y-2">
                            <div
                                v-for="point in result.timeline"
                                :key="point.day"
                                class="rounded-md border border-border/70 bg-background/70 p-3 text-xs"
                            >
                                <div class="font-medium text-foreground">{{ point.day }}</div>
                                <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-muted-foreground">
                                    <span>{{ t('bluesky.analytics.metrics.posts') }}: {{ point.posts }}</span>
                                    <span>{{ t('bluesky.analytics.metrics.mediaPosts') }}: {{ point.postsWithMedia }}</span>
                                    <span>{{ t('bluesky.analytics.metrics.linkPosts') }}: {{ point.postsWithLinks }}</span>
                                    <span>{{ t('bluesky.metrics.replies') }}: {{ point.replies }}</span>
                                    <span>{{ t('bluesky.metrics.reposts') }}: {{ point.reposts }}</span>
                                    <span>{{ t('bluesky.metrics.likes') }}: {{ point.likes }}</span>
                                    <span>{{ t('bluesky.metrics.quotes') }}: {{ point.quotes }}</span>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-xs text-muted-foreground">
                            {{ t('bluesky.analytics.noTimeline') }}
                        </p>
                    </SectionCard>

                    <SectionCard :title="t('bluesky.analytics.topPosts')">
                        <div v-if="result.topPosts.length > 0" class="space-y-2">
                            <BlueskyCompactPostCard
                                v-for="post in result.topPosts"
                                :key="post.id"
                                :post="post"
                                :format-date="formatDate"
                            />
                        </div>
                        <p v-else class="text-xs text-muted-foreground">
                            {{ t('bluesky.analytics.noTopPosts') }}
                        </p>
                    </SectionCard>
                </section>

                <section class="grid gap-4 xl:grid-cols-2">
                    <SectionCard :title="t('bluesky.analytics.topDomains')">
                        <div v-if="result.topDomains.length > 0" class="flex flex-wrap gap-2">
                            <span
                                v-for="domain in result.topDomains"
                                :key="domain.domain"
                                class="rounded-full border border-input px-2 py-1 text-xs"
                            >
                                {{ domain.domain }} | {{ domain.count }}
                            </span>
                        </div>
                        <p v-else class="text-xs text-muted-foreground">
                            {{ t('bluesky.analytics.noDomains') }}
                        </p>
                    </SectionCard>

                    <SectionCard :title="t('bluesky.analytics.topTags')">
                        <div v-if="result.topTags.length > 0" class="flex flex-wrap gap-2">
                            <span
                                v-for="tag in result.topTags"
                                :key="tag.tag"
                                class="inline-flex items-center gap-1 rounded-full border border-input px-2 py-1 text-xs"
                            >
                                <Tags class="h-3 w-3" />
                                #{{ tag.tag }} | {{ tag.count }}
                            </span>
                        </div>
                        <p v-else class="text-xs text-muted-foreground">
                            {{ t('bluesky.analytics.noTags') }}
                        </p>
                    </SectionCard>
                </section>

                <section class="grid gap-4 xl:grid-cols-3">
                    <SectionCard :title="t('bluesky.analytics.topAuthors')">
                        <div v-if="result.topAuthors.length > 0" class="space-y-2">
                            <div
                                v-for="author in result.topAuthors"
                                :key="author.did"
                                class="rounded-md border border-border/70 bg-background/70 p-3 text-xs"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="truncate font-medium text-foreground">
                                            {{ author.displayName || author.handle }}
                                        </div>
                                        <div class="truncate text-muted-foreground">@{{ author.handle }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-foreground">{{ author.count }}</div>
                                        <div class="text-muted-foreground">{{ t('bluesky.analytics.usesInSample') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-xs text-muted-foreground">
                            {{ t('bluesky.analytics.noAuthors') }}
                        </p>
                    </SectionCard>

                    <SectionCard :title="t('bluesky.analytics.topMentions')">
                        <div v-if="result.topMentions.length > 0" class="space-y-2">
                            <div
                                v-for="mention in result.topMentions"
                                :key="mention.did"
                                class="rounded-md border border-border/70 bg-background/70 p-3 text-xs"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="truncate font-medium text-foreground">
                                            {{ mention.displayName || mention.handle || mention.did }}
                                        </div>
                                        <div class="truncate text-muted-foreground">
                                            {{ mention.handle ? `@${mention.handle}` : mention.did }}
                                        </div>
                                        <a
                                            v-if="mention.url"
                                            :href="mention.url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="mt-1 inline-flex items-center gap-1 text-primary hover:underline"
                                        >
                                            <ExternalLink class="h-3 w-3" />
                                            {{ t('bluesky.common.openProfile') }}
                                        </a>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-foreground">{{ mention.count }}</div>
                                        <div class="text-muted-foreground">{{ t('bluesky.analytics.mentionsInSample') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-xs text-muted-foreground">
                            {{ t('bluesky.analytics.noMentions') }}
                        </p>
                    </SectionCard>

                    <SectionCard :title="t('bluesky.analytics.topLanguages')">
                        <div v-if="result.topLanguages.length > 0" class="space-y-2">
                            <div
                                v-for="language in result.topLanguages"
                                :key="language.language"
                                class="rounded-md border border-border/70 bg-background/70 p-3 text-xs"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div class="font-medium text-foreground">{{ language.language || '-' }}</div>
                                    <div class="font-semibold text-foreground">{{ language.count }}</div>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-xs text-muted-foreground">
                            {{ t('bluesky.analytics.noLanguages') }}
                        </p>
                    </SectionCard>
                </section>
            </template>
        </div>
    </IntelResultPanel>
</template>
