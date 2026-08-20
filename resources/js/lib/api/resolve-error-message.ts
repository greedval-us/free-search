import { ApiError } from './errors';

const TECHNICAL_ERROR_PATTERNS = [
    /^Failed to fetch$/i,
    /^Load failed$/i,
    /^NetworkError/i,
    /^Network request failed/i,
    /^HTTP \d{3}$/i,
    /^(Request|Parser|Analytics).*(failed|error)/i,
    /^(Failed|Unable|Could not) to /i,
    /\b(exception|stack trace|sqlstate)\b/i,
    /(?:[A-Z]:\\|\/var\/www\/|\/app\/Http\/)/i,
    /fetch/i,
    /network/i,
    /cors/i,
];

const isTechnicalMessage = (message: string) =>
    TECHNICAL_ERROR_PATTERNS.some((pattern) => pattern.test(message.trim()));

export const resolveApiErrorMessage = (
    message: string | null | undefined,
    fallback: string
): string => {
    const normalized = message?.trim();

    return normalized && !isTechnicalMessage(normalized)
        ? normalized
        : fallback;
};

export const resolveClientErrorMessage = (
    error: unknown,
    fallback: string
): string => {
    if (error instanceof ApiError) {
        return resolveApiErrorMessage(error.message, fallback);
    }

    if (
        error instanceof Error &&
        error.message.trim() !== '' &&
        !isTechnicalMessage(error.message)
    ) {
        return resolveApiErrorMessage(error.message, fallback);
    }

    return fallback;
};
