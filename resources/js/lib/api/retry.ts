import type { RetryPolicy } from './types';

const DEFAULT_RETRY_ON_STATUSES = [408, 425, 429, 500, 502, 503, 504];

export const defaultRetryPolicy: Required<RetryPolicy> = {
    attempts: 2,
    baseDelayMs: 250,
    maxDelayMs: 1500,
    retryOnStatuses: DEFAULT_RETRY_ON_STATUSES,
    retryOnNetworkError: true,
};

const nonNegativeNumber = (value: number | undefined, fallback: number) =>
    value !== undefined && Number.isFinite(value) && value >= 0
        ? value
        : fallback;

export const toRetryPolicy = (policy?: RetryPolicy): Required<RetryPolicy> => ({
    attempts: Math.floor(
        nonNegativeNumber(policy?.attempts, defaultRetryPolicy.attempts)
    ),
    baseDelayMs: nonNegativeNumber(
        policy?.baseDelayMs,
        defaultRetryPolicy.baseDelayMs
    ),
    maxDelayMs: nonNegativeNumber(
        policy?.maxDelayMs,
        defaultRetryPolicy.maxDelayMs
    ),
    retryOnStatuses:
        policy?.retryOnStatuses ?? defaultRetryPolicy.retryOnStatuses,
    retryOnNetworkError:
        policy?.retryOnNetworkError ?? defaultRetryPolicy.retryOnNetworkError,
});

export const sleep = async (delayMs: number, signal?: AbortSignal) =>
    new Promise<void>((resolve, reject) => {
        const abortError = () =>
            signal?.reason ??
            new DOMException('The request was aborted.', 'AbortError');

        if (signal?.aborted) {
            reject(abortError());

            return;
        }

        const onAbort = () => {
            clearTimeout(timer);
            reject(abortError());
        };
        const timer = setTimeout(() => {
            signal?.removeEventListener('abort', onAbort);
            resolve();
        }, delayMs);
        signal?.addEventListener('abort', onAbort, { once: true });
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
