import { computed, ref } from 'vue';
import { apiRequest } from '@/lib/api';
import type {
    BlueskyInteractionPayload,
    BlueskyInteractionState,
    BlueskySearchForm,
    BlueskySearchPayload,
    BlueskyPost,
    BlueskyThreadPayload,
    BlueskyThreadState,
} from '../types';

type TranslateFn = (key: string) => string;

const LIMIT_MIN = 1;
const LIMIT_MAX = 25;
const DEFAULT_LIMIT = 10;

const createSearchForm = (): BlueskySearchForm => ({
    q: '',
    type: 'posts',
    limit: DEFAULT_LIMIT,
    cursor: '',
    sort: 'latest',
    language: '',
    author: '',
    mentions: '',
    domain: '',
    url: '',
    tag: '',
    since: '',
    until: '',
});

const createInteractionState = (): BlueskyInteractionState => ({
    open: false,
    loading: false,
    loadingMore: false,
    error: null,
    items: [],
    hasMore: false,
    nextCursor: null,
});

const createThreadState = (): BlueskyThreadState => ({
    open: false,
    loading: false,
    error: null,
    root: null,
    ancestors: [],
    replies: [],
});

export const useBlueskySearch = (t: TranslateFn) => {
    const form = ref<BlueskySearchForm>(createSearchForm());
    const loading = ref(false);
    const loadingMore = ref(false);
    const error = ref<string | null>(null);
    const result = ref<BlueskySearchPayload | null>(null);
    const showAdvanced = ref(false);
    const searchPanelCollapsed = ref(false);
    const likesByPostId = ref<Record<string, BlueskyInteractionState>>({});
    const repostsByPostId = ref<Record<string, BlueskyInteractionState>>({});
    const threadByPostId = ref<Record<string, BlueskyThreadState>>({});

    const canSearch = computed(() => form.value.q.trim().length > 0);
    const totalShown = computed(
        () => (result.value?.posts.length ?? 0) + (result.value?.actors.length ?? 0)
    );
    const hasMore = computed(() => {
        if (!result.value) {
            return false;
        }

        if (form.value.type === 'posts') {
            return result.value.pagination.posts.hasMore;
        }

        if (form.value.type === 'actors') {
            return result.value.pagination.actors.hasMore;
        }

        return (
            result.value.pagination.posts.hasMore ||
            result.value.pagination.actors.hasMore
        );
    });

    const clampLimit = () => {
        const value = Number(form.value.limit);
        form.value.limit = Number.isFinite(value)
            ? Math.min(LIMIT_MAX, Math.max(LIMIT_MIN, Math.trunc(value)))
            : DEFAULT_LIMIT;
    };

    const nextCursor = () => {
        if (!result.value) {
            return '';
        }

        if (form.value.type === 'posts') {
            return result.value.pagination.posts.nextCursor ?? '';
        }

        if (form.value.type === 'actors') {
            return result.value.pagination.actors.nextCursor ?? '';
        }

        return (
            result.value.pagination.posts.nextCursor ??
            result.value.pagination.actors.nextCursor ??
            ''
        );
    };

    const runSearch = async (append = false) => {
        if (append) {
            loadingMore.value = true;
        } else {
            loading.value = true;
            error.value = null;
            form.value.cursor = '';
        }

        const response = await apiRequest<BlueskySearchPayload>(
            '/bluesky/search',
            {
                query: {
                    q: form.value.q,
                    type: form.value.type,
                    limit: form.value.limit,
                    cursor: append ? nextCursor() || undefined : undefined,
                    sort: form.value.sort,
                    language: form.value.language || undefined,
                    author: form.value.author || undefined,
                    mentions: form.value.mentions || undefined,
                    domain: form.value.domain || undefined,
                    url: form.value.url || undefined,
                    tag: form.value.tag || undefined,
                    since: form.value.since || undefined,
                    until: form.value.until || undefined,
                },
            }
        );

        loading.value = false;
        loadingMore.value = false;

        if (!response.ok) {
            error.value = response.message ?? t('bluesky.errors.requestFailed');

            return;
        }

        if (append && result.value) {
            result.value = {
                ...response.data,
                posts: [...result.value.posts, ...response.data.posts],
                actors: [...result.value.actors, ...response.data.actors],
            };

            return;
        }

        result.value = response.data;
    };

    const formatDate = (value: string) =>
        value ? new Date(value).toLocaleString() : '-';

    const ensureLikesState = (postId: string) => {
        if (!likesByPostId.value[postId]) {
            likesByPostId.value[postId] = createInteractionState();
        }

        return likesByPostId.value[postId];
    };

    const ensureRepostsState = (postId: string) => {
        if (!repostsByPostId.value[postId]) {
            repostsByPostId.value[postId] = createInteractionState();
        }

        return repostsByPostId.value[postId];
    };

    const ensureThreadState = (postId: string) => {
        if (!threadByPostId.value[postId]) {
            threadByPostId.value[postId] = createThreadState();
        }

        return threadByPostId.value[postId];
    };

    const loadLikes = async (post: BlueskyPost, append = false) => {
        const state = ensureLikesState(post.id);

        if ((append && state.loadingMore) || (!append && state.loading)) {
            return;
        }

        if (append) {
            state.loadingMore = true;
        } else {
            state.loading = true;
            state.error = null;
        }

        const response = await apiRequest<BlueskyInteractionPayload>(
            '/bluesky/posts/likes',
            {
                query: {
                    uri: post.uri,
                    cid: post.cid,
                    limit: form.value.limit,
                    cursor: append ? (state.nextCursor ?? undefined) : undefined,
                },
            }
        );

        state.loading = false;
        state.loadingMore = false;

        if (!response.ok) {
            state.error = response.message ?? t('bluesky.errors.requestFailed');

            return;
        }

        state.items = append
            ? [...state.items, ...response.data.items]
            : response.data.items;
        state.hasMore = response.data.pagination.hasMore;
        state.nextCursor = response.data.pagination.nextCursor;
    };

    const loadReposts = async (post: BlueskyPost, append = false) => {
        const state = ensureRepostsState(post.id);

        if ((append && state.loadingMore) || (!append && state.loading)) {
            return;
        }

        if (append) {
            state.loadingMore = true;
        } else {
            state.loading = true;
            state.error = null;
        }

        const response = await apiRequest<BlueskyInteractionPayload>(
            '/bluesky/posts/reposts',
            {
                query: {
                    uri: post.uri,
                    limit: form.value.limit,
                    cursor: append ? (state.nextCursor ?? undefined) : undefined,
                },
            }
        );

        state.loading = false;
        state.loadingMore = false;

        if (!response.ok) {
            state.error = response.message ?? t('bluesky.errors.requestFailed');

            return;
        }

        state.items = append
            ? [...state.items, ...response.data.items]
            : response.data.items;
        state.hasMore = response.data.pagination.hasMore;
        state.nextCursor = response.data.pagination.nextCursor;
    };

    const loadThread = async (post: BlueskyPost) => {
        const state = ensureThreadState(post.id);

        if (state.loading) {
            return;
        }

        state.loading = true;
        state.error = null;

        const response = await apiRequest<BlueskyThreadPayload>(
            '/bluesky/posts/thread',
            {
                query: {
                    uri: post.uri,
                    depth: 6,
                    parentHeight: 6,
                },
            }
        );

        state.loading = false;

        if (!response.ok) {
            state.error = response.message ?? t('bluesky.errors.requestFailed');

            return;
        }

        state.root = response.data.root;
        state.ancestors = response.data.ancestors;
        state.replies = response.data.replies;
    };

    const toggleLikes = async (post: BlueskyPost) => {
        const state = ensureLikesState(post.id);
        state.open = !state.open;

        if (!state.open || state.loading || state.items.length > 0) {
            return;
        }

        await loadLikes(post);
    };

    const toggleReposts = async (post: BlueskyPost) => {
        const state = ensureRepostsState(post.id);
        state.open = !state.open;

        if (!state.open || state.loading || state.items.length > 0) {
            return;
        }

        await loadReposts(post);
    };

    const toggleThread = async (post: BlueskyPost) => {
        const state = ensureThreadState(post.id);
        state.open = !state.open;

        if (!state.open || state.loading || state.root !== null) {
            return;
        }

        await loadThread(post);
    };

    return {
        limitMax: LIMIT_MAX,
        form,
        loading,
        loadingMore,
        error,
        result,
        showAdvanced,
        searchPanelCollapsed,
        canSearch,
        totalShown,
        hasMore,
        clampLimit,
        runSearch,
        formatDate,
        ensureLikesState,
        ensureRepostsState,
        ensureThreadState,
        toggleLikes,
        toggleReposts,
        toggleThread,
        loadLikes,
        loadReposts,
    };
};
