<script setup lang="ts">
import { ExternalLink } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import IntelAdvancedFilters from '@/components/ui/IntelAdvancedFilters.vue';
import SearchTabLayout from '@/components/ui/search/SearchTabLayout.vue';
import { useI18n } from '@/composables/useI18n';
import {
    getRepeatQueryParams,
    isRepeatAutorunEnabled,
    readRepeatQueryInt,
    readRepeatQueryParam,
} from '@/composables/useRepeatQuery';
import { apiRequest } from '@/lib/api';
import type {
    YouTubeCommentItem,
    YouTubeCommentsPayload,
    YouTubeSearchPayload,
    YouTubeVideo,
} from '../types';

const { t } = useI18n();
const form = ref({
    q: '',
    type: 'video' as 'video' | 'channel' | 'playlist',
    channelId: '',
    order: 'relevance',
    publishedAfter: '',
    publishedBefore: '',
    regionCode: '',
    relevanceLanguage: '',
    safeSearch: 'moderate',
    videoDuration: 'any',
    videoDefinition: 'any',
    videoCaption: 'any',
    limit: 10,
    pageToken: '',
});
const loading = ref(false);
const loadingMore = ref(false);
const error = ref<string | null>(null);
const result = ref<YouTubeSearchPayload | null>(null);
const searchPanelCollapsed = ref(false);
const showAdvanced = ref(false);
const commentsByVideoId = ref<
    Record<
        string,
        {
            open: boolean;
            loading: boolean;
            loadingMore: boolean;
            error: string | null;
            items: YouTubeCommentItem[];
            nextPageToken: string | null;
            openRepliesByCommentId: Record<string, boolean>;
        }
    >
>({});

const canSearch = computed(() => form.value.q.trim().length > 0);
const canUseVideoActions = (item: YouTubeVideo) => item.type === 'video';

const numberFormat = new Intl.NumberFormat();
const fmt = (value: number) => numberFormat.format(value ?? 0);
const formatDate = (value: string) =>
    value ? new Date(value).toLocaleString() : '-';

const ensureCommentState = (videoId: string) => {
    if (!commentsByVideoId.value[videoId]) {
        commentsByVideoId.value[videoId] = {
            open: false,
            loading: false,
            loadingMore: false,
            error: null,
            items: [],
            nextPageToken: null,
            openRepliesByCommentId: {},
        };
    }

    return commentsByVideoId.value[videoId];
};

const clampLimit = () => {
    const value = Number(form.value.limit);
    form.value.limit = Number.isFinite(value)
        ? Math.min(10, Math.max(1, Math.trunc(value)))
        : 10;
};

const runSearch = async (append = false) => {
    if (append) {
        loadingMore.value = true;
    } else {
        loading.value = true;
        error.value = null;
    }

    const response = await apiRequest<YouTubeSearchPayload>(
        '/youtube/search/videos',
        {
            query: {
                q: form.value.q,
                type: form.value.type,
                channelId: form.value.channelId,
                order: form.value.order,
                publishedAfter: form.value.publishedAfter,
                publishedBefore: form.value.publishedBefore,
                regionCode: form.value.regionCode,
                relevanceLanguage: form.value.relevanceLanguage,
                safeSearch: form.value.safeSearch,
                videoDuration: form.value.videoDuration,
                videoDefinition: form.value.videoDefinition,
                videoCaption: form.value.videoCaption,
                limit: Math.min(10, Math.max(1, form.value.limit)),
                pageToken: append
                    ? result.value?.pagination.nextPageToken
                    : form.value.pageToken,
            },
        }
    );

    loading.value = false;
    loadingMore.value = false;

    if (!response.ok) {
        error.value = response.message ?? t('youtube.common.requestFailed');

        return;
    }

    if (append && result.value) {
        result.value = {
            ...response.data,
            items: [...result.value.items, ...response.data.items],
        };

        return;
    }

    result.value = response.data;
    commentsByVideoId.value = {};
};

const loadComments = async (video: YouTubeVideo, append = false) => {
    if (video.type !== 'video') {
        return;
    }

    const state = ensureCommentState(video.id);
    state.error = null;

    if (append) {
        if (!state.nextPageToken || state.loadingMore) {
            return;
        }

        state.loadingMore = true;
    } else {
        if (state.loading) {
            return;
        }

        state.loading = true;
    }

    const response = await apiRequest<YouTubeCommentsPayload>(
        '/youtube/search/comments-preview',
        {
            query: {
                videoId: video.id,
                limit: 20,
                order: 'relevance',
                pageToken: append ? state.nextPageToken : '',
            },
        }
    );

    if (append) {
        state.loadingMore = false;
    } else {
        state.loading = false;
    }

    if (!response.ok) {
        state.error = response.message ?? t('youtube.common.requestFailed');

        return;
    }

    state.items = append
        ? [...state.items, ...response.data.items]
        : response.data.items;
    state.nextPageToken = response.data.pagination.nextPageToken;
};

const toggleComments = async (video: YouTubeVideo) => {
    if (video.type !== 'video') {
        return;
    }

    const state = ensureCommentState(video.id);
    state.open = !state.open;

    if (!state.open || state.items.length > 0 || state.loading) {
        return;
    }

    await loadComments(video, false);
};

const isRepliesOpen = (videoId: string, commentId: string) =>
    ensureCommentState(videoId).openRepliesByCommentId[commentId] === true;

const toggleReplies = (videoId: string, commentId: string) => {
    const state = ensureCommentState(videoId);
    state.openRepliesByCommentId[commentId] =
        !state.openRepliesByCommentId[commentId];
};

onMounted(() => {
    const params = getRepeatQueryParams();

    if (!params) {
        return;
    }

    const tab = readRepeatQueryParam(params, ['tab']);

    if (tab !== '' && tab !== 'search') {
        return;
    }

    const q = readRepeatQueryParam(params, ['q']);
    const type = readRepeatQueryParam(params, ['type']);
    const channelId = readRepeatQueryParam(params, ['channelId']);
    const order = readRepeatQueryParam(params, ['order']);
    const publishedAfter = readRepeatQueryParam(params, ['publishedAfter']);
    const publishedBefore = readRepeatQueryParam(params, ['publishedBefore']);
    const regionCode = readRepeatQueryParam(params, ['regionCode']);
    const relevanceLanguage = readRepeatQueryParam(params, [
        'relevanceLanguage',
    ]);
    const safeSearch = readRepeatQueryParam(params, ['safeSearch']);
    const videoDuration = readRepeatQueryParam(params, ['videoDuration']);
    const videoDefinition = readRepeatQueryParam(params, ['videoDefinition']);
    const videoCaption = readRepeatQueryParam(params, ['videoCaption']);
    const limit = readRepeatQueryInt(params, 'limit');

    if (q !== '') {
        form.value.q = q;
    }

    if (type === 'video' || type === 'channel' || type === 'playlist') {
        form.value.type = type;
    }

    if (channelId !== '') {
        form.value.channelId = channelId;
    }

    if (order !== '') {
        form.value.order = order;
    }

    if (publishedAfter !== '') {
        form.value.publishedAfter = publishedAfter;
    }

    if (publishedBefore !== '') {
        form.value.publishedBefore = publishedBefore;
    }

    if (regionCode !== '') {
        form.value.regionCode = regionCode;
    }

    if (relevanceLanguage !== '') {
        form.value.relevanceLanguage = relevanceLanguage;
    }

    if (safeSearch !== '') {
        form.value.safeSearch = safeSearch;
    }

    if (videoDuration !== '') {
        form.value.videoDuration = videoDuration;
    }

    if (videoDefinition !== '') {
        form.value.videoDefinition = videoDefinition;
    }

    if (videoCaption !== '') {
        form.value.videoCaption = videoCaption;
    }

    if (limit !== null) {
        form.value.limit = limit;
        clampLimit();
    }

    if (isRepeatAutorunEnabled(params) && canSearch.value) {
        void runSearch(false);
    }
});
</script>

<template>
    <SearchTabLayout
        :title="t('youtube.search.title')"
        :help-label="t('youtube.help.label')"
        :help-text="t('youtube.search.hint')"
        :subtitle="t('youtube.search.filters')"
        :collapsed-text="t('youtube.search.collapsed')"
        :collapsed="searchPanelCollapsed"
        :show-advanced="showAdvanced"
        :loading="loading"
        :can-search="canSearch"
        :advanced-show-aria="t('youtube.search.advancedAriaShow')"
        :advanced-hide-aria="t('youtube.search.advancedAriaHide')"
        :submit-label="t('youtube.search.submit')"
        :searching-label="t('youtube.common.loading')"
        :error="error"
        @update:collapsed="searchPanelCollapsed = $event"
        @update:show-advanced="showAdvanced = $event"
        @submit="runSearch(false)"
    >
        <template #fields>
            <div
                class="grid min-w-0 flex-1 gap-3 md:grid-cols-2 xl:grid-cols-5"
            >
                <label class="intel-field xl:col-span-2">
                    <span class="intel-label">{{
                        t('youtube.search.query')
                    }}</span>
                    <input
                        v-model="form.q"
                        type="text"
                        class="intel-input"
                        :placeholder="t('youtube.search.placeholder')"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('youtube.search.type')
                    }}</span>
                    <select v-model="form.type" class="intel-select">
                        <option value="video">
                            {{ t('youtube.options.searchType.video') }}
                        </option>
                        <option value="channel">
                            {{ t('youtube.options.searchType.channel') }}
                        </option>
                        <option value="playlist">
                            {{ t('youtube.options.searchType.playlist') }}
                        </option>
                    </select>
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('youtube.search.channelFilter')
                    }}</span>
                    <input
                        v-model="form.channelId"
                        type="text"
                        class="intel-input"
                        :placeholder="t('youtube.search.channelPlaceholder')"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('youtube.search.order')
                    }}</span>
                    <select v-model="form.order" class="intel-select">
                        <option value="relevance">
                            {{ t('youtube.options.searchOrder.relevance') }}
                        </option>
                        <option value="date">
                            {{ t('youtube.options.searchOrder.date') }}
                        </option>
                        <option value="viewCount">
                            {{ t('youtube.options.searchOrder.viewCount') }}
                        </option>
                        <option value="rating">
                            {{ t('youtube.options.searchOrder.rating') }}
                        </option>
                        <option value="title">
                            {{ t('youtube.options.searchOrder.title') }}
                        </option>
                    </select>
                </label>
            </div>
        </template>
        <template #toolbarLeading>
            <label class="intel-field min-w-[120px]">
                <span class="intel-label">{{ t('youtube.search.limit') }}</span>
                <input
                    v-model.number="form.limit"
                    type="number"
                    min="1"
                    max="10"
                    class="intel-input"
                    @input="clampLimit"
                    @blur="clampLimit"
                />
            </label>
        </template>
        <template #advanced>
            <IntelAdvancedFilters
                :open="!searchPanelCollapsed && showAdvanced"
                content-class="md:grid-cols-3 xl:grid-cols-6"
            >
                <label class="intel-field">
                    <span class="intel-label">{{
                        t('youtube.search.publishedAfter')
                    }}</span>
                    <input
                        v-model="form.publishedAfter"
                        type="date"
                        class="intel-input"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('youtube.search.publishedBefore')
                    }}</span>
                    <input
                        v-model="form.publishedBefore"
                        type="date"
                        class="intel-input"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('youtube.search.regionCode')
                    }}</span>
                    <input
                        v-model="form.regionCode"
                        maxlength="2"
                        class="intel-input uppercase"
                        :placeholder="t('youtube.search.regionPlaceholder')"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('youtube.search.relevanceLanguage')
                    }}</span>
                    <input
                        v-model="form.relevanceLanguage"
                        class="intel-input"
                        :placeholder="t('youtube.search.languagePlaceholder')"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('youtube.search.safeSearch')
                    }}</span>
                    <select v-model="form.safeSearch" class="intel-select">
                        <option value="moderate">
                            {{ t('youtube.options.safeSearch.moderate') }}
                        </option>
                        <option value="none">
                            {{ t('youtube.options.safeSearch.none') }}
                        </option>
                        <option value="strict">
                            {{ t('youtube.options.safeSearch.strict') }}
                        </option>
                    </select>
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('youtube.search.videoDuration')
                    }}</span>
                    <select
                        v-model="form.videoDuration"
                        :disabled="form.type !== 'video'"
                        class="intel-select"
                    >
                        <option value="any">
                            {{ t('youtube.options.videoDuration.any') }}
                        </option>
                        <option value="short">
                            {{ t('youtube.options.videoDuration.short') }}
                        </option>
                        <option value="medium">
                            {{ t('youtube.options.videoDuration.medium') }}
                        </option>
                        <option value="long">
                            {{ t('youtube.options.videoDuration.long') }}
                        </option>
                    </select>
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('youtube.search.videoDefinition')
                    }}</span>
                    <select
                        v-model="form.videoDefinition"
                        :disabled="form.type !== 'video'"
                        class="intel-select"
                    >
                        <option value="any">
                            {{ t('youtube.options.videoDefinition.any') }}
                        </option>
                        <option value="high">
                            {{ t('youtube.options.videoDefinition.high') }}
                        </option>
                        <option value="standard">
                            {{ t('youtube.options.videoDefinition.standard') }}
                        </option>
                    </select>
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('youtube.search.videoCaption')
                    }}</span>
                    <select
                        v-model="form.videoCaption"
                        :disabled="form.type !== 'video'"
                        class="intel-select"
                    >
                        <option value="any">
                            {{ t('youtube.options.videoCaption.any') }}
                        </option>
                        <option value="closedCaption">
                            {{
                                t('youtube.options.videoCaption.closedCaption')
                            }}
                        </option>
                        <option value="none">
                            {{ t('youtube.options.videoCaption.none') }}
                        </option>
                    </select>
                </label>

                <p class="intel-inline-note md:col-span-3 xl:col-span-6">
                    {{ t('youtube.search.publicDataHint') }}
                </p>
            </IntelAdvancedFilters>
        </template>
        <template #results>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold">
                    {{ t('youtube.search.resultTitle') }}
                </h2>
                <p class="text-xs text-muted-foreground">
                    {{ t('youtube.search.shown') }}:
                    {{ result?.items.length ?? 0 }} /
                    {{ result?.pagination.total ?? 0 }}
                </p>
            </div>

            <div
                class="intel-scroll min-h-0 flex-1 overflow-y-auto overscroll-contain pr-1"
            >
                <div
                    v-if="!loading && (!result || result.items.length === 0)"
                    class="intel-empty"
                >
                    {{ t('youtube.search.empty') }}
                </div>

                <div v-else class="space-y-3">
                    <article
                        v-for="video in result?.items ?? []"
                        :key="video.id"
                        class="relative rounded-lg border border-border/80 bg-background/70 p-3"
                    >
                        <div class="flex gap-3">
                            <img
                                v-if="video.thumbnail"
                                :src="video.thumbnail"
                                :alt="video.title"
                                class="h-24 w-40 rounded-md object-cover"
                                loading="lazy"
                            />
                            <div class="min-w-0 flex-1">
                                <h2 class="line-clamp-2 text-sm font-semibold">
                                    {{ video.title }}
                                </h2>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{
                                        t(
                                            `youtube.options.searchType.${video.type}`
                                        )
                                    }}
                                    | {{ video.channelTitle }} |
                                    {{ t('youtube.common.published') }}:
                                    {{ formatDate(video.publishedAt) }}
                                </p>
                                <p
                                    class="mt-2 line-clamp-2 text-xs text-muted-foreground"
                                >
                                    {{ video.description }}
                                </p>
                                <div
                                    v-if="canUseVideoActions(video)"
                                    class="mt-2 flex flex-wrap gap-2 text-xs"
                                >
                                    <span
                                        >{{
                                            t(
                                                'youtube.analytics.metrics.views'
                                            )
                                        }}: {{ fmt(video.views) }}</span
                                    >
                                    <span
                                        >{{
                                            t(
                                                'youtube.analytics.metrics.likes'
                                            )
                                        }}: {{ fmt(video.likes) }}</span
                                    >
                                    <span
                                        >{{
                                            t(
                                                'youtube.analytics.metrics.comments'
                                            )
                                        }}: {{ fmt(video.comments) }}</span
                                    >
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <a
                                        :href="video.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="cursor-pointer rounded-full border border-input px-2 py-1 text-xs text-primary hover:bg-accent"
                                    >
                                        <ExternalLink
                                            class="mr-1 inline h-3 w-3"
                                        />
                                        {{ t('youtube.common.open') }}
                                    </a>
                                    <button
                                        v-if="canUseVideoActions(video)"
                                        type="button"
                                        class="cursor-pointer rounded-full border border-input px-3 py-1 text-xs font-medium text-foreground hover:bg-accent"
                                        @click="toggleComments(video)"
                                    >
                                        {{
                                            ensureCommentState(video.id).open
                                                ? t(
                                                      'youtube.comments.toggleHide'
                                                  )
                                                : `${t('youtube.comments.toggleShow')} (${fmt(video.comments)})`
                                        }}
                                    </button>
                                </div>

                                <div
                                    v-if="
                                        canUseVideoActions(video) &&
                                        ensureCommentState(video.id).open
                                    "
                                    class="mt-3 rounded-lg border border-border/70 bg-card/60 p-3"
                                >
                                    <p
                                        v-if="
                                            ensureCommentState(video.id).loading
                                        "
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ t('youtube.common.loading') }}
                                    </p>
                                    <p
                                        v-else-if="
                                            ensureCommentState(video.id).error
                                        "
                                        class="text-xs text-destructive"
                                    >
                                        {{ ensureCommentState(video.id).error }}
                                    </p>
                                    <p
                                        v-else-if="
                                            ensureCommentState(video.id).items
                                                .length === 0
                                        "
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ t('youtube.comments.empty') }}
                                    </p>
                                    <div v-else class="space-y-2">
                                        <article
                                            v-for="comment in ensureCommentState(
                                                video.id
                                            ).items"
                                            :key="comment.id"
                                            class="rounded-md border border-border/70 bg-background/70 p-2"
                                        >
                                            <div
                                                class="mb-1 flex flex-wrap items-center gap-2 text-[11px] text-muted-foreground"
                                            >
                                                <a
                                                    v-if="
                                                        comment.authorChannelUrl
                                                    "
                                                    :href="
                                                        comment.authorChannelUrl
                                                    "
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="text-primary hover:underline"
                                                >
                                                    {{ comment.author }}
                                                </a>
                                                <span v-else>{{
                                                    comment.author
                                                }}</span>
                                                <span>{{
                                                    formatDate(
                                                        comment.publishedAt
                                                    )
                                                }}</span>
                                                <span
                                                    >👍
                                                    {{
                                                        fmt(comment.likeCount)
                                                    }}</span
                                                >
                                                <span
                                                    >💬
                                                    {{
                                                        fmt(comment.replyCount)
                                                    }}</span
                                                >
                                            </div>
                                            <p
                                                class="text-xs leading-relaxed text-foreground"
                                            >
                                                {{ comment.text }}
                                            </p>

                                            <div
                                                v-if="comment.replyCount > 0"
                                                class="mt-2"
                                            >
                                                <button
                                                    type="button"
                                                    class="cursor-pointer rounded-full border border-input px-2.5 py-1 text-[11px] font-medium text-foreground hover:bg-accent"
                                                    @click="
                                                        toggleReplies(
                                                            video.id,
                                                            comment.id
                                                        )
                                                    "
                                                >
                                                    {{
                                                        isRepliesOpen(
                                                            video.id,
                                                            comment.id
                                                        )
                                                            ? t(
                                                                  'youtube.comments.toggleHideReplies'
                                                              )
                                                            : `${t('youtube.comments.toggleShowReplies')} (${fmt(comment.replyCount)})`
                                                    }}
                                                </button>
                                            </div>

                                            <div
                                                v-if="
                                                    comment.replyCount > 0 &&
                                                    isRepliesOpen(
                                                        video.id,
                                                        comment.id
                                                    )
                                                "
                                                class="mt-2 space-y-1.5 border-l border-border/70 pl-2"
                                            >
                                                <article
                                                    v-for="reply in comment.replies"
                                                    :key="reply.id"
                                                    class="rounded-md border border-border/60 bg-background/80 p-2"
                                                >
                                                    <div
                                                        class="mb-1 flex flex-wrap items-center gap-2 text-[11px] text-muted-foreground"
                                                    >
                                                        <span>{{
                                                            reply.author
                                                        }}</span>
                                                        <span>{{
                                                            formatDate(
                                                                reply.publishedAt
                                                            )
                                                        }}</span>
                                                        <span
                                                            >👍
                                                            {{
                                                                fmt(
                                                                    reply.likeCount
                                                                )
                                                            }}</span
                                                        >
                                                    </div>
                                                    <p
                                                        class="text-xs leading-relaxed text-foreground"
                                                    >
                                                        {{ reply.text }}
                                                    </p>
                                                </article>

                                                <p
                                                    v-if="
                                                        comment.replyCount >
                                                        comment.replies.length
                                                    "
                                                    class="text-[11px] text-muted-foreground"
                                                >
                                                    {{
                                                        t(
                                                            'youtube.comments.repliesPartialHint'
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </article>

                                        <div
                                            v-if="
                                                ensureCommentState(video.id)
                                                    .nextPageToken
                                            "
                                            class="pt-1"
                                        >
                                            <button
                                                type="button"
                                                :disabled="
                                                    ensureCommentState(video.id)
                                                        .loadingMore
                                                "
                                                class="cursor-pointer rounded-md border border-input bg-background px-3 py-1 text-xs font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                                                @click="
                                                    loadComments(video, true)
                                                "
                                            >
                                                {{
                                                    ensureCommentState(video.id)
                                                        .loadingMore
                                                        ? t(
                                                              'youtube.common.loading'
                                                          )
                                                        : t(
                                                              'youtube.common.loadMore'
                                                          )
                                                }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </template>
        <template #footer>
            <div
                v-if="result?.pagination.nextPageToken"
                class="mt-4 flex justify-center"
            >
                <button
                    :disabled="loadingMore"
                    class="cursor-pointer rounded-md border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                    @click="runSearch(true)"
                >
                    {{
                        loadingMore
                            ? t('youtube.common.loading')
                            : t('youtube.common.loadMore')
                    }}
                </button>
            </div>
        </template>
    </SearchTabLayout>
</template>
