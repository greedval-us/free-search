export type ApiMeta = Record<string, unknown>;

export type ApiFieldErrors = Record<string, string[]>;

export type ApiEnvelope<TData = unknown> = {
    ok: boolean;
    data?: TData;
    message?: string;
    errors?: ApiFieldErrors;
    meta?: ApiMeta;
};

export type ApiSuccess<TData> = {
    ok: true;
    data: TData;
    message?: string;
    meta?: ApiMeta;
};

export type ApiFailure = {
    ok: false;
    message: string;
    status?: number;
    code?: string;
    errors?: ApiFieldErrors;
    cause?: unknown;
    meta?: ApiMeta;
};

export type ApiResult<TData> = ApiSuccess<TData> | ApiFailure;

export type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

export type RetryPolicy = {
    attempts?: number;
    baseDelayMs?: number;
    maxDelayMs?: number;
    retryOnStatuses?: number[];
    retryOnNetworkError?: boolean;
};

export type RequestOptions = {
    method?: HttpMethod;
    query?: Record<string, string | number | boolean | null | undefined>;
    body?: unknown;
    headers?: HeadersInit;
    signal?: AbortSignal;
    retry?: RetryPolicy;
    credentials?: RequestCredentials;
};
