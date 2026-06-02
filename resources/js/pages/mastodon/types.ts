export type MastodonTabValue = 'search' | 'analytics' | 'parser';

export type MastodonStatus = {
    id: string;
    url: string;
    uri: string;
    inReplyToId: string | null;
    createdAt: string;
    content: string;
    spoilerText: string;
    language: string;
    visibility: string;
    sensitive: boolean;
    repliesCount: number;
    reblogsCount: number;
    favouritesCount: number;
    mediaAttachmentsCount: number;
    hasMedia: boolean;
    hasLinks: boolean;
    postType: 'original' | 'reply' | 'boost' | string;
    instanceDomain: string;
    links: string[];
    domains: string[];
    mentions: Array<{
        id: string;
        username: string;
        acct: string;
        url: string;
    }>;
    tags: string[];
    account: {
        id: string;
        username: string;
        acct: string;
        displayName: string;
        url: string;
        avatar: string;
        instanceDomain: string;
    };
};

export type MastodonStatusThreadNode = MastodonStatus & {
    replies: MastodonStatusThreadNode[];
};

export type MastodonAccount = {
    id: string;
    username: string;
    acct: string;
    displayName: string;
    url: string;
    avatar: string;
    header: string;
    discoverable: boolean;
    locked: boolean;
    bot: boolean;
    group: boolean;
    note: string;
    createdAt: string;
    followersCount: number;
    followingCount: number;
    statusesCount: number;
    lastStatusAt: string;
    instanceDomain: string;
    fields: Array<{
        name: string;
        value: string;
        verifiedAt: string;
    }>;
};

export type MastodonHashtag = {
    id: string;
    name: string;
    url: string;
    history: Array<{
        day: string;
        uses: number;
        accounts: number;
    }>;
};

export type MastodonSearchPayload = {
    statuses: MastodonStatus[];
    accounts: MastodonAccount[];
    hashtags: MastodonHashtag[];
    pagination: {
        query: string;
        type: string;
        limit: number;
        offset: number;
        nextOffset: number | null;
        hasMore: boolean;
    };
};

export type MastodonSearchType = 'statuses' | 'accounts' | 'hashtags' | 'all';

export type MastodonSearchForm = {
    q: string;
    type: MastodonSearchType;
    limit: number;
    resolve: boolean;
    author: string;
    dateFrom: string;
    dateTo: string;
    language: string;
    hasMedia: string;
    hasLinks: string;
    hasReplies: string;
    instanceDomain: string;
};

export type MastodonStatusContextPayload = {
    ancestors: MastodonStatus[];
    descendants: MastodonStatus[];
    descendantsTree: MastodonStatusThreadNode[];
};

export type MastodonStatusContextState = {
    open: boolean;
    loading: boolean;
    error: string | null;
    ancestors: MastodonStatus[];
    descendants: MastodonStatus[];
    descendantsTree: MastodonStatusThreadNode[];
};

export type MastodonAccountStatusesPayload = {
    statuses: MastodonStatus[];
    pagination: {
        limit: number;
        maxId: string | null;
        nextMaxId: string | null;
        hasMore: boolean;
    };
};

export type MastodonAccountFollowersPayload = {
    accounts: MastodonAccount[];
    pagination: {
        limit: number;
        maxId: string | null;
        nextMaxId: string | null;
        hasMore: boolean;
    };
};

export type MastodonAccountDetailsState = {
    statusesOpen: boolean;
    followersOpen: boolean;
    statusesLoading: boolean;
    followersLoading: boolean;
    statusesLoadingMore: boolean;
    followersLoadingMore: boolean;
    statusesError: string | null;
    followersError: string | null;
    statuses: MastodonStatus[];
    followers: MastodonAccount[];
    statusesHasMore: boolean;
    followersHasMore: boolean;
    statusesNextMaxId: string | null;
    followersNextMaxId: string | null;
};

export type MastodonTagTimelinePayload = {
    statuses: MastodonStatus[];
    analytics: {
        uniqueAccountsCount: number;
        uniqueAccounts: Array<{
            id: string;
            username?: string;
            acct: string;
            displayName?: string;
            url?: string;
            avatar?: string;
            instanceDomain?: string;
        }>;
        uniqueInstancesCount: number;
        instanceDomains: string[];
        postsWithMediaCount: number;
        postsWithLinksCount: number;
    };
    pagination: {
        limit: number;
        maxId: string | null;
        nextMaxId: string | null;
        hasMore: boolean;
    };
};

export type MastodonHashtagDetailsState = {
    open: boolean;
    loading: boolean;
    loadingMore: boolean;
    error: string | null;
    statuses: MastodonStatus[];
    uniqueAccountsCount: number;
    uniqueAccounts: Array<{
        id: string;
        username?: string;
        acct: string;
        displayName?: string;
        url?: string;
        avatar?: string;
        instanceDomain?: string;
    }>;
    uniqueInstancesCount: number;
    instanceDomains: string[];
    postsWithMediaCount: number;
    postsWithLinksCount: number;
    hasMore: boolean;
    nextMaxId: string | null;
};

export type MastodonAnalyticsPayload = {
    profile: MastodonAccount | MastodonHashtag | null;
    meta: {
        mode: 'account' | 'hashtag' | string;
        target: string;
        resolvedTarget: string;
        pagesRequested: number;
        pagesLoaded: number;
        sampledPosts: number;
    };
    summary: {
        postsCount: number;
        uniqueAccountsCount: number;
        uniqueInstancesCount: number;
        uniqueLanguagesCount: number;
        postsWithMediaCount: number;
        postsWithLinksCount: number;
        replyPostsCount: number;
        boostPostsCount: number;
        sensitivePostsCount: number;
        totalReplies: number;
        totalReblogs: number;
        totalFavourites: number;
    };
    timeline: Array<{
        day: string;
        posts: number;
        postsWithMedia: number;
        postsWithLinks: number;
        replies: number;
        reblogs: number;
        favourites: number;
    }>;
    topDomains: Array<{
        domain: string;
        count: number;
    }>;
    topTags: Array<{
        tag: string;
        count: number;
    }>;
    topAccounts: Array<
        MastodonStatus['account'] & {
            count: number;
        }
    >;
    topMentions: Array<{
        id: string;
        username: string;
        acct: string;
        url: string;
        count: number;
    }>;
    topLanguages: Array<{
        language: string;
        count: number;
    }>;
    topPosts: MastodonStatus[];
};
