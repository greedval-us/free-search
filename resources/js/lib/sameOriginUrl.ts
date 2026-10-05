export const resolveSameOriginUrl = (value: unknown): string | null => {
    if (
        typeof value !== 'string' ||
        value.trim() === '' ||
        /[\u0000-\u001f\u007f]/.test(value) ||
        typeof window === 'undefined'
    ) {
        return null;
    }

    try {
        const url = new URL(value, window.location.origin);

        if (
            !['http:', 'https:'].includes(url.protocol) ||
            url.origin !== window.location.origin ||
            url.username !== '' ||
            url.password !== ''
        ) {
            return null;
        }

        return url.href;
    } catch {
        return null;
    }
};
