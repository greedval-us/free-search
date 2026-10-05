import { resolveSameOriginUrl } from '@/lib/sameOriginUrl';
import type { HttpMethod } from './types';

export const requestHeaders = (
    url: string,
    method: HttpMethod,
    headers: HeadersInit | undefined,
    hasBody: boolean
): Headers => {
    const result = new Headers(headers);

    if (!result.has('Accept')) {
        result.set('Accept', 'application/json');
    }

    if (hasBody && !result.has('Content-Type')) {
        result.set('Content-Type', 'application/json');
    }

    if (
        method === 'GET' ||
        !resolveSameOriginUrl(url) ||
        typeof document === 'undefined' ||
        result.get('X-CSRF-TOKEN')?.trim() ||
        result.get('X-XSRF-TOKEN')?.trim()
    ) {
        return result;
    }

    const cookie = document.cookie
        ?.split(';')
        .map((part) => part.trim())
        .find((part) => part.startsWith('XSRF-TOKEN='));

    if (cookie) {
        try {
            const token = decodeURIComponent(
                cookie.slice('XSRF-TOKEN='.length)
            );

            if (token) {
                result.set('X-XSRF-TOKEN', token);

                return result;
            }
        } catch {
            // Fall back to the page token when the cookie cannot be decoded.
        }
    }

    const token = document.querySelector<HTMLMetaElement>(
        'meta[name="csrf-token"]'
    )?.content;

    if (token) {
        result.set('X-CSRF-TOKEN', token);
    }

    return result;
};
