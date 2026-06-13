import { ApiError } from './errors';

const TECHNICAL_ERROR_PATTERNS = [
    /^Failed to fetch$/i,
    /^Load failed$/i,
    /^NetworkError/i,
    /^Network request failed/i,
    /^HTTP \d{3}$/i,
    /fetch/i,
    /network/i,
    /cors/i,
];

const isTechnicalMessage = (message: string) =>
    TECHNICAL_ERROR_PATTERNS.some((pattern) => pattern.test(message.trim()));

export const resolveClientErrorMessage = (
    error: unknown,
    fallback: string
): string => {
    if (error instanceof ApiError && error.message.trim() !== '') {
        return error.message;
    }

    if (
        error instanceof Error &&
        error.message.trim() !== '' &&
        !isTechnicalMessage(error.message)
    ) {
        return error.message;
    }

    return fallback;
};
