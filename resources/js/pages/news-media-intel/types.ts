export type NewsCategory = 'news' | 'general';
export type NewsFilters = {
    categories: NewsCategory[];
    language: string;
    timeRange: '' | 'day' | 'week' | 'month' | 'year';
    safeSearch: number;
    engines: string[];
    maxPages: number;
};
export type NewsOptions = {
    categories: NewsCategory[];
    languages: string[];
    engines: Record<NewsCategory, string[]>;
    maxPages: number;
    defaults: Omit<NewsFilters, 'categories' | 'engines'>;
};
export type NewsMention = {
    source: 'searxng';
    title: string;
    snippet: string;
    link: string;
    publishedAt: string | null;
    engines?: string[];
    category?: string;
    categories?: string[];
    position?: number | null;
    publisher?: string;
};
export type NewsSentiment = {
    positive: number;
    neutral: number;
    negative: number;
};
export type SearchCoverage = {
    pagesRequested?: number;
    pagesLoaded?: number;
    truncated: boolean;
    stopReason: string | null;
    unresponsiveEngines?: Array<{ name: string; reason: string }>;
    engines?: string[];
    estimatedTotal?: number | null;
    status?: 'ok' | 'unavailable';
};
export type NewsInfobox = {
    title: string;
    content: string;
    urls: Array<string | { title?: string; url: string }>;
};
export type NewsResult = {
    query: string;
    mentions: NewsMention[];
    topics: Array<{ topic: string; count: number }>;
    timeline: Array<{ date: string; mentions: number }>;
    sentiment: NewsSentiment;
    coverage?: SearchCoverage;
    suggestions?: string[];
    corrections?: string[];
    answers?: string[];
    infoboxes?: NewsInfobox[];
};
export type MarketingForm = {
    query: string;
    brand: string;
    competitors: string;
    domain: string;
};
export type NewsAnalytics = {
    query: string;
    checkedAt: string;
    options: NewsFilters;
    reportId: string;
    reportExpiresAt: string;
    summary: {
        mentions: number;
        newsMentions: number;
        generalMentions: number;
        publishers: number;
        knownDates: number;
        undatedMentions: number;
        futureDates: number;
        freshMentions: number;
        freshnessPercent: number | null;
        entityMatches: number;
        sentiment: NewsSentiment;
    };
    mentions: NewsMention[];
    coverage: {
        general: SearchCoverage;
        news: SearchCoverage;
        partial: boolean;
    };
    suggestions: string[];
    corrections: string[];
    answers: string[];
    infoboxes: NewsInfobox[];
    publishers: Array<{
        host: string;
        count: number;
        share: number;
        types: string[];
        engines: string[];
        evidenceUrls: string[];
    }>;
    brandComparison: {
        denominator: 'entity_matches';
        totalMatches: number;
        coMentionDocuments: number;
        entities: Array<{
            name: string;
            kind: 'brand' | 'competitor';
            mentions: number;
            share: number;
            sentiment: NewsSentiment;
            evidenceUrls: string[];
        }>;
    };
    topics: Array<{
        topic: string;
        count: number;
        share: number;
        evidenceUrls: string[];
    }>;
    questions: Array<{
        question: string;
        count: number;
        sources: string[];
        evidenceUrls: string[];
    }>;
    contentOpportunities: Array<{
        code:
            | 'topic_coverage'
            | 'answer_question'
            | 'brand_presence'
            | 'publisher_outreach';
        params: Record<string, string | number>;
        evidenceUrls: string[];
    }>;
    visibility: {
        domain: string | null;
        generalResults: number;
        domainMatches: number;
        share: number;
        bestObservedPosition: number | null;
        pages: Array<{ url: string; position: number | null; title: string }>;
        method: 'observed_general_results';
    };
    timeline: Array<{ date: string; mentions: number }>;
};
