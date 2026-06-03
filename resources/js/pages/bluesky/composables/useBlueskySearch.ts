import { computed, ref } from 'vue';
import { apiRequest } from '@/lib/api';
import type {
    BlueskyActor,
    BlueskyActorDetailsState,
    BlueskyActorListPayload,
    BlueskyInteractionPayload,
    BlueskyInteractionState,
    BlueskyPostListPayload,
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

const createActorDetailsState = (): BlueskyActorDetailsState => ({
    postsOpen: false,
    followersOpen: false,
    followsOpen: false,
    postsLoading: false,
    followersLoading: false,
    followsLoading: false,
    postsLoadingMore: false,
    followersLoadingMore: false,
    followsLoadingMore: false,
    postsError: null,
    followersError: null,
    followsError: null,
    posts: [],
    followers: [],
    follows: [],
    postsHasMore: false,
    followersHasMore: false,
    followsHasMore: false,
    postsNextCursor: null,
    followersNextCursor: null,
    followsNextCursor: null,
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
    const actorDetailsByDid = ref<Record<string, BlueskyActorDetailsState>>({});

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

    const ensureActorDetailsState = (actorDid: string) => {
        if (!actorDetailsByDid.value[actorDid]) {
            actorDetailsByDid.value[actorDid] = createActorDetailsState();
        }

        return actorDetailsByDid.value[actorDid];
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

    const loadActorPosts = async (actor: BlueskyActor, append = false) => {
        const state = ensureActorDetailsState(actor.did);

        if ((append && state.postsLoadingMore) || (!append && state.postsLoading)) {
            return;
        }

        if (append) {
            state.postsLoadingMore = true;
        } else {
            state.postsLoading = true;
            state.postsError = null;
        }

        const response = await apiRequest<BlueskyPostListPayload>('/bluesky/actors/feed', {
            query: {
                actor: actor.did || actor.handle,
                limit: form.value.limit,
                cursor: append ? (state.postsNextCursor ?? undefined) : undefined,
            },
        });

        state.postsLoading = false;
        state.postsLoadingMore = false;

        if (!response.ok) {
            state.postsError = response.message ?? t('bluesky.errors.requestFailed');

            return;
        }

        state.posts = append ? [...state.posts, ...response.data.items] : response.data.items;
        state.postsHasMore = response.data.pagination.hasMore;
        state.postsNextCursor = response.data.pagination.nextCursor;
    };

    const loadActorFollowers = async (actor: BlueskyActor, append = false) => {
        const state = ensureActorDetailsState(actor.did);

        if ((append && state.followersLoadingMore) || (!append && state.followersLoading)) {
            return;
        }

        if (append) {
            state.followersLoadingMore = true;
        } else {
            state.followersLoading = true;
            state.followersError = null;
        }

        const response = await apiRequest<BlueskyActorListPayload>('/bluesky/actors/followers', {
            query: {
                actor: actor.did || actor.handle,
                limit: form.value.limit,
                cursor: append ? (state.followersNextCursor ?? undefined) : undefined,
            },
        });

        state.followersLoading = false;
        state.followersLoadingMore = false;

        if (!response.ok) {
            state.followersError = response.message ?? t('bluesky.errors.requestFailed');

            return;
        }

        state.followers = append ? [...state.followers, ...response.data.items] : response.data.items;
        state.followersHasMore = response.data.pagination.hasMore;
        state.followersNextCursor = response.data.pagination.nextCursor;
    };

    const loadActorFollows = async (actor: BlueskyActor, append = false) => {
        const state = ensureActorDetailsState(actor.did);

        if ((append && state.followsLoadingMore) || (!append && state.followsLoading)) {
            return;
        }

        if (append) {
            state.followsLoadingMore = true;
        } else {
            state.followsLoading = true;
            state.followsError = null;
        }

        const response = await apiRequest<BlueskyActorListPayload>('/bluesky/actors/follows', {
            query: {
                actor: actor.did || actor.handle,
                limit: form.value.limit,
                cursor: append ? (state.followsNextCursor ?? undefined) : undefined,
            },
        });

        state.followsLoading = false;
        state.followsLoadingMore = false;

        if (!response.ok) {
            state.followsError = response.message ?? t('bluesky.errors.requestFailed');

            return;
        }

        state.follows = append ? [...state.follows, ...response.data.items] : response.data.items;
        state.followsHasMore = response.data.pagination.hasMore;
        state.followsNextCursor = response.data.pagination.nextCursor;
    };

    const toggleActorPosts = async (actor: BlueskyActor) => {
        const state = ensureActorDetailsState(actor.did);
        state.postsOpen = !state.postsOpen;

        if (!state.postsOpen || state.postsLoading || state.posts.length > 0) {
            return;
        }

        await loadActorPosts(actor);
    };

    const toggleActorFollowers = async (actor: BlueskyActor) => {
        const state = ensureActorDetailsState(actor.did);
        state.followersOpen = !state.followersOpen;

        if (!state.followersOpen || state.followersLoading || state.followers.length > 0) {
            return;
        }

        await loadActorFollowers(actor);
    };

    const toggleActorFollows = async (actor: BlueskyActor) => {
        const state = ensureActorDetailsState(actor.did);
        state.followsOpen = !state.followsOpen;

        if (!state.followsOpen || state.followsLoading || state.follows.length > 0) {
            return;
        }

        await loadActorFollows(actor);
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
        ensureActorDetailsState,
        toggleLikes,
        toggleReposts,
        toggleThread,
        loadLikes,
        loadReposts,
        toggleActorPosts,
        toggleActorFollowers,
        toggleActorFollows,
        loadActorPosts,
        loadActorFollowers,
        loadActorFollows,
    };
};
