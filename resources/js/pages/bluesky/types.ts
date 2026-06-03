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

export type BlueskyPost = {
    id: string;
    uri: string;
    cid: string;
    url: string;
    text: string;
    createdAt: string;
    indexedAt: string;
    replyCount: number;
    repostCount: number;
    likeCount: number;
    quoteCount: number;
    languages: string[];
    hashtags: string[];
    mentions: string[];
    links: string[];
    domains: string[];
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
