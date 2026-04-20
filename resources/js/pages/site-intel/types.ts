export type SiteIntelTabValue = 'siteHealth' | 'domainLite';

export type SiteHealthResult = {
    target: {
        input: string;
        host: string;
    };
    checkedAt: string;
    dns: {
        a: string[];
        aaaa: string[];
    };
    http: {
        chain: Array<{
            url: string;
            status: number;
            location: string | null;
            responseTimeMs: number;
            error: string | null;
        }>;
        finalUrl: string;
        finalStatus: number;
        totalRedirects: number;
    };
    headers: Record<string, { present: boolean; value: string | null }>;
    ssl: {
        available: boolean;
        subject: string | null;
        issuer: string | null;
        validFrom: string | null;
        validTo: string | null;
        daysRemaining: number | null;
    };
    score: {
        value: number;
        level: 'low' | 'medium' | 'high';
        signals: string[];
    };
};

export type DomainLiteResult = {
    domain: string;
    checkedAt: string;
    dns: {
        a: string[];
        aaaa: string[];
        ns: string[];
        mx: Array<{ host: string; priority: number }>;
        txt: string[];
        caa: string[];
        emailSecurity: {
            hasSpf: boolean;
            hasDmarc: boolean;
            dmarc: string[];
        };
    };
    whois: {
        server: string | null;
        available: boolean;
        createdAt: string | null;
        updatedAt: string | null;
        expiresAt: string | null;
        registrar: string | null;
        country: string | null;
        sample: string | null;
    };
    risk: {
        score: number;
        level: 'low' | 'medium' | 'high';
        signals: string[];
    };
};

