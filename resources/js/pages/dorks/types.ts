export type DorksTabValue = 'search' | 'analytics';

export type DorkGoal = {
    key: string;
    label: string;
};

export type DorkResultItem = {
    source: string;
    goal: string;
    dork: string;
    title: string;
    snippet: string;
    url: string;
    domain: string | null;
};

export type DorksDistributionItem = {
    key: string;
    label: string;
    count: number;
};

export type DorksGraphNode = {
    id: string;
    type: string;
    label: string;
    confidence?: number;
};

export type DorksGraphEdge = {
    source: string;
    target: string;
    kind: string;
    confidence?: number;
};

export type DorksSearchPayload = {
    target: string;
    goal: string;
    checkedAt: string;
    summary: {
        total: number;
        uniqueDomains: number;
        sources: number;
        goals: number;
    };
    items: DorkResultItem[];
    analytics: {
        goalDistribution: DorksDistributionItem[];
        sourceDistribution: DorksDistributionItem[];
        topDomains: DorksDistributionItem[];
        graph: {
            nodes: DorksGraphNode[];
            edges: DorksGraphEdge[];
        };
    };
    diagnostics: {
        attemptedSources: Array<{
            source: string;
            goal: string;
            ok: boolean;
            count: number;
            durationMs: number;
        }>;
        sourceErrors: Array<{
            source: string;
            goal: string;
            message: string;
            durationMs: number;
        }>;
    };
    availableGoals: DorkGoal[];
};

export type DorksApiResponse = {
    ok: boolean;
    message?: string;
    errors?: Record<string, string[]>;
    goals?: DorkGoal[];
} & Partial<DorksSearchPayload>;

