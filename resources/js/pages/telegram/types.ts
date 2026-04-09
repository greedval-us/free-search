export type TelegramTabValue = 'search' | 'analytics';

export type SearchItem = {
    id: number;
    date: number;
    message: string;
    fromId: number;
    authorId: number | null;
    peerId: number;
    views: number | null;
    forwards: number | null;
    postAuthor: string | null;
    authorSignature: string | null;
    repliesCount: number | null;
    telegramUrl: string | null;
    media: {
        hasMedia: boolean;
        type: string;
        label: string;
        rawType: string | null;
    };
    reactions: Array<{
        key: string;
        emoji: string;
        count: number;
        senderIds: number[];
    }>;
    reactionSenderIds: number[];
    gifts: {
        hasGift: boolean;
        types: string[];
        senderIds: number[];
        entries: Array<{
            key: string;
            label: string;
            senderIds: number[];
        }>;
    };
};

export type SearchResponse = {
    ok: boolean;
    message?: string;
    items: SearchItem[];
    pagination: {
        limit: number;
        offsetId: number;
        nextOffsetId: number | null;
        hasMore: boolean;
        total: number;
    };
};

export type CommentItem = {
    id: number;
    date: number;
    message: string;
    authorId: number | null;
};

export type CommentState = {
    open: boolean;
    loading: boolean;
    loaded: boolean;
    error: string | null;
    items: CommentItem[];
    total: number;
    hasMore: boolean;
    nextOffsetId: number | null;
};
