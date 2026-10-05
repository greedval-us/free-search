import { requestHeaders } from './csrf';
import { ApiError, normalizeErrorPayload, parseApiEnvelope } from './errors';
import { buildQueryString } from './query';
import { getBackoffDelay, sleep, toRetryPolicy } from './retry';
import {
    resolveDefaultRetryPolicy,
    resolveEndpointRetryPolicy,
} from './retry-policies';
import type { ApiResult, RequestOptions } from './types';

const isRetriableStatus = (status: number, retriableStatuses: number[]) =>
    retriableStatuses.includes(status);

const isAbortError = (error: unknown) =>
    error instanceof Error && error.name === 'AbortError';

const requestFailure = (error: unknown, aborted = false) =>
    normalizeErrorPayload({
        message: aborted
            ? 'Request cancelled.'
            : error instanceof Error
              ? error.message
              : 'Network request failed.',
        code: aborted ? 'aborted' : 'network_error',
        cause: error,
    });

export const apiRequest = async <TData = unknown>(
    url: string,
    options: RequestOptions = {}
): Promise<ApiResult<TData>> => {
    const method = options.method ?? 'GET';
    const retryPolicy = toRetryPolicy(
        options.retry ??
            resolveEndpointRetryPolicy(url, method) ??
            (method === 'GET'
                ? resolveDefaultRetryPolicy()
                : { attempts: 0, retryOnNetworkError: false })
    );
    const hashIndex = url.indexOf('#');
    const baseUrl = hashIndex === -1 ? url : url.slice(0, hashIndex);
    const hash = hashIndex === -1 ? '' : url.slice(hashIndex);
    const query = buildQueryString(options.query);
    const requestUrl = `${baseUrl}${query ? `${baseUrl.includes('?') ? '&' : '?'}${query.slice(1)}` : ''}${hash}`;
    const body =
        options.body !== undefined && options.body !== null
            ? JSON.stringify(options.body)
            : undefined;

    let lastError: unknown = null;

    for (let attempt = 0; attempt <= retryPolicy.attempts; attempt += 1) {
        try {
            if (options.signal?.aborted) {
                return requestFailure(options.signal.reason, true);
            }

            const response = await fetch(requestUrl, {
                method,
                headers: requestHeaders(
                    requestUrl,
                    method,
                    options.headers,
                    body !== undefined
                ),
                body,
                signal: options.signal,
                credentials: options.credentials ?? 'same-origin',
            });

            const rawPayload = await response.json().catch((error) => {
                if (isAbortError(error) || options.signal?.aborted) {
                    throw error;
                }

                return null;
            });

            if (options.signal?.aborted) {
                return requestFailure(options.signal.reason, true);
            }

            const envelope = parseApiEnvelope<TData>(rawPayload);

            if (response.ok && envelope?.ok) {
                return {
                    ok: true,
                    data:
                        envelope.data !== undefined
                            ? envelope.data
                            : (rawPayload as TData),
                    message: envelope.message,
                    meta: envelope.meta,
                };
            }

            const fallbackMessage = `HTTP ${response.status}`;
            const errorPayload = normalizeErrorPayload({
                message: envelope?.message ?? fallbackMessage,
                status: response.status,
                code: envelope?.code,
                errors: envelope?.errors,
                meta: envelope?.meta,
            });

            if (
                attempt >= retryPolicy.attempts ||
                !isRetriableStatus(response.status, retryPolicy.retryOnStatuses)
            ) {
                return errorPayload;
            }
        } catch (error) {
            if (isAbortError(error) || options.signal?.aborted) {
                return requestFailure(error, true);
            }

            lastError = error;

            if (
                attempt >= retryPolicy.attempts ||
                !retryPolicy.retryOnNetworkError
            ) {
                return requestFailure(error);
            }
        }

        try {
            await sleep(
                getBackoffDelay(
                    attempt,
                    retryPolicy.baseDelayMs,
                    retryPolicy.maxDelayMs
                ),
                options.signal
            );
        } catch (error) {
            return requestFailure(error, true);
        }
    }

    return requestFailure(lastError);
};

export const apiRequestOrThrow = async <TData = unknown>(
    url: string,
    options: RequestOptions = {}
): Promise<TData> => {
    const result = await apiRequest<TData>(url, options);

    if (result.ok) {
        return result.data;
    }

    throw new ApiError(result);
};
