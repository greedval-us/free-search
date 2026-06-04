<script setup lang="ts">
import { ExternalLink, Tags } from 'lucide-vue-next';
import { computed } from 'vue';
import AnalyticsControlPanel from '@/components/ui/analytics/AnalyticsControlPanel.vue';
import AnalyticsChipSection from '@/components/ui/analytics/AnalyticsChipSection.vue';
import AnalyticsMetricGrid from '@/components/ui/analytics/AnalyticsMetricGrid.vue';
import AnalyticsRankSection from '@/components/ui/analytics/AnalyticsRankSection.vue';
import AnalyticsSampleGrid from '@/components/ui/analytics/AnalyticsSampleGrid.vue';
import AnalyticsTimelineSection from '@/components/ui/analytics/AnalyticsTimelineSection.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
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
const formatDate = (value: string) =>
    value ? new Date(value).toLocaleString() : '-';

const primaryMetrics = computed(() =>
    result.value
        ? [
              {
                  title: t('bluesky.analytics.metrics.posts'),
                  value: fmt(result.value.summary.postsCount),
              },
              {
                  title: t('bluesky.analytics.metrics.authors'),
                  value: fmt(result.value.summary.uniqueAuthorsCount),
              },
              {
                  title: t('bluesky.analytics.metrics.languages'),
                  value: fmt(result.value.summary.uniqueLanguagesCount),
              },
              {
                  title: t('bluesky.analytics.metrics.mediaPosts'),
                  value: fmt(result.value.summary.postsWithMediaCount),
              },
              {
                  title: t('bluesky.analytics.metrics.linkPosts'),
                  value: fmt(result.value.summary.postsWithLinksCount),
              },
          ]
        : []
);

const engagementMetrics = computed(() =>
    result.value
        ? [
              {
                  title: t('bluesky.analytics.metrics.replyPosts'),
                  value: fmt(result.value.summary.replyPostsCount),
              },
              {
                  title: t('bluesky.analytics.metrics.repliesTotal'),
                  value: fmt(result.value.summary.totalReplies),
              },
              {
                  title: t('bluesky.analytics.metrics.repostsTotal'),
                  value: fmt(result.value.summary.totalReposts),
              },
              {
                  title: t('bluesky.analytics.metrics.likesTotal'),
                  value: fmt(result.value.summary.totalLikes),
              },
              {
                  title: t('bluesky.analytics.metrics.quotesTotal'),
                  value: fmt(result.value.summary.totalQuotes),
              },
          ]
        : []
);

const sampleItems = computed(() =>
    result.value
        ? [
              {
                  label: t('bluesky.analytics.sampleTarget'),
                  value: result.value.meta.resolvedTarget,
              },
              {
                  label: t('bluesky.analytics.sampledPosts'),
                  value: fmt(result.value.meta.sampledPosts),
              },
              {
                  label: t('bluesky.analytics.pagesRequested'),
                  value: fmt(result.value.meta.pagesRequested),
              },
              {
                  label: t('bluesky.analytics.pagesLoaded'),
                  value: fmt(result.value.meta.pagesLoaded),
              },
          ]
        : []
);

const timelineItems = computed(() => [
    {
        key: 'posts',
        label: t('bluesky.analytics.metrics.posts'),
        value: '',
    },
    {
        key: 'postsWithMedia',
        label: t('bluesky.analytics.metrics.mediaPosts'),
        value: '',
    },
    {
        key: 'postsWithLinks',
        label: t('bluesky.analytics.metrics.linkPosts'),
        value: '',
    },
    { key: 'replies', label: t('bluesky.metrics.replies'), value: '' },
    { key: 'reposts', label: t('bluesky.metrics.reposts'), value: '' },
    { key: 'likes', label: t('bluesky.metrics.likes'), value: '' },
    { key: 'quotes', label: t('bluesky.metrics.quotes'), value: '' },
]);

const timelinePoints = computed(() =>
    result.value?.timeline.map((point) => ({
        day: point.day,
        values: [
            { key: 'posts', value: point.posts },
            { key: 'postsWithMedia', value: point.postsWithMedia },
            { key: 'postsWithLinks', value: point.postsWithLinks },
            { key: 'replies', value: point.replies },
            { key: 'reposts', value: point.reposts },
            { key: 'likes', value: point.likes },
            { key: 'quotes', value: point.quotes },
        ],
    })) ?? []
);

const topDomains = computed(() =>
    result.value?.topDomains.map((domain) => ({
        key: domain.domain,
        label: `${domain.domain} | ${domain.count}`,
    })) ?? []
);

const topTags = computed(() =>
    result.value?.topTags.map((tag) => ({
        key: tag.tag,
        label: `#${tag.tag} | ${tag.count}`,
        icon: Tags,
    })) ?? []
);

const topAuthors = computed(() =>
    result.value?.topAuthors.map((author) => ({
        key: author.did,
        title: author.displayName || author.handle,
        subtitle: `@${author.handle}`,
        caption: t('bluesky.analytics.usesInSample'),
        value: author.count,
    })) ?? []
);

const topMentions = computed(() =>
    result.value?.topMentions.map((mention) => ({
        key: mention.did,
        title: mention.displayName || mention.handle || mention.did,
        subtitle: mention.handle ? `@${mention.handle}` : mention.did,
        caption: t('bluesky.analytics.mentionsInSample'),
        value: mention.count,
        link: mention.url,
        linkLabel: t('bluesky.common.openProfile'),
    })) ?? []
);

const topLanguages = computed(() =>
    result.value?.topLanguages.map((language) => ({
        key: language.language || 'unknown',
        title: language.language || '-',
        value: language.count,
    })) ?? []
);
</script>

<template>
    <AnalyticsControlPanel
        :title="t('bluesky.analytics.title')"
        :help-label="t('bluesky.help.label')"
        :help-text="t('bluesky.analytics.hint')"
        :subtitle="t('bluesky.analytics.hint')"
        :collapsed-text="t('bluesky.analytics.collapsed')"
        :collapsed="panelCollapsed"
        :loading="loading"
        :can-run="canRun"
        :can-use-report-actions="canUseReportActions"
        :run-label="t('bluesky.analytics.refresh')"
        :loading-label="t('bluesky.analytics.loading')"
        :report-label="t('bluesky.analytics.report')"
        :download-report-label="t('bluesky.analytics.downloadReport')"
        @update:collapsed="panelCollapsed = $event"
        @run="runAnalytics"
        @open-report="openReport"
        @download-report="downloadReport"
    >
        <template #fields>
            <div class="grid gap-2.5 md:grid-cols-2 xl:grid-cols-12">
                <label class="block min-w-0 xl:col-span-2">
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >
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
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >
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
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >
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
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >
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
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >
                        {{ t('bluesky.analytics.dateFrom') }}
                    </span>
                    <input
                        v-model="form.dateFrom"
                        type="date"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    />
                </label>

                <label class="block min-w-0 xl:col-span-1">
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >
                        {{ t('bluesky.analytics.dateTo') }}
                    </span>
                    <input
                        v-model="form.dateTo"
                        type="date"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    />
                </label>
            </div>
        </template>
        <template #toolbarLeading>
            <label class="block min-w-0">
                <span
                    class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                >
                    {{ t('bluesky.analytics.resolveProfile') }}
                </span>
                <span
                    class="flex h-10 items-center gap-3 rounded-md border border-input bg-background px-3 text-sm"
                >
                    <input
                        v-model="form.resolve"
                        type="checkbox"
                        class="h-4 w-4"
                    />
                    <span>{{ t('bluesky.analytics.resolveProfile') }}</span>
                </span>
            </label>
        </template>
        <template #afterActions>
            <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
        </template>
    </AnalyticsControlPanel>

    <IntelResultPanel>
        <div class="intel-scroll min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
            <SectionCard
                v-if="!result"
                :title="t('bluesky.analytics.empty.title')"
            >
                <p class="text-sm text-muted-foreground">
                    {{
                        loading
                            ? t('bluesky.analytics.loading')
                            : t('bluesky.analytics.empty.description')
                    }}
                </p>
            </SectionCard>

            <template v-else>
                <SectionCard
                    v-if="accountProfile"
                    :title="t('bluesky.analytics.accountProfile')"
                >
                    <BlueskyActorProfileCard :actor="accountProfile" />
                </SectionCard>

                <SectionCard
                    v-if="hashtagProfile"
                    :title="t('bluesky.analytics.hashtagProfile')"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-base font-semibold">
                                #{{ hashtagProfile.name }}
                            </h2>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ t('bluesky.metrics.uses') }}:
                                {{ hashtagProfile.history[0]?.uses ?? 0 }}
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

                <AnalyticsMetricGrid
                    :items="primaryMetrics"
                    grid-class="sm:grid-cols-2 xl:grid-cols-5"
                />

                <AnalyticsMetricGrid
                    :items="engagementMetrics"
                    grid-class="sm:grid-cols-2 xl:grid-cols-5"
                />

                <AnalyticsSampleGrid
                    :title="t('bluesky.analytics.sample')"
                    :items="sampleItems"
                />

                <section class="grid gap-4 xl:grid-cols-2">
                    <AnalyticsTimelineSection
                        :title="t('bluesky.analytics.timeline')"
                        :items="timelineItems"
                        :points="timelinePoints"
                        :empty-text="t('bluesky.analytics.noTimeline')"
                    />

                    <SectionCard :title="t('bluesky.analytics.topPosts')">
                        <div
                            v-if="result.topPosts.length > 0"
                            class="space-y-2"
                        >
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
                    <AnalyticsChipSection
                        :title="t('bluesky.analytics.topDomains')"
                        :items="topDomains"
                        :empty-text="t('bluesky.analytics.noDomains')"
                    />

                    <AnalyticsChipSection
                        :title="t('bluesky.analytics.topTags')"
                        :items="topTags"
                        :empty-text="t('bluesky.analytics.noTags')"
                    />
                </section>

                <section class="grid gap-4 xl:grid-cols-3">
                    <AnalyticsRankSection
                        :title="t('bluesky.analytics.topAuthors')"
                        :items="topAuthors"
                        :empty-text="t('bluesky.analytics.noAuthors')"
                    />

                    <AnalyticsRankSection
                        :title="t('bluesky.analytics.topMentions')"
                        :items="topMentions"
                        :empty-text="t('bluesky.analytics.noMentions')"
                    />

                    <AnalyticsRankSection
                        :title="t('bluesky.analytics.topLanguages')"
                        :items="topLanguages"
                        :empty-text="t('bluesky.analytics.noLanguages')"
                    />
                </section>
            </template>
        </div>
    </IntelResultPanel>
</template>
