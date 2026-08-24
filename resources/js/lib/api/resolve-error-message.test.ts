import { describe, expect, it } from 'vitest';
import { ApiError } from './errors';
import {
    resolveApiErrorMessage,
    resolveClientErrorMessage,
} from './resolve-error-message';

describe('API error message resolver', () => {
    const fallback = 'Не удалось загрузить данные. Попробуйте ещё раз.';

    it('keeps a useful public message', () => {
        expect(resolveApiErrorMessage('Account not found.', fallback)).toBe(
            'Account not found.'
        );
    });

    it.each([
        'Request failed.',
        'HTTP 500',
        'SQLSTATE[HY000] connection failed',
        'RuntimeException in /var/www/app/Http/Controller.php',
    ])('replaces technical message: %s', (message) => {
        expect(resolveApiErrorMessage(message, fallback)).toBe(fallback);
    });

    it('replaces a technical ApiError with localized fallback', () => {
        const error = new ApiError({
            ok: false,
            message: 'Failed to fetch',
        });

        expect(resolveClientErrorMessage(error, fallback)).toBe(fallback);
    });
});
