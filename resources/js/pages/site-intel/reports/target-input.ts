const DOMAIN = /^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i;
const LOCAL_DOMAIN =
    /\.(?:local|localhost|internal|intranet|lan|home|test|invalid|example)$/i;

export const normalizeReportTarget = (input: string): string | null => {
    const value = input.trim();

    if (!value || value.length > 512 || /[\p{Cc}\s\\]/u.test(value)) {
        return null;
    }

    const candidate = value.includes('://') ? value : `https://${value}`;
    // Check the raw authority too: URL parsing erases default ports and trailing dots.
    const authority = candidate.match(/^https?:\/\/([^/?#]+)/i)?.[1];

    if (!authority || !DOMAIN.test(authority) || LOCAL_DOMAIN.test(authority)) {
        return null;
    }

    try {
        const url = new URL(candidate);

        if (
            !['http:', 'https:'].includes(url.protocol) ||
            !DOMAIN.test(url.hostname)
        ) {
            return null;
        }

        url.hash = '';
        const normalized = url.toString();

        return normalized.length <= 512 ? normalized : null;
    } catch {
        return null;
    }
};
