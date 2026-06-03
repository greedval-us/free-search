export type BlueskyTabValue = 'search';

export type BlueskyActor = {
    did: string;
    handle: string;
    displayName: string;
    description: string;
    avatar: string;
    banner: string;
    url: string;
    followersCount: number;
    followsCount: number;
    postsCount: number;
    indexedAt: string;
    createdAt: string;
    labels: string[];
};

export type BlueskyActorListPayload = {
    items: BlueskyActor[];
    pagination: {
        limit: number;
        cursor: string | null;
        nextCursor: string | null;
        hasMore: boolean;
    };
    meta: {
        actor: string;
        kind: string;
    };
};

export type BlueskyPost = {
    id: string;
    uri: string;
    cid: string;
    url: string;
    text: string;
    createdAt: string;
    indexedAt: string;
    labels: string[];
    replyCount: number;
    repostCount: number;
    likeCount: number;
    quoteCount: number;
    replyRootUri: string;
    replyParentUri: string;
    languages: string[];
    hashtags: string[];
    mentions: string[];
    links: string[];
    domains: string[];
    media: {
        type: string;
        images: Array<{
            thumb: string;
            fullsize: string;
            alt: string;
        }>;
        video: {
            playlist: string;
            thumbnail: string;
            alt: string;
        };
        external: {
            uri: string;
            title: string;
            description: string;
            thumb: string;
        };
    };
    hasMedia: boolean;
    hasLinks: boolean;
    postType: string;
    author: BlueskyActor;
};

export type BlueskySearchType = 'all' | 'posts' | 'actors';
export type BlueskySearchSort = 'top' | 'latest';

export type BlueskySearchForm = {
    q: string;
    type: BlueskySearchType;
    limit: number;
    cursor: string;
    sort: BlueskySearchSort;
    language: string;
    author: string;
    mentions: string;
    domain: string;
    url: string;
    tag: string;
    since: string;
    until: string;
};

export type BlueskySearchPayload = {
    posts: BlueskyPost[];
    actors: BlueskyActor[];
    meta: {
        query: string;
        type: BlueskySearchType | string;
        limit: number;
        sort: BlueskySearchSort | string;
    };
    pagination: {
        cursor: string | null;
        posts: {
            nextCursor: string | null;
            hasMore: boolean;
        };
        actors: {
            nextCursor: string | null;
            hasMore: boolean;
        };
    };
};

export type BlueskyInteractionItem = {
    actor: BlueskyActor;
    createdAt: string;
    indexedAt: string;
};

export type BlueskyInteractionPayload = {
    items: BlueskyInteractionItem[];
    pagination: {
        limit: number;
        cursor: string | null;
        nextCursor: string | null;
        hasMore: boolean;
    };
    meta: {
        uri: string;
        kind: string;
    };
};

export type BlueskyThreadNode = BlueskyPost & {
    replies: BlueskyThreadNode[];
};

export type BlueskyThreadPayload = {
    root: BlueskyThreadNode | null;
    ancestors: BlueskyPost[];
    replies: BlueskyThreadNode[];
    meta: {
        uri: string;
        depth: number;
        parentHeight: number;
    };
};

export type BlueskyInteractionState = {
    open: boolean;
    loading: boolean;
    loadingMore: boolean;
    error: string | null;
    items: BlueskyInteractionItem[];
    hasMore: boolean;
    nextCursor: string | null;
};

export type BlueskyPostListPayload = {
    items: BlueskyPost[];
    pagination: {
        limit: number;
        cursor: string | null;
        nextCursor: string | null;
        hasMore: boolean;
    };
    meta: {
        actor: string;
        filter?: string | null;
    };
};

export type BlueskyThreadState = {
    open: boolean;
    loading: boolean;
    error: string | null;
    root: BlueskyThreadNode | null;
    ancestors: BlueskyPost[];
    replies: BlueskyThreadNode[];
};

export type BlueskyActorDetailsState = {
    postsOpen: boolean;
    followersOpen: boolean;
    followsOpen: boolean;
    postsLoading: boolean;
    followersLoading: boolean;
    followsLoading: boolean;
    postsLoadingMore: boolean;
    followersLoadingMore: boolean;
    followsLoadingMore: boolean;
    postsError: string | null;
    followersError: string | null;
    followsError: string | null;
    posts: BlueskyPost[];
    followers: BlueskyActor[];
    follows: BlueskyActor[];
    postsHasMore: boolean;
    followersHasMore: boolean;
    followsHasMore: boolean;
    postsNextCursor: string | null;
    followersNextCursor: string | null;
    followsNextCursor: string | null;
};
