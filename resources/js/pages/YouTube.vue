<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { BarChart3, Download, ExternalLink, LoaderCircle, MessageSquareText, Search, Youtube } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue';
import { useI18n } from '@/composables/useI18n';
import { apiRequest } from '@/lib/api';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'YouTube',
                href: '/youtube',
            },
        ],
    },
});

type TabValue = 'search' | 'analytics' | 'parser';

type YouTubeVideo = {
    id: string;
    title: string;
    description: string;
    channelId: string;
    channelTitle: string;
    publishedAt: string;
    thumbnail: string;
    duration: string;
    views: number;
    likes: number;
    comments: number;
    url: string;
};

type SearchPayload = {
    items: YouTubeVideo[];
    pagination: {
        nextPageToken: string | null;
        total: number;
        perPage: number;
    };
};

type AnalyticsPayload = {
    mode: 'video' | 'channel';
    video: YouTubeVideo | null;
    totals: {
        videos: number;
        views: number;
        likes: number;
        comments: number;
        avgViews: number;
        engagementRate: number;
    };
    topVideos: YouTubeVideo[];
};

type CommentItem = {
    id: string;
    threadId: string;
    author: string;
    authorChannelUrl: string;
    text: string;
    likeCount: number;
    publishedAt: string;
    replyCount: number;
    replies: Array<{
        id: string;
        author: string;
        text: string;
        likeCount: number;
        publishedAt: string;
    }>;
};

type CommentsPayload = {
    items: CommentItem[];
    pagination: {
        nextPageToken: string | null;
        total: number;
        perPage: number;
    };
};

const { locale } = useI18n();

const copy = computed(() => {
    if (locale.value === 'ru') {
        return {
            title: 'YouTube',
            search: 'Поиск',
            analytics: 'Аналитика',
            parser: 'Парсер',
            query: 'Запрос',
            channelId: 'Channel ID',
            videoId: 'Video ID',
            limit: 'Лимит',
            order: 'Сортировка',
            find: 'Найти',
            analyze: 'Анализировать',
            parse: 'Собрать комментарии',
            loading: 'Загрузка...',
            empty: 'Пока нет данных',
            published: 'Опубликовано',
            views: 'Просмотры',
            likes: 'Лайки',
            comments: 'Комментарии',
            open: 'Открыть',
            loadMore: 'Загрузить ещё',
            nextPage: 'Следующая страница',
            searchHint: 'Поиск видео через YouTube Data API v3.',
            analyticsHint: 'Сводка по одному видео или последним видео канала.',
            parserHint: 'Парсинг верхнеуровневых комментариев и доступных ответов.',
            totals: 'Сводка',
            topVideos: 'Топ видео',
            engagement: 'ER',
            avgViews: 'Средние просмотры',
            apiKeyMissing: 'Если появится ошибка ключа, добавь YOUTUBE_DATA_API_KEY в .env.',
            searchPlaceholder: 'например: osint tools',
        };
    }

    return {
        title: 'YouTube',
        search: 'Search',
        analytics: 'Analytics',
        parser: 'Parser',
        query: 'Query',
        channelId: 'Channel ID',
        videoId: 'Video ID',
        limit: 'Limit',
        order: 'Order',
        find: 'Search',
        analyze: 'Analyze',
        parse: 'Parse comments',
        loading: 'Loading...',
        empty: 'No data yet',
        published: 'Published',
        views: 'Views',
        likes: 'Likes',
        comments: 'Comments',
        open: 'Open',
        loadMore: 'Load more',
        nextPage: 'Next page',
        searchHint: 'Search videos with YouTube Data API v3.',
        analyticsHint: 'Summary for one video or latest channel videos.',
        parserHint: 'Parse top-level comments and available replies.',
        totals: 'Summary',
        topVideos: 'Top videos',
        engagement: 'ER',
        avgViews: 'Avg views',
        apiKeyMissing: 'If you see a key error, add YOUTUBE_DATA_API_KEY to .env.',
        searchPlaceholder: 'for example: osint tools',
    };
});

const activeTab = ref<TabValue>('search');
const tabs = computed(() => [
    { key: 'search' as const, label: copy.value.search, icon: Search },
    { key: 'analytics' as const, label: copy.value.analytics, icon: BarChart3 },
    { key: 'parser' as const, label: copy.value.parser, icon: Download },
]);

const searchForm = reactive({
    q: '',
    channelId: '',
    order: 'relevance',
    limit: 12,
    pageToken: '',
});

const analyticsForm = reactive({
    mode: 'video' as 'video' | 'channel',
    videoId: '',
    channelId: '',
    limit: 10,
});

const parserForm = reactive({
    videoId: '',
    order: 'relevance',
    searchTerms: '',
    limit: 20,
    pageToken: '',
});

const loading = ref(false);
const error = ref<string | null>(null);
const searchResult = ref<SearchPayload | null>(null);
const analyticsResult = ref<AnalyticsPayload | null>(null);
const commentsResult = ref<CommentsPayload | null>(null);

const numberFormat = new Intl.NumberFormat();
const fmt = (value: number) => numberFormat.format(value ?? 0);
const formatDate = (value: string) => value ? new Date(value).toLocaleString() : '-';

const runSearch = async (append = false) => {
    loading.value = true;
    error.value = null;

    const result = await apiRequest<SearchPayload>('/youtube/search/videos', {
        query: {
            q: searchForm.q,
            channelId: searchForm.channelId,
            order: searchForm.order,
            limit: searchForm.limit,
            pageToken: append ? searchResult.value?.pagination.nextPageToken : searchForm.pageToken,
        },
    });

    loading.value = false;

    if (!result.ok) {
        error.value = result.message ?? 'Request failed';
        return;
    }

    if (append && searchResult.value) {
        searchResult.value = {
            ...result.data,
            items: [...searchResult.value.items, ...result.data.items],
        };
        return;
    }

    searchResult.value = result.data;
};

const runAnalytics = async () => {
    loading.value = true;
    error.value = null;

    const result = await apiRequest<AnalyticsPayload>('/youtube/analytics/summary', {
        query: {
            mode: analyticsForm.mode,
            videoId: analyticsForm.videoId,
            channelId: analyticsForm.channelId,
            limit: analyticsForm.limit,
        },
    });

    loading.value = false;

    if (!result.ok) {
        error.value = result.message ?? 'Request failed';
        return;
    }

    analyticsResult.value = result.data;
};

const runParser = async (append = false) => {
    loading.value = true;
    error.value = null;

    const result = await apiRequest<CommentsPayload>('/youtube/parser/comments', {
        query: {
            videoId: parserForm.videoId,
            order: parserForm.order,
            searchTerms: parserForm.searchTerms,
            limit: parserForm.limit,
            pageToken: append ? commentsResult.value?.pagination.nextPageToken : parserForm.pageToken,
        },
    });

    loading.value = false;

    if (!result.ok) {
        error.value = result.message ?? 'Request failed';
        return;
    }

    if (append && commentsResult.value) {
        commentsResult.value = {
            ...result.data,
            items: [...commentsResult.value.items, ...result.data.items],
        };
        return;
    }

    commentsResult.value = result.data;
};

const useVideoForAnalytics = (video: YouTubeVideo) => {
    activeTab.value = 'analytics';
    analyticsForm.mode = 'video';
    analyticsForm.videoId = video.id;
    analyticsForm.channelId = '';
};

const useVideoForParser = (video: YouTubeVideo) => {
    activeTab.value = 'parser';
    parserForm.videoId = video.id;
};
</script>

<template>
    <Head :title="copy.title" />

    <div class="flex h-full min-h-0 flex-1 flex-col gap-4 overflow-hidden rounded-xl p-4">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <Youtube class="h-5 w-5 text-red-500" />
                <h1 class="text-base font-semibold">YouTube</h1>
            </div>
            <p class="text-xs text-muted-foreground">{{ copy.apiKeyMissing }}</p>
        </div>

        <div class="flex items-center justify-center gap-1 rounded-lg bg-slate-800/80 p-1">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                @click="activeTab = tab.key"
                :class="[
                    'flex items-center rounded-md px-3 py-1.5 text-xs font-medium transition-all',
                    activeTab === tab.key
                        ? 'bg-slate-700/80 text-red-200 shadow-sm'
                        : 'text-slate-400 hover:bg-slate-700/50 hover:text-slate-200',
                ]"
            >
                <component :is="tab.icon" class="mr-1.5 h-3.5 w-3.5" />
                {{ tab.label }}
            </button>
        </div>

        <IntelSearchPanel v-if="activeTab === 'search'">
            <div class="mb-3 flex items-center gap-2 text-sm font-semibold">
                <Search class="h-4 w-4 text-red-400" />
                <span>{{ copy.search }}</span>
                <span class="text-xs font-normal text-muted-foreground">{{ copy.searchHint }}</span>
            </div>
            <div class="grid gap-3 md:grid-cols-5">
                <label class="md:col-span-2">
                    <span class="mb-1 block text-xs text-muted-foreground">{{ copy.query }}</span>
                    <input v-model="searchForm.q" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" :placeholder="copy.searchPlaceholder" />
                </label>
                <label>
                    <span class="mb-1 block text-xs text-muted-foreground">{{ copy.channelId }}</span>
                    <input v-model="searchForm.channelId" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
                </label>
                <label>
                    <span class="mb-1 block text-xs text-muted-foreground">{{ copy.order }}</span>
                    <select v-model="searchForm.order" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                        <option value="relevance">relevance</option>
                        <option value="date">date</option>
                        <option value="viewCount">viewCount</option>
                        <option value="rating">rating</option>
                    </select>
                </label>
                <label>
                    <span class="mb-1 block text-xs text-muted-foreground">{{ copy.limit }}</span>
                    <input v-model.number="searchForm.limit" type="number" min="1" max="50" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
                </label>
            </div>
            <button :disabled="loading || searchForm.q.trim() === ''" class="mt-3 inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60" @click="runSearch(false)">
                <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                {{ loading ? copy.loading : copy.find }}
            </button>
        </IntelSearchPanel>

        <IntelSearchPanel v-else-if="activeTab === 'analytics'">
            <div class="mb-3 flex items-center gap-2 text-sm font-semibold">
                <BarChart3 class="h-4 w-4 text-red-400" />
                <span>{{ copy.analytics }}</span>
                <span class="text-xs font-normal text-muted-foreground">{{ copy.analyticsHint }}</span>
            </div>
            <div class="grid gap-3 md:grid-cols-4">
                <label>
                    <span class="mb-1 block text-xs text-muted-foreground">Mode</span>
                    <select v-model="analyticsForm.mode" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                        <option value="video">video</option>
                        <option value="channel">channel</option>
                    </select>
                </label>
                <label>
                    <span class="mb-1 block text-xs text-muted-foreground">{{ copy.videoId }}</span>
                    <input v-model="analyticsForm.videoId" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
                </label>
                <label>
                    <span class="mb-1 block text-xs text-muted-foreground">{{ copy.channelId }}</span>
                    <input v-model="analyticsForm.channelId" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
                </label>
                <label>
                    <span class="mb-1 block text-xs text-muted-foreground">{{ copy.limit }}</span>
                    <input v-model.number="analyticsForm.limit" type="number" min="1" max="50" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
                </label>
            </div>
            <button :disabled="loading || (analyticsForm.videoId.trim() === '' && analyticsForm.channelId.trim() === '')" class="mt-3 inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60" @click="runAnalytics">
                <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                {{ loading ? copy.loading : copy.analyze }}
            </button>
        </IntelSearchPanel>

        <IntelSearchPanel v-else>
            <div class="mb-3 flex items-center gap-2 text-sm font-semibold">
                <MessageSquareText class="h-4 w-4 text-red-400" />
                <span>{{ copy.parser }}</span>
                <span class="text-xs font-normal text-muted-foreground">{{ copy.parserHint }}</span>
            </div>
            <div class="grid gap-3 md:grid-cols-4">
                <label>
                    <span class="mb-1 block text-xs text-muted-foreground">{{ copy.videoId }}</span>
                    <input v-model="parserForm.videoId" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
                </label>
                <label>
                    <span class="mb-1 block text-xs text-muted-foreground">{{ copy.query }}</span>
                    <input v-model="parserForm.searchTerms" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
                </label>
                <label>
                    <span class="mb-1 block text-xs text-muted-foreground">{{ copy.order }}</span>
                    <select v-model="parserForm.order" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                        <option value="relevance">relevance</option>
                        <option value="time">time</option>
                    </select>
                </label>
                <label>
                    <span class="mb-1 block text-xs text-muted-foreground">{{ copy.limit }}</span>
                    <input v-model.number="parserForm.limit" type="number" min="1" max="100" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
                </label>
            </div>
            <button :disabled="loading || parserForm.videoId.trim() === ''" class="mt-3 inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60" @click="runParser(false)">
                <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                {{ loading ? copy.loading : copy.parse }}
            </button>
        </IntelSearchPanel>

        <p v-if="error" class="text-sm text-destructive">{{ error }}</p>

        <IntelResultPanel>
            <div v-if="activeTab === 'search'" class="telegram-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
                <p v-if="!searchResult || searchResult.items.length === 0" class="intel-empty">{{ copy.empty }}</p>
                <article v-for="video in searchResult?.items ?? []" :key="video.id" class="rounded-lg border border-border/80 bg-background/70 p-3">
                    <div class="flex gap-3">
                        <img v-if="video.thumbnail" :src="video.thumbnail" :alt="video.title" class="h-24 w-40 rounded-md object-cover" loading="lazy" />
                        <div class="min-w-0 flex-1">
                            <h2 class="line-clamp-2 text-sm font-semibold">{{ video.title }}</h2>
                            <p class="mt-1 text-xs text-muted-foreground">{{ video.channelTitle }} · {{ copy.published }}: {{ formatDate(video.publishedAt) }}</p>
                            <p class="mt-2 line-clamp-2 text-xs text-muted-foreground">{{ video.description }}</p>
                            <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                <span>{{ copy.views }}: {{ fmt(video.views) }}</span>
                                <span>{{ copy.likes }}: {{ fmt(video.likes) }}</span>
                                <span>{{ copy.comments }}: {{ fmt(video.comments) }}</span>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a :href="video.url" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md border border-input px-3 py-1 text-xs hover:bg-accent">
                                    <ExternalLink class="h-3 w-3" /> {{ copy.open }}
                                </a>
                                <button type="button" class="rounded-md border border-input px-3 py-1 text-xs hover:bg-accent" @click="useVideoForAnalytics(video)">{{ copy.analytics }}</button>
                                <button type="button" class="rounded-md border border-input px-3 py-1 text-xs hover:bg-accent" @click="useVideoForParser(video)">{{ copy.parser }}</button>
                            </div>
                        </div>
                    </div>
                </article>
                <button v-if="searchResult?.pagination.nextPageToken" :disabled="loading" class="rounded-md border border-input px-4 py-2 text-sm hover:bg-accent disabled:opacity-60" @click="runSearch(true)">
                    {{ copy.loadMore }}
                </button>
            </div>

            <div v-else-if="activeTab === 'analytics'" class="telegram-scroll min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
                <p v-if="!analyticsResult" class="intel-empty">{{ copy.empty }}</p>
                <template v-else>
                    <section class="grid gap-3 md:grid-cols-5">
                        <div class="rounded-lg border border-border/80 bg-background/70 p-3">
                            <p class="text-xs text-muted-foreground">Videos</p>
                            <p class="text-xl font-semibold">{{ fmt(analyticsResult.totals.videos) }}</p>
                        </div>
                        <div class="rounded-lg border border-border/80 bg-background/70 p-3">
                            <p class="text-xs text-muted-foreground">{{ copy.views }}</p>
                            <p class="text-xl font-semibold">{{ fmt(analyticsResult.totals.views) }}</p>
                        </div>
                        <div class="rounded-lg border border-border/80 bg-background/70 p-3">
                            <p class="text-xs text-muted-foreground">{{ copy.likes }}</p>
                            <p class="text-xl font-semibold">{{ fmt(analyticsResult.totals.likes) }}</p>
                        </div>
                        <div class="rounded-lg border border-border/80 bg-background/70 p-3">
                            <p class="text-xs text-muted-foreground">{{ copy.avgViews }}</p>
                            <p class="text-xl font-semibold">{{ fmt(analyticsResult.totals.avgViews) }}</p>
                        </div>
                        <div class="rounded-lg border border-border/80 bg-background/70 p-3">
                            <p class="text-xs text-muted-foreground">{{ copy.engagement }}</p>
                            <p class="text-xl font-semibold">{{ analyticsResult.totals.engagementRate }}%</p>
                        </div>
                    </section>
                    <section>
                        <h2 class="mb-2 text-sm font-semibold">{{ copy.topVideos }}</h2>
                        <div class="space-y-2">
                            <article v-for="video in analyticsResult.topVideos" :key="video.id" class="rounded-lg border border-border/80 bg-background/70 p-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-sm font-medium">{{ video.title }}</h3>
                                        <p class="mt-1 text-xs text-muted-foreground">{{ copy.views }}: {{ fmt(video.views) }} · {{ copy.likes }}: {{ fmt(video.likes) }} · {{ copy.comments }}: {{ fmt(video.comments) }}</p>
                                    </div>
                                    <a :href="video.url" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md border border-input px-3 py-1 text-xs hover:bg-accent">
                                        <ExternalLink class="h-3 w-3" /> {{ copy.open }}
                                    </a>
                                </div>
                            </article>
                        </div>
                    </section>
                </template>
            </div>

            <div v-else class="telegram-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
                <p v-if="!commentsResult || commentsResult.items.length === 0" class="intel-empty">{{ copy.empty }}</p>
                <article v-for="comment in commentsResult?.items ?? []" :key="comment.id" class="rounded-lg border border-border/80 bg-background/70 p-3">
                    <div class="mb-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                        <a v-if="comment.authorChannelUrl" :href="comment.authorChannelUrl" target="_blank" rel="noopener noreferrer" class="text-primary">{{ comment.author }}</a>
                        <span v-else>{{ comment.author }}</span>
                        <span>{{ formatDate(comment.publishedAt) }}</span>
                        <span>{{ copy.likes }}: {{ fmt(comment.likeCount) }}</span>
                        <span>Replies: {{ fmt(comment.replyCount) }}</span>
                    </div>
                    <p class="whitespace-pre-wrap text-sm">{{ comment.text }}</p>
                    <div v-if="comment.replies.length > 0" class="mt-3 space-y-2 border-l border-border pl-3">
                        <article v-for="reply in comment.replies" :key="reply.id" class="text-xs">
                            <p class="font-medium">{{ reply.author }} · {{ formatDate(reply.publishedAt) }}</p>
                            <p class="mt-1 whitespace-pre-wrap text-muted-foreground">{{ reply.text }}</p>
                        </article>
                    </div>
                </article>
                <button v-if="commentsResult?.pagination.nextPageToken" :disabled="loading" class="rounded-md border border-input px-4 py-2 text-sm hover:bg-accent disabled:opacity-60" @click="runParser(true)">
                    {{ copy.loadMore }}
                </button>
            </div>
        </IntelResultPanel>
    </div>
</template>
