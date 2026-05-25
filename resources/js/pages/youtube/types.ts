export type YouTubeTabValue = 'search' | 'analytics' | 'parser';

export type YouTubeVideo = {
    id: string;
    type: 'video' | 'channel' | 'playlist';
    title: string;
    description: string;
    channelId: string;
    channelTitle: string;
    publishedAt: string;
    thumbnail: string;
    duration: string;
    durationSeconds: number;
    durationLabel: string;
    categoryId: string;
    tags: string[];
    defaultLanguage: string;
    defaultAudioLanguage: string;
    definition: string;
    caption: string;
    dimension: string;
    projection: string;
    licensedContent: boolean;
    privacyStatus: string;
    embeddable: boolean;
    license: string;
    madeForKids: boolean;
    views: number;
    likes: number;
    comments: number;
    favorites: number;
    engagementRate: number;
    url: string;
};

export type YouTubeChannel = {
    id: string;
    title: string;
    description: string;
    customUrl: string;
    publishedAt: string;
    country: string;
    thumbnail: string;
    uploadsPlaylistId: string;
    viewCount: number;
    subscriberCount: number;
    hiddenSubscriberCount: boolean;
    videoCount: number;
    privacyStatus: string;
    madeForKids: boolean;
    keywords: string;
    topicCategories: string[];
    url: string;
};

export type YouTubeSearchPayload = {
    items: YouTubeVideo[];
    pagination: {
        nextPageToken: string | null;
        total: number;
        perPage: number;
    };
};

export type YouTubeAnalyticsPayload = {
    mode: 'video' | 'channel';
    video: YouTubeVideo | null;
    totals: {
        videos: number;
        views: number;
        likes: number;
        comments: number;
        avgViews: number;
        avgLikes: number;
        avgComments: number;
        medianViews: number;
        engagementRate: number;
        likeRate: number;
        commentRate: number;
    };
    channel: YouTubeChannel | null;
    distribution: {
        timeline: Array<{
            key: string;
            videos: number;
            views: number;
            likes: number;
            comments: number;
        }>;
        duration: Record<'short' | 'medium' | 'long', number>;
        definition: Record<string, number>;
        captions: {
            with: number;
            without: number;
        };
    };
    leaders: {
        byViews: YouTubeVideo[];
        byLikes: YouTubeVideo[];
        byComments: YouTubeVideo[];
        byEngagement: YouTubeVideo[];
    };
    insights: Array<{
        key: string;
        label: string;
        value: string;
    }>;
    topTags: Array<{
        tag: string;
        count: number;
    }>;
    topVideos: YouTubeVideo[];
};

export type YouTubeCommentItem = {
    id: string;
    threadId: string;
    author: string;
    authorChannelUrl: string;
    text: string;
    likeCount: number;
    publishedAt: string;
    replyCount: number;
    replies: Array<{
        id: string;
        author: string;
        text: string;
        likeCount: number;
        publishedAt: string;
    }>;
};

export type YouTubeCommentsPayload = {
    items: YouTubeCommentItem[];
    pagination: {
        nextPageToken: string | null;
        total: number;
        perPage: number;
    };
};
