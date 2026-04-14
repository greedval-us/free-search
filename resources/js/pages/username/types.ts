export type UsernameTabValue = 'search' | 'analytics';

export type UsernameSearchStatus = 'found' | 'not_found' | 'unknown';

export type UsernameSearchItem = {
    key: string;
    name: string;
    profileUrl: string;
    profileDomain: string;
    category: string;
    regionGroup: string;
    primaryUsersRegion: string;
    status: UsernameSearchStatus;
    httpStatus: number | null;
    confidence: number;
    error: string | null;
};

export type UsernameAnalytics = {
    confidence: {
        average: number;
        high: number;
        medium: number;
        low: number;
    };
    similarity: {
        variants: Array<{
            username: string;
            reason: string;
            foundInPrioritySources: number | null;
            checkedPrioritySources: number | null;
        }>;
    };
    graph: {
        nodes: UsernameGraphNode[];
        edges: UsernameGraphEdge[];
    };
};

export type UsernameGraphNode = {
    id: string;
    type: string;
    label: string;
    status?: string;
    confidence?: number;
};

export type UsernameGraphEdge = {
    source: string;
    target: string;
    kind: string;
    status?: string;
    confidence?: number;
};

export type UsernameSearchResponse = {
    ok: boolean;
    username: string;
    checkedAt: string;
    summary: {
        checked: number;
        found: number;
        notFound: number;
        unknown: number;
    };
    items: UsernameSearchItem[];
    analytics: UsernameAnalytics;
};
