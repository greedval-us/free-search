export type SiteIntelTabValue = 'siteHealth' | 'domainLite' | 'analytics' | 'seoAudit';

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
        profile: string;
        signals: string[];
    };
    profile: {
        key: string;
        label: string;
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

export type SiteIntelAnalyticsResult = {
    target: {
        url: string;
        domain: string;
    };
    checkedAt: string;
    overview: {
        score: {
            value: number;
            level: 'low' | 'medium' | 'high';
        };
        healthScore: number;
        domainRiskScore: number;
        headersCoverage: {
            present: number;
            total: number;
            percent: number;
        };
        dnsStats: {
            a: number;
            aaaa: number;
            ns: number;
            mx: number;
        };
        daysToDomainExpiry: number | null;
        redirects: number;
        recommendations: string[];
        signals: {
            risks: string[];
            strengths: string[];
        };
    };
    siteHealth: SiteHealthResult;
    domainLite: DomainLiteResult;
};

export type SiteIntelSeoAuditResult = {
    target: {
        input: string;
        finalUrl: string;
        host: string;
    };
    checkedAt: string;
    status: {
        httpCode: number;
        responseTimeMs: number;
    };
    meta: {
        title: string;
        titleLength: number;
        description: string;
        descriptionLength: number;
        canonical: string;
        robots: string;
    };
    headings: {
        h1: number;
        h2: number;
        h3: number;
    };
    links: {
        internal: number;
        external: number;
        nofollow: number;
    };
    indexability: {
        metaRobots: string;
        xRobotsTag: string;
        indexable: boolean;
        reason: string;
    };
    robots: {
        url: string;
        available: boolean;
        status: number;
        sitemapFromRobots: string;
        content: string;
        rules: {
            userAgents: string[];
            groups: Array<{
                userAgents: string[];
                allow: string[];
                disallow: string[];
                crawlDelay: number | null;
            }>;
            hasWildcardGroup: boolean;
            hasCrawlDelay: boolean;
        };
    };
    sitemap: {
        url: string;
        available: boolean;
        status: number;
        entries: number;
    };
    performance: {
        ttfbMsApprox: number;
        pageSizeKb: number;
        resourceCount: number;
        renderBlocking: {
            css: number;
            scripts: number;
            total: number;
        };
    };
    security: {
        https: boolean;
        mixedContent: boolean;
        hasCsp: boolean;
        hasHsts: boolean;
    };
    mobileFriendly: {
        hasViewportTag: boolean;
        viewportContent: string;
        hasDeviceWidth: boolean;
        isResponsive: boolean;
    };
    pagination: {
        hasRelPrev: boolean;
        hasRelNext: boolean;
        isPaginated: boolean;
    };
    soft404: {
        detected: boolean;
        markers: string[];
    };
    quality: {
        anchors: {
            total: number;
            empty: number;
            generic: number;
        };
        htmlValidation: {
            issues: string[];
            issueCount: number;
        };
        accessibility: {
            imagesTotal: number;
            imagesWithoutAlt: number;
            inputsTotal: number;
            labelsTotal: number;
            headingOrderBroken: boolean;
        };
        content: {
            wordCount: number;
            textLength: number;
            textToHtmlRatio: number;
            thinContent: boolean;
            lowTextRatio: boolean;
        };
        linkGraph: {
            internalOutlinks: number;
            externalOutlinks: number;
            orphanRisk: boolean;
        };
    };
    international: {
        clusters: Array<{ langs: string; count: number }>;
        missingXDefault: string[];
        missingReciprocal: Array<{ source: string; target: string; lang: string }>;
        pagesWithHreflang: number;
    };
    crawlBudget: {
        source: string;
        periodDays: number;
        host: string;
        botHits: number;
        statusBuckets: {
            '2xx': number;
            '3xx': number;
            '4xx': number;
            '5xx': number;
        };
        topBotAgents: Array<{ agent: string; count: number }>;
    };
    crawl: {
        limit: number;
        scanned: number;
        pages: Array<{
            url: string;
            status: number;
            title: string;
            description: string;
            titleLength: number;
            descriptionLength: number;
            h1Count: number;
            canonical: string;
            indexable: boolean;
            hreflang: {
                count: number;
                tags: Array<{ lang: string; href: string }>;
            };
        }>;
        duplicates: {
            titles: Array<{ value: string; count: number; urls: string[] }>;
            descriptions: Array<{ value: string; count: number; urls: string[] }>;
            h1: Array<{ value: string; count: number; urls: string[] }>;
        };
        canonicalAudit: {
            missing: string[];
            crossDomain: Array<{ url: string; canonical: string }>;
            invalid: Array<{ url: string; canonical: string }>;
            selfReferencing: number;
        };
        hreflangAudit: {
            pagesWithHreflang: number;
            pagesWithoutSelfReference: string[];
            duplicateLangTags: Array<{ url: string; lang: string; count: number }>;
        };
    };
    sitemapAudit: {
        source: string;
        sampled: number;
        non200: Array<{ url: string; status: number }>;
        checked: Array<{ url: string; status: number }>;
    };
    score: {
        value: number;
        level: 'low' | 'medium' | 'high';
        profile: string;
        signals: string[];
    };
    recommendations: Array<{
        priority: 'critical' | 'medium' | 'low';
        key: string;
    }>;
};
