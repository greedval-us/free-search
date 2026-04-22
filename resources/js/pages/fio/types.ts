export type FioMatch = {
    title: string;
    snippet: string;
    url: string;
    domain: string | null;
    source: string;
    region: string;
    age: number | null;
    ageBucket: string;
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
    };
    checkedAt: string;
    summary: {
        matches: number;
        domains: number;
        topRegion: string;
        topAgeBucket: string;
        medianAge: number | null;
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
    };
};
