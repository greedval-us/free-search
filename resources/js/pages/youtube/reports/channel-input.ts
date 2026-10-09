export const CHANNEL_ID_PATTERN = /^UC[a-zA-Z0-9_-]{22}$/;
export const CHANNEL_HANDLE_PATTERN = /^@[\p{L}\p{N}_.-]{3,30}$/u;

export const normalizeReportChannel = (value: string): string => {
    const input = value.trim();

    if (input.startsWith('@')) {
        return input.toLowerCase();
    }

    const youtubePath = input.match(
        /^(?:https?:\/\/)?(?:(?:www|m)\.)?youtube\.com(\/[^?#]*)$/i
    );

    if (!youtubePath) {
        return input;
    }

    try {
        const url = new URL(input.includes('://') ? input : `https://${input}`);

        if (
            !['http:', 'https:'].includes(url.protocol) ||
            !['youtube.com', 'www.youtube.com', 'm.youtube.com'].includes(
                url.hostname
            ) ||
            url.port ||
            url.username ||
            url.password ||
            url.search ||
            url.hash
        ) {
            return input;
        }

        const path = decodeURIComponent(youtubePath[1]).replace(/\/$/, '');
        const channel = path.startsWith('/channel/')
            ? path.slice('/channel/'.length)
            : path.slice(1);

        if (CHANNEL_ID_PATTERN.test(channel)) {
            return channel;
        }

        if (CHANNEL_HANDLE_PATTERN.test(channel)) {
            return channel.toLowerCase();
        }
    } catch {
        return input;
    }

    return input;
};
