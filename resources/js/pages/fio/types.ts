export type FioMatch = {
    title: string;
    snippet: string;
    url: string;
    domain: string | null;
    source: string;
    sourceReliability: number;
    region: string;
    age: number | null;
    ageBucket: string;
    qualifier: string | null;
    qualifierMatched: boolean;
    confidence: number;
};

export type FioCluster = {
    key: string;
    count: number;
    percent: number;
};

export type FioLookupResult = {
    target: {
        fullName: string;
        qualifier: string | null;
        qualifierTerms?: string[];
    };
    checkedAt: string;
    summary: {
        matches: number;
        domains: number;
        topRegion: string;
        topAgeBucket: string;
        medianAge: number | null;
        averageConfidence: number;
        qualifierMatches: number;
    };
    clusters: {
        regions: FioCluster[];
        ages: FioCluster[];
    };
    matches: FioMatch[];
    source: {
        provider: string;
        mode: string;
        providers?: string[];
        stats?: Array<{
            source: string;
            reliability: number;
            matches: number;
            qualifierMatches: number;
            averageConfidence: number;
        }>;
    };
};
