export const resolveArticleUrl = (value: unknown): string | null => {
    if (
        typeof value !== 'string' ||
        value.trim() === '' ||
        /\p{Cc}/u.test(value)
    ) {
        return null;
    }

    try {
        const url = new URL(value);

        if (
            !['http:', 'https:'].includes(url.protocol) ||
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
