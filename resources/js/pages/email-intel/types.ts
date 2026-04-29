export type EmailIntelSignal = {
    type: string;
    level: 'positive' | 'info' | 'low' | 'medium' | 'high';
    message: string;
};

export type EmailIntelGraphNode = {
    id: string;
    type: string;
    label: string;
    level?: string;
};

export type EmailIntelGraphEdge = {
    source: string;
    target: string;
    kind: string;
};

export type EmailIntelResult = {
    checkedAt: string;
    target: {
        email: string;
        localPart: string;
        domain: string;
        normalized: string;
        sha256: string;
    };
    dns: {
        mx: Array<{ host: string; priority: number }>;
        a: string[];
        aaaa: string[];
        txt: string[];
        dmarc: string[];
        emailSecurity: {
            hasSpf: boolean;
            hasDmarc: boolean;
        };
    };
    profile: {
        isFreeProvider: boolean;
        isDisposable: boolean;
        isRoleAccount: boolean;
        gravatarHash: string;
        gravatarUrl: string;
    };
    analytics: {
        provider: {
            name: string;
            type: string;
            confidence: number;
            mxHosts: string[];
        };
        spf: {
            present: boolean;
            record: string | null;
            includes: string[];
            ip4: string[];
            ip6: string[];
            mechanisms: string[];
            all: string | null;
            strictness: string;
        };
        dmarc: {
            present: boolean;
            record: string | null;
            policy: string | null;
            subdomainPolicy: string | null;
            pct: number | null;
            rua: string[];
            ruf: string[];
            adkim: string | null;
            aspf: string | null;
            strength: string;
        };
        localPart: {
            isRoleAccount: boolean;
            hasPlusAddressing: boolean;
            hasYear: boolean;
            hasSeparators: boolean;
            tokens: string[];
            length: number;
            entropy: number;
            looksRandom: boolean;
        };
        scores: {
            mailSecurity: number;
            domainHealth: number;
            identityConfidence: number;
            overall: number;
        };
        searchLinks: Array<{
            label: string;
            type: string;
            url: string;
        }>;
        similarDomains: Array<{
            domain: string;
            reason: string;
        }>;
        webSnapshot: {
            url: string;
            available: boolean;
            status: number | null;
            durationMs: number;
            securityHeaders: Record<string, boolean>;
        };
        recommendations: Array<{
            key: string;
            priority: string;
            impact: number;
        }>;
        graph: {
            nodes: EmailIntelGraphNode[];
            edges: EmailIntelGraphEdge[];
        };
        pivots: {
            username: string;
            siteIntel: string;
            gravatar: string;
        };
    };
    riskScore: number;
    riskLevel: 'clean' | 'low' | 'medium' | 'high';
    signals: EmailIntelSignal[];
};
