import type { ApiEnvelope, ApiFailure } from './types';

const DEFAULT_ERROR_MESSAGE = 'Request failed. Please try again.';

export class ApiError extends Error {
    status?: number;
    code?: string;
    errors?: Record<string, string[]>;
    meta?: Record<string, unknown>;
    cause?: unknown;

    constructor(payload: ApiFailure) {
        super(payload.message);
        this.name = 'ApiError';
        this.status = payload.status;
        this.code = payload.code;
        this.errors = payload.errors;
        this.meta = payload.meta;
        this.cause = payload.cause;
    }
}

const isObject = (value: unknown): value is Record<string, unknown> =>
    typeof value === 'object' && value !== null;

export const parseApiEnvelope = <TData>(
    value: unknown
): ApiEnvelope<TData> | null => {
    if (!isObject(value) || typeof value.ok !== 'boolean') {
        return null;
    }

    return value as ApiEnvelope<TData>;
};

export const normalizeErrorPayload = (input: {
    message?: string;
    status?: number;
    code?: string;
    errors?: Record<string, string[]>;
    meta?: Record<string, unknown>;
    cause?: unknown;
}): ApiFailure => ({
    ok: false,
    message: input.message?.trim() || DEFAULT_ERROR_MESSAGE,
    status: input.status,
    code: input.code,
    errors: input.errors,
    meta: input.meta,
    cause: input.cause,
});
