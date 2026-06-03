import type { Ref } from 'vue';
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
import {
    BlueskyPostEngagementKind as PostEngagementKind,
    BlueskySearchSort as SearchSort,
    BlueskySearchType as SearchType,
} from '../types';

type TranslateFn = (key: string) => string;

const LIMIT_MIN = 1;
const LIMIT_MAX = 25;
const DEFAULT_LIMIT = 10;

const createSearchForm = (): BlueskySearchForm => ({
    q: '',
    type: SearchType.Posts,
    limit: DEFAULT_LIMIT,
    cursor: '',
    sort: SearchSort.Latest,
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

type PagedPayload<TItem> = {
    items: TItem[];
    pagination: {
        hasMore: boolean;
        nextCursor: string | null;
    };
};

type ActorDetailHandler = {
    load: (actor: BlueskyActor, append?: boolean) => Promise<void>;
    toggle: (actor: BlueskyActor) => Promise<void>;
};

const ensureStateInMap = <TState>(
    store: Ref<Record<string, TState>>,
    key: string,
    createState: () => TState
): TState => {
    if (!store.value[key]) {
        store.value[key] = createState();
    }

    return store.value[key];
};

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
        () =>
            (result.value?.posts.length ?? 0) +
            (result.value?.actors.length ?? 0)
    );
    const hasMore = computed(() => {
        if (!result.value) {
            return false;
        }

        if (form.value.type === SearchType.Posts) {
            return result.value.pagination.posts.hasMore;
        }

        if (form.value.type === SearchType.Actors) {
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

        if (form.value.type === SearchType.Posts) {
            return result.value.pagination.posts.nextCursor ?? '';
        }

        if (form.value.type === SearchType.Actors) {
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

    const ensureInteractionState = (kind: PostEngagementKind, postId: string) =>
        ensureStateInMap(
            kind === PostEngagementKind.Likes ? likesByPostId : repostsByPostId,
            postId,
            createInteractionState
        );

    const ensureLikesState = (postId: string) =>
        ensureInteractionState(PostEngagementKind.Likes, postId);

    const ensureRepostsState = (postId: string) =>
        ensureInteractionState(PostEngagementKind.Reposts, postId);

    const ensureThreadState = (postId: string) => {
        if (!threadByPostId.value[postId]) {
            threadByPostId.value[postId] = createThreadState();
        }

        return threadByPostId.value[postId];
    };

    const ensureActorDetailsState = (actorDid: string) =>
        ensureStateInMap(actorDetailsByDid, actorDid, createActorDetailsState);

    const loadInteraction = async (
        kind: PostEngagementKind,
        post: BlueskyPost,
        append = false
    ) => {
        const state = ensureInteractionState(kind, post.id);

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
            kind === PostEngagementKind.Likes
                ? '/bluesky/posts/likes'
                : '/bluesky/posts/reposts',
            {
                query: {
                    uri: post.uri,
                    limit: form.value.limit,
                    cursor: append
                        ? (state.nextCursor ?? undefined)
                        : undefined,
                    ...(kind === PostEngagementKind.Likes
                        ? { cid: post.cid }
                        : {}),
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

    const loadLikes = async (post: BlueskyPost, append = false) =>
        loadInteraction(PostEngagementKind.Likes, post, append);

    const loadReposts = async (post: BlueskyPost, append = false) =>
        loadInteraction(PostEngagementKind.Reposts, post, append);

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

    const toggleInteraction = async (
        kind: PostEngagementKind,
        post: BlueskyPost
    ) => {
        const state = ensureInteractionState(kind, post.id);
        state.open = !state.open;

        if (!state.open || state.loading || state.items.length > 0) {
            return;
        }

        await loadInteraction(kind, post);
    };

    const toggleLikes = async (post: BlueskyPost) =>
        toggleInteraction(PostEngagementKind.Likes, post);

    const toggleReposts = async (post: BlueskyPost) =>
        toggleInteraction(PostEngagementKind.Reposts, post);

    const toggleThread = async (post: BlueskyPost) => {
        const state = ensureThreadState(post.id);
        state.open = !state.open;

        if (!state.open || state.loading || state.root !== null) {
            return;
        }

        await loadThread(post);
    };

    const createActorDetailHandler = <
        TItem,
        TPayload extends PagedPayload<TItem>,
    >(options: {
        endpoint:
            | '/bluesky/actors/feed'
            | '/bluesky/actors/followers'
            | '/bluesky/actors/follows';
        isOpen: (state: BlueskyActorDetailsState) => boolean;
        setOpen: (state: BlueskyActorDetailsState, value: boolean) => void;
        isLoading: (state: BlueskyActorDetailsState) => boolean;
        setLoading: (state: BlueskyActorDetailsState, value: boolean) => void;
        isLoadingMore: (state: BlueskyActorDetailsState) => boolean;
        setLoadingMore: (
            state: BlueskyActorDetailsState,
            value: boolean
        ) => void;
        setError: (
            state: BlueskyActorDetailsState,
            value: string | null
        ) => void;
        getItems: (state: BlueskyActorDetailsState) => TItem[];
        setItems: (state: BlueskyActorDetailsState, items: TItem[]) => void;
        setHasMore: (state: BlueskyActorDetailsState, value: boolean) => void;
        getNextCursor: (state: BlueskyActorDetailsState) => string | null;
        setNextCursor: (
            state: BlueskyActorDetailsState,
            value: string | null
        ) => void;
    }): ActorDetailHandler => {
        const load = async (actor: BlueskyActor, append = false) => {
            const state = ensureActorDetailsState(actor.did);

            if (
                (append && options.isLoadingMore(state)) ||
                (!append && options.isLoading(state))
            ) {
                return;
            }

            if (append) {
                options.setLoadingMore(state, true);
            } else {
                options.setLoading(state, true);
                options.setError(state, null);
            }

            const response = await apiRequest<TPayload>(options.endpoint, {
                query: {
                    actor: actor.did || actor.handle,
                    limit: form.value.limit,
                    cursor: append
                        ? (options.getNextCursor(state) ?? undefined)
                        : undefined,
                },
            });

            options.setLoading(state, false);
            options.setLoadingMore(state, false);

            if (!response.ok) {
                options.setError(
                    state,
                    response.message ?? t('bluesky.errors.requestFailed')
                );

                return;
            }

            const items = append
                ? [...options.getItems(state), ...response.data.items]
                : response.data.items;

            options.setItems(state, items);
            options.setHasMore(state, response.data.pagination.hasMore);
            options.setNextCursor(state, response.data.pagination.nextCursor);
        };

        const toggle = async (actor: BlueskyActor) => {
            const state = ensureActorDetailsState(actor.did);
            const nextOpenState = !options.isOpen(state);
            options.setOpen(state, nextOpenState);

            if (
                !nextOpenState ||
                options.isLoading(state) ||
                options.getItems(state).length > 0
            ) {
                return;
            }

            await load(actor);
        };

        return { load, toggle };
    };

    const actorPostsHandler = createActorDetailHandler<
        BlueskyPost,
        BlueskyPostListPayload
    >({
        endpoint: '/bluesky/actors/feed',
        isOpen: (state) => state.postsOpen,
        setOpen: (state, value) => {
            state.postsOpen = value;
        },
        isLoading: (state) => state.postsLoading,
        setLoading: (state, value) => {
            state.postsLoading = value;
        },
        isLoadingMore: (state) => state.postsLoadingMore,
        setLoadingMore: (state, value) => {
            state.postsLoadingMore = value;
        },
        setError: (state, value) => {
            state.postsError = value;
        },
        getItems: (state) => state.posts,
        setItems: (state, items) => {
            state.posts = items;
        },
        setHasMore: (state, value) => {
            state.postsHasMore = value;
        },
        getNextCursor: (state) => state.postsNextCursor,
        setNextCursor: (state, value) => {
            state.postsNextCursor = value;
        },
    });

    const actorFollowersHandler = createActorDetailHandler<
        BlueskyActor,
        BlueskyActorListPayload
    >({
        endpoint: '/bluesky/actors/followers',
        isOpen: (state) => state.followersOpen,
        setOpen: (state, value) => {
            state.followersOpen = value;
        },
        isLoading: (state) => state.followersLoading,
        setLoading: (state, value) => {
            state.followersLoading = value;
        },
        isLoadingMore: (state) => state.followersLoadingMore,
        setLoadingMore: (state, value) => {
            state.followersLoadingMore = value;
        },
        setError: (state, value) => {
            state.followersError = value;
        },
        getItems: (state) => state.followers,
        setItems: (state, items) => {
            state.followers = items;
        },
        setHasMore: (state, value) => {
            state.followersHasMore = value;
        },
        getNextCursor: (state) => state.followersNextCursor,
        setNextCursor: (state, value) => {
            state.followersNextCursor = value;
        },
    });

    const actorFollowsHandler = createActorDetailHandler<
        BlueskyActor,
        BlueskyActorListPayload
    >({
        endpoint: '/bluesky/actors/follows',
        isOpen: (state) => state.followsOpen,
        setOpen: (state, value) => {
            state.followsOpen = value;
        },
        isLoading: (state) => state.followsLoading,
        setLoading: (state, value) => {
            state.followsLoading = value;
        },
        isLoadingMore: (state) => state.followsLoadingMore,
        setLoadingMore: (state, value) => {
            state.followsLoadingMore = value;
        },
        setError: (state, value) => {
            state.followsError = value;
        },
        getItems: (state) => state.follows,
        setItems: (state, items) => {
            state.follows = items;
        },
        setHasMore: (state, value) => {
            state.followsHasMore = value;
        },
        getNextCursor: (state) => state.followsNextCursor,
        setNextCursor: (state, value) => {
            state.followsNextCursor = value;
        },
    });

    const loadActorPosts = actorPostsHandler.load;
    const loadActorFollowers = actorFollowersHandler.load;
    const loadActorFollows = actorFollowsHandler.load;

    const toggleActorPosts = actorPostsHandler.toggle;
    const toggleActorFollowers = actorFollowersHandler.toggle;
    const toggleActorFollows = actorFollowsHandler.toggle;

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
