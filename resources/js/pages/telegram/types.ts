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

export type TelegramAnalyticsSummary = {
    range: {
        chatUsername: string;
        dateFrom: string;
        dateTo: string;
        label: string;
        periodDays: number;
        groupBy: 'hour' | 'day';
        keyword?: string | null;
    };
    groupInfo: {
        id: number | null;
        title: string;
        username: string | null;
        type: 'channel' | 'group' | 'forum' | 'gigagroup' | 'chat';
        description: string | null;
        participantsCount: number | null;
        onlineCount: number | null;
        verified: boolean;
        restricted: boolean;
        scam: boolean;
        createdAt: number | null;
        linkedChatId: number | null;
        canViewStats: boolean;
    } | null;
    score: {
        priority: 'balanced' | 'reach' | 'discussion' | 'virality';
        weights: {
            views: number;
            forwards: number;
            replies: number;
            reactions: number;
            gifts: number;
        };
    };
    summary: {
        totals: {
            messages: number;
            views: number;
            forwards: number;
            replies: number;
            reactions: number;
            gifts: number;
            mediaPosts: number;
            uniqueAuthors: number;
            avgViewsPerPost: number;
            avgInteractionsPerPost: number;
        };
        funnel: {
            stages: Array<{
                key: 'messages' | 'views' | 'interactions' | 'reactions';
                value: number;
                conversionFromPrevious: number;
                conversionFromStart: number;
            }>;
        };
        audience: {
            activeAuthors: number;
            singleMessageAuthors: number;
            returningAuthors: number;
            topAuthorShare: number;
            top5AuthorsShare: number;
            concentrationIndex: number;
            mostActiveHours: Array<{
                hour: number;
                label: string;
                messages: number;
            }>;
        };
        fraudSignals: {
            riskLevel: 'low' | 'medium' | 'high';
            riskScore: number;
            suspiciousPostsCount: number;
            triggers: Array<{
                key: string;
                score: number;
                value: number;
                threshold: number;
            }>;
            suspiciousPosts: Array<{
                id: number;
                date: number;
                message: string;
                telegramUrl: string | null;
                riskScore: number;
                reasons: string[];
            }>;
        };
        timeline: Array<{
            key: string;
            label: string;
            messages: number;
            views: number;
            forwards: number;
            replies: number;
            reactions: number;
            gifts: number;
            media: number;
            interactions: number;
        }>;
        topMedia: Array<{
            key: string;
            label: string;
            count: number;
            share: number;
        }>;
        topReactions: Array<{
            label: string;
            count: number;
            share: number;
        }>;
        topPosts: Array<{
            id: number;
            date: number;
            message: string;
            telegramUrl: string | null;
            views: number;
            forwards: number;
            replies: number;
            reactions: number;
            mediaLabel: string | null;
            score: number;
        }>;
        opinionLeaders: Array<{
            authorKey: string;
            authorId: number | null;
            authorLabel: string | null;
            messages: number;
            forwards: number;
            replies: number;
            reactions: number;
            gifts: number;
            interactions: number;
            score: number;
        }>;
        opinionLeadersDaily: Array<{
            authorKey: string;
            authorId: number | null;
            authorLabel: string | null;
            dayKey: string;
            dayLabel: string;
            messages: number;
            forwards: number;
            replies: number;
            reactions: number;
            gifts: number;
            interactions: number;
            score: number;
        }>;
        chatUsername: string;
    };
};
