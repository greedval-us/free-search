export type MastodonTabValue = 'search';

export type MastodonStatus = {
    id: string;
    url: string;
    uri: string;
    createdAt: string;
    content: string;
    language: string;
    visibility: string;
    sensitive: boolean;
    repliesCount: number;
    reblogsCount: number;
    favouritesCount: number;
    mediaAttachmentsCount: number;
    tags: string[];
    account: {
        id: string;
        username: string;
        acct: string;
        displayName: string;
        url: string;
        avatar: string;
    };
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
    note: string;
    followersCount: number;
    followingCount: number;
    statusesCount: number;
    lastStatusAt: string;
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
