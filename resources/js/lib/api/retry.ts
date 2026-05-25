import type { RetryPolicy } from './types';

const DEFAULT_RETRY_ON_STATUSES = [408, 425, 429, 500, 502, 503, 504];

export const defaultRetryPolicy: Required<RetryPolicy> = {
    attempts: 2,
    baseDelayMs: 250,
    maxDelayMs: 1500,
    retryOnStatuses: DEFAULT_RETRY_ON_STATUSES,
    retryOnNetworkError: true,
};

export const toRetryPolicy = (policy?: RetryPolicy): Required<RetryPolicy> => ({
    ...defaultRetryPolicy,
    ...policy,
    retryOnStatuses:
        policy?.retryOnStatuses ?? defaultRetryPolicy.retryOnStatuses,
});

export const sleep = async (delayMs: number) =>
    new Promise<void>((resolve) => {
        window.setTimeout(resolve, delayMs);
    });

export const getBackoffDelay = (
    attemptIndex: number,
    baseDelayMs: number,
    maxDelayMs: number
) => {
    const exponential = baseDelayMs * Math.pow(2, attemptIndex);
    const jitter = Math.round(Math.random() * baseDelayMs);

    return Math.min(exponential + jitter, maxDelayMs);
};
