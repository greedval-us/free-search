<script setup lang="ts">
import { Tags } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AnalyticsChipSection from '@/components/ui/analytics/AnalyticsChipSection.vue';
import AnalyticsControlPanel from '@/components/ui/analytics/AnalyticsControlPanel.vue';
import AnalyticsMetricGrid from '@/components/ui/analytics/AnalyticsMetricGrid.vue';
import AnalyticsRankSection from '@/components/ui/analytics/AnalyticsRankSection.vue';
import AnalyticsSampleGrid from '@/components/ui/analytics/AnalyticsSampleGrid.vue';
import AnalyticsTimelineSection from '@/components/ui/analytics/AnalyticsTimelineSection.vue';
import HashtagProfileCard from '@/components/ui/HashtagProfileCard.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import SectionCard from '@/components/ui/SectionCard.vue';
import { useI18n } from '@/composables/useI18n';
import { apiRequest } from '@/lib/api';
import MastodonAccountCard from '../components/MastodonAccountCard.vue';
import MastodonStatusCard from '../components/MastodonStatusCard.vue';
import type {
    MastodonAccount,
    MastodonAnalyticsPayload,
    MastodonHashtag,
} from '../types';
import MastodonAnalyticsEmptyState from './MastodonAnalyticsEmptyState.vue';

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
const canUseReportActions = computed(
    () => result.value !== null && canRun.value
);
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

const clampNumber = (
    value: number,
    min: number,
    max: number,
    fallback: number
) => {
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
        error.value =
            response.message ?? t('mastodon.analytics.errors.requestFailed');

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

const primaryMetrics = computed(() =>
    result.value
        ? [
              {
                  title: t('mastodon.analytics.metrics.posts'),
                  value: fmt(result.value.summary.postsCount),
              },
              {
                  title: t('mastodon.analytics.metrics.accounts'),
                  value: fmt(result.value.summary.uniqueAccountsCount),
              },
              {
                  title: t('mastodon.analytics.metrics.instances'),
                  value: fmt(result.value.summary.uniqueInstancesCount),
              },
              {
                  title: t('mastodon.analytics.metrics.languages'),
                  value: fmt(result.value.summary.uniqueLanguagesCount),
              },
              {
                  title: t('mastodon.analytics.metrics.mediaPosts'),
                  value: fmt(result.value.summary.postsWithMediaCount),
              },
              {
                  title: t('mastodon.analytics.metrics.linkPosts'),
                  value: fmt(result.value.summary.postsWithLinksCount),
              },
          ]
        : []
);

const engagementMetrics = computed(() =>
    result.value
        ? [
              {
                  title: t('mastodon.analytics.metrics.replyPosts'),
                  value: fmt(result.value.summary.replyPostsCount),
              },
              {
                  title: t('mastodon.analytics.metrics.boostPosts'),
                  value: fmt(result.value.summary.boostPostsCount),
              },
              {
                  title: t('mastodon.analytics.metrics.sensitivePosts'),
                  value: fmt(result.value.summary.sensitivePostsCount),
              },
              {
                  title: t('mastodon.analytics.metrics.repliesTotal'),
                  value: fmt(result.value.summary.totalReplies),
              },
              {
                  title: t('mastodon.analytics.metrics.reblogsTotal'),
                  value: fmt(result.value.summary.totalReblogs),
              },
              {
                  title: t('mastodon.analytics.metrics.favouritesTotal'),
                  value: fmt(result.value.summary.totalFavourites),
              },
          ]
        : []
);

const sampleItems = computed(() =>
    result.value
        ? [
              {
                  label: t('mastodon.analytics.sampleTarget'),
                  value: result.value.meta.resolvedTarget,
              },
              {
                  label: t('mastodon.analytics.sampledPosts'),
                  value: fmt(result.value.meta.sampledPosts),
              },
              {
                  label: t('mastodon.analytics.pagesRequested'),
                  value: fmt(result.value.meta.pagesRequested),
              },
              {
                  label: t('mastodon.analytics.pagesLoaded'),
                  value: fmt(result.value.meta.pagesLoaded),
              },
          ]
        : []
);

const timelineItems = computed(() => [
    {
        key: 'posts',
        label: t('mastodon.analytics.metrics.posts'),
        value: '',
    },
    {
        key: 'postsWithMedia',
        label: t('mastodon.analytics.metrics.mediaPosts'),
        value: '',
    },
    {
        key: 'postsWithLinks',
        label: t('mastodon.analytics.metrics.linkPosts'),
        value: '',
    },
    { key: 'replies', label: t('mastodon.metrics.replies'), value: '' },
    { key: 'reblogs', label: t('mastodon.metrics.reblogs'), value: '' },
    {
        key: 'favourites',
        label: t('mastodon.metrics.favourites'),
        value: '',
    },
]);

const timelinePoints = computed(
    () =>
        result.value?.timeline.map((point) => ({
            day: point.day,
            values: [
                { key: 'posts', value: point.posts },
                { key: 'postsWithMedia', value: point.postsWithMedia },
                { key: 'postsWithLinks', value: point.postsWithLinks },
                { key: 'replies', value: point.replies },
                { key: 'reblogs', value: point.reblogs },
                { key: 'favourites', value: point.favourites },
            ],
        })) ?? []
);

const topDomains = computed(
    () =>
        result.value?.topDomains.map((domain) => ({
            key: domain.domain,
            label: `${domain.domain} | ${domain.count}`,
        })) ?? []
);

const topTags = computed(
    () =>
        result.value?.topTags.map((tag) => ({
            key: tag.tag,
            label: `#${tag.tag} | ${tag.count}`,
            icon: Tags,
        })) ?? []
);

const topAccounts = computed(
    () =>
        result.value?.topAccounts.map((account) => ({
            key: account.id,
            title: account.displayName || account.username,
            subtitle: `@${account.acct}`,
            caption: t('mastodon.analytics.usesInSample'),
            value: account.count,
        })) ?? []
);

const topMentions = computed(
    () =>
        result.value?.topMentions.map((mention) => ({
            key: mention.acct,
            title: `@${mention.acct}`,
            caption: t('mastodon.analytics.mentionsInSample'),
            value: mention.count,
            link: mention.url,
            linkLabel: t('mastodon.common.openProfile'),
        })) ?? []
);

const topLanguages = computed(
    () =>
        result.value?.topLanguages.map((language) => ({
            key: language.language || 'unknown',
            title: language.language || '-',
            value: language.count,
        })) ?? []
);

const hashtagProfileStats = computed(() =>
    hashtagProfile.value
        ? [
              {
                  label: t('mastodon.metrics.historyPoints'),
                  value: hashtagProfile.value.history.length,
              },
              {
                  label: t('mastodon.metrics.uses'),
                  value: hashtagProfile.value.history[0]?.uses ?? 0,
              },
          ]
        : []
);
</script>

<template>
    <AnalyticsControlPanel
        :title="t('mastodon.analytics.title')"
        :help-label="t('mastodon.help.label')"
        :help-text="t('mastodon.analytics.hint')"
        :subtitle="t('mastodon.analytics.hint')"
        :collapsed-text="t('mastodon.analytics.collapsed')"
        :collapsed="panelCollapsed"
        :loading="loading"
        :can-run="canRun"
        :can-use-report-actions="canUseReportActions"
        :run-label="t('mastodon.analytics.refresh')"
        :loading-label="t('mastodon.analytics.loading')"
        :report-label="t('mastodon.analytics.report')"
        :download-report-label="t('mastodon.analytics.downloadReport')"
        @update:collapsed="panelCollapsed = $event"
        @run="runAnalytics"
        @open-report="openReport"
        @download-report="downloadReport"
    >
        <template #fields>
            <div class="grid gap-2.5 md:grid-cols-2 xl:grid-cols-12">
                <label class="intel-field xl:col-span-2">
                    <span class="intel-label">{{
                        t('mastodon.analytics.mode')
                    }}</span>
                    <select v-model="form.mode" class="intel-select">
                        <option value="account">
                            {{ t('mastodon.analytics.modes.account') }}
                        </option>
                        <option value="hashtag">
                            {{ t('mastodon.analytics.modes.hashtag') }}
                        </option>
                    </select>
                </label>

                <label class="intel-field xl:col-span-4">
                    <span class="intel-label">{{
                        t('mastodon.analytics.target')
                    }}</span>
                    <input
                        v-model="form.target"
                        type="text"
                        class="intel-input"
                        :placeholder="
                            isAccountMode
                                ? t('mastodon.analytics.accountPlaceholder')
                                : t('mastodon.analytics.hashtagPlaceholder')
                        "
                    />
                </label>

                <label class="intel-field xl:col-span-2">
                    <span class="intel-label">{{
                        t('mastodon.analytics.limit')
                    }}</span>
                    <input
                        v-model.number="form.limit"
                        type="number"
                        min="1"
                        max="20"
                        class="intel-input"
                        @change="
                            form.limit = clampNumber(form.limit, 1, 20, 10)
                        "
                    />
                </label>

                <label class="intel-field xl:col-span-2">
                    <span class="intel-label">{{
                        t('mastodon.analytics.pages')
                    }}</span>
                    <input
                        v-model.number="form.pages"
                        type="number"
                        min="1"
                        max="5"
                        class="intel-input"
                        @change="form.pages = clampNumber(form.pages, 1, 5, 3)"
                    />
                </label>

                <label class="intel-field xl:col-span-1">
                    <span class="intel-label">{{
                        t('mastodon.analytics.dateFrom')
                    }}</span>
                    <input
                        v-model="form.dateFrom"
                        type="date"
                        class="intel-input"
                    />
                </label>

                <label class="intel-field xl:col-span-1">
                    <span class="intel-label">{{
                        t('mastodon.analytics.dateTo')
                    }}</span>
                    <input
                        v-model="form.dateTo"
                        type="date"
                        class="intel-input"
                    />
                </label>
            </div>
        </template>
        <template #toolbarLeading>
            <label class="intel-field">
                <span class="intel-label">
                    {{ t('mastodon.search.resolveRemote') }}
                </span>
                <span
                    class="flex h-10 items-center gap-3 rounded-md border border-input bg-background px-3 text-sm"
                >
                    <input
                        v-model="form.resolve"
                        type="checkbox"
                        class="h-4 w-4"
                    />
                    <span>{{ t('mastodon.search.resolveRemote') }}</span>
                </span>
            </label>
            <p class="intel-inline-note">
                {{ t('mastodon.analytics.targetHint') }}
            </p>
        </template>
        <template #afterActions>
            <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
        </template>
    </AnalyticsControlPanel>

    <IntelResultPanel>
        <div class="intel-scroll min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
            <MastodonAnalyticsEmptyState
                v-if="!result"
                :loading="loading"
                :disabled="!canRun"
                @refresh="runAnalytics"
            />

            <template v-else>
                <SectionCard
                    v-if="accountProfile"
                    :title="t('mastodon.analytics.accountProfile')"
                >
                    <MastodonAccountCard
                        :account="accountProfile"
                        :format-date="formatDate"
                    />
                </SectionCard>

                <SectionCard
                    v-if="hashtagProfile"
                    :title="t('mastodon.analytics.hashtagProfile')"
                >
                    <HashtagProfileCard
                        :name="hashtagProfile.name"
                        :url="hashtagProfile.url"
                        :open-label="t('mastodon.common.openTag')"
                        :stats="hashtagProfileStats"
                        :history="hashtagProfile.history"
                        :uses-label="t('mastodon.metrics.uses')"
                        :accounts-label="t('mastodon.metrics.accounts')"
                    />
                </SectionCard>

                <AnalyticsMetricGrid
                    :items="primaryMetrics"
                    grid-class="sm:grid-cols-2 xl:grid-cols-6"
                />

                <AnalyticsMetricGrid
                    :items="engagementMetrics"
                    grid-class="sm:grid-cols-2 xl:grid-cols-6"
                />

                <AnalyticsSampleGrid
                    :title="t('mastodon.analytics.sample')"
                    :items="sampleItems"
                />

                <section class="grid gap-4 xl:grid-cols-2">
                    <AnalyticsTimelineSection
                        :title="t('mastodon.analytics.timeline')"
                        :items="timelineItems"
                        :points="timelinePoints"
                        :empty-text="t('mastodon.analytics.noTimeline')"
                    />

                    <SectionCard :title="t('mastodon.analytics.topPosts')">
                        <div
                            v-if="result.topPosts.length > 0"
                            class="space-y-2"
                        >
                            <MastodonStatusCard
                                v-for="status in result.topPosts"
                                :key="status.id"
                                :status="status"
                                :format-date="formatDate"
                                compact
                            />
                        </div>
                        <p v-else class="text-xs text-muted-foreground">
                            {{ t('mastodon.analytics.noTopPosts') }}
                        </p>
                    </SectionCard>
                </section>

                <section class="grid gap-4 xl:grid-cols-2">
                    <AnalyticsChipSection
                        :title="t('mastodon.analytics.topDomains')"
                        :items="topDomains"
                        :empty-text="t('mastodon.analytics.noDomains')"
                    />

                    <AnalyticsChipSection
                        :title="t('mastodon.analytics.topTags')"
                        :items="topTags"
                        :empty-text="t('mastodon.analytics.noTags')"
                    />
                </section>

                <section class="grid gap-4 xl:grid-cols-3">
                    <AnalyticsRankSection
                        :title="t('mastodon.analytics.topAccounts')"
                        :items="topAccounts"
                        :empty-text="t('mastodon.analytics.noAccounts')"
                    />

                    <AnalyticsRankSection
                        :title="t('mastodon.analytics.topMentions')"
                        :items="topMentions"
                        :empty-text="t('mastodon.analytics.noMentions')"
                    />

                    <AnalyticsRankSection
                        :title="t('mastodon.analytics.topLanguages')"
                        :items="topLanguages"
                        :empty-text="t('mastodon.analytics.noLanguages')"
                    />
                </section>
            </template>
        </div>
    </IntelResultPanel>
</template>
