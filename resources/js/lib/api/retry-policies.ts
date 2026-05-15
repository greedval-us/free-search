import type { HttpMethod, RetryPolicy } from './types';

type EndpointRetryRule = {
    path: string;
    methods?: HttpMethod[];
    policy: RetryPolicy;
};

const fallbackEndpointRetryRules: EndpointRetryRule[] = [
    {
        path: '/telegram/parser/status/',
        methods: ['GET'],
        policy: {
            attempts: 4,
            baseDelayMs: 120,
            maxDelayMs: 1200,
        },
    },
    {
        path: '/telegram/parser/start',
        methods: ['POST'],
        policy: {
            attempts: 0,
            retryOnNetworkError: false,
        },
    },
    {
        path: '/telegram/parser/stop/',
        methods: ['POST'],
        policy: {
            attempts: 0,
            retryOnNetworkError: false,
        },
    },
    {
        path: '/telegram/analytics/summary',
        methods: ['GET'],
        policy: {
            attempts: 3,
            baseDelayMs: 180,
            maxDelayMs: 1200,
        },
    },
    {
        path: '/site-intel/seo-audit',
        methods: ['GET'],
        policy: {
            attempts: 1,
            baseDelayMs: 300,
            maxDelayMs: 1200,
        },
    },
    {
        path: '/site-intel/analytics',
        methods: ['GET'],
        policy: {
            attempts: 2,
            baseDelayMs: 250,
            maxDelayMs: 1200,
        },
    },
    {
        path: '/site-intel/site-health',
        methods: ['GET'],
        policy: {
            attempts: 2,
            baseDelayMs: 220,
            maxDelayMs: 1000,
        },
    },
    {
        path: '/site-intel/domain-lite',
        methods: ['GET'],
        policy: {
            attempts: 2,
            baseDelayMs: 200,
            maxDelayMs: 1000,
        },
    },
];

const fromRuntimePolicy = (policy?: {
    attempts?: number;
    base_delay_ms?: number;
    max_delay_ms?: number;
    retry_on_statuses?: number[];
    retry_on_network_error?: boolean;
}): RetryPolicy => ({
    attempts: policy?.attempts,
    baseDelayMs: policy?.base_delay_ms,
    maxDelayMs: policy?.max_delay_ms,
    retryOnStatuses: policy?.retry_on_statuses,
    retryOnNetworkError: policy?.retry_on_network_error,
});

const getRuntimeEndpointRetryRules = (): EndpointRetryRule[] => {
    if (typeof window === 'undefined') {
        return [];
    }

    const rules = window.__OSINT_FRONTEND_CONFIG__?.apiRetry?.endpoint_rules;

    if (!Array.isArray(rules)) {
        return [];
    }

    return rules
        .filter((rule) => typeof rule.path === 'string' && rule.path.length > 0)
        .map((rule) => ({
            path: rule.path,
            methods: Array.isArray(rule.methods) ? (rule.methods as HttpMethod[]) : undefined,
            policy: fromRuntimePolicy(rule.policy),
        }));
};

const getAllEndpointRetryRules = (): EndpointRetryRule[] => {
    const runtimeRules = getRuntimeEndpointRetryRules();

    return runtimeRules.length > 0 ? runtimeRules : fallbackEndpointRetryRules;
};

export const resolveDefaultRetryPolicy = (): RetryPolicy | undefined => {
    if (typeof window === 'undefined') {
        return undefined;
    }

    const runtimeDefault = window.__OSINT_FRONTEND_CONFIG__?.apiRetry?.default;

    if (!runtimeDefault) {
        return undefined;
    }

    return fromRuntimePolicy(runtimeDefault);
};

export const resolveEndpointRetryPolicy = (url: string, method: HttpMethod): RetryPolicy | undefined => {
    for (const rule of getAllEndpointRetryRules()) {
        const methodMatched = !rule.methods || rule.methods.includes(method);
        const pathMatched = url.includes(rule.path);

        if (methodMatched && pathMatched) {
            return rule.policy;
        }
    }

    return undefined;
};
