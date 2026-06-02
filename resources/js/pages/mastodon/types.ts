export type MastodonTabValue = 'search';

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

export type MastodonStatusContextPayload = {
    ancestors: MastodonStatus[];
    descendants: MastodonStatus[];
    descendantsTree: MastodonStatusThreadNode[];
};
