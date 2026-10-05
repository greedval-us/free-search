export type NewsResult = {
    query: string;
    mentions: Array<{
        source: 'searxng';
        title: string;
        snippet: string;
        link: string;
        publishedAt: string;
    }>;
    topics: Array<{ topic: string; count: number }>;
    timeline: Array<{ date: string; mentions: number }>;
    sentiment: { positive: number; neutral: number; negative: number };
};
