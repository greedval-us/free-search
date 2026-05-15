import { ApiError, normalizeErrorPayload, parseApiEnvelope } from './errors';
import { buildQueryString } from './query';
import { resolveDefaultRetryPolicy, resolveEndpointRetryPolicy } from './retry-policies';
import { getBackoffDelay, sleep, toRetryPolicy } from './retry';
import type { ApiResult, RequestOptions } from './types';

const DEFAULT_HEADERS: HeadersInit = {
    Accept: 'application/json',
};

const withJsonBody = (headers: HeadersInit | undefined, body: unknown): HeadersInit => {
    if (body === undefined || body === null) {
        return headers ?? DEFAULT_HEADERS;
    }

    return {
        'Content-Type': 'application/json',
        ...DEFAULT_HEADERS,
        ...(headers ?? {}),
    };
};

const isRetriableStatus = (status: number, retriableStatuses: number[]) => retriableStatuses.includes(status);

export const apiRequest = async <TData = unknown>(
    url: string,
    options: RequestOptions = {},
): Promise<ApiResult<TData>> => {
    const method = options.method ?? 'GET';
    const retryPolicy = toRetryPolicy(
        options.retry ?? resolveEndpointRetryPolicy(url, method) ?? resolveDefaultRetryPolicy(),
    );
    const requestUrl = `${url}${buildQueryString(options.query)}`;
    const headers = withJsonBody(options.headers, options.body);
    const body = options.body !== undefined && options.body !== null ? JSON.stringify(options.body) : undefined;

    let lastError: unknown = null;

    for (let attempt = 0; attempt <= retryPolicy.attempts; attempt += 1) {
        try {
            const response = await fetch(requestUrl, {
                method,
                headers,
                body,
                signal: options.signal,
                credentials: options.credentials ?? 'same-origin',
            });

            const rawPayload = await response.json().catch(() => null);
            const envelope = parseApiEnvelope<TData>(rawPayload);

            if (response.ok && envelope?.ok) {
                return {
                    ok: true,
                    data: (envelope.data !== undefined ? envelope.data : (rawPayload as TData)),
                    message: envelope.message,
                    meta: envelope.meta,
                };
            }

            const fallbackMessage = `HTTP ${response.status}`;
            const errorPayload = normalizeErrorPayload({
                message: envelope?.message ?? fallbackMessage,
                status: response.status,
                errors: envelope?.errors,
                meta: envelope?.meta,
            });

            if (attempt < retryPolicy.attempts && isRetriableStatus(response.status, retryPolicy.retryOnStatuses)) {
                const delay = getBackoffDelay(attempt, retryPolicy.baseDelayMs, retryPolicy.maxDelayMs);
                await sleep(delay);
                continue;
            }

            return errorPayload;
        } catch (error) {
            lastError = error;

            if (attempt < retryPolicy.attempts && retryPolicy.retryOnNetworkError) {
                const delay = getBackoffDelay(attempt, retryPolicy.baseDelayMs, retryPolicy.maxDelayMs);
                await sleep(delay);
                continue;
            }
        }
    }

    return normalizeErrorPayload({
        message: lastError instanceof Error ? lastError.message : 'Network request failed.',
        cause: lastError,
    });
};

export const apiRequestOrThrow = async <TData = unknown>(
    url: string,
    options: RequestOptions = {},
): Promise<TData> => {
    const result = await apiRequest<TData>(url, options);

    if (result.ok) {
        return result.data;
    }

    throw new ApiError(result);
};
