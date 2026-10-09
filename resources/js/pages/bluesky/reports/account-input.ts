const HANDLE_PATTERN =
    /^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z](?:[a-z0-9-]{0,61}[a-z0-9])?$/i;
const RESERVED_HANDLE_SUFFIX =
    /\.(?:alt|arpa|example|internal|invalid|local|localhost|onion|test)$/i;

const isHandle = (value: string): boolean =>
    HANDLE_PATTERN.test(value) && !RESERVED_HANDLE_SUFFIX.test(value);

export const isReportAccount = (value: string): boolean =>
    value.length <= 255 &&
    (isHandle(value) ||
        /^did:plc:[a-z2-7]{24}$/.test(value) ||
        (value.startsWith('did:web:') &&
            isHandle(value.slice('did:web:'.length))));

export const normalizeReportAccount = (value: string): string => {
    const input = value.trim();

    if (/^(?:https?:\/\/)?bsky\.app\//i.test(input)) {
        try {
            const url = new URL(
                input.includes('://') ? input : `https://${input}`
            );

            if (
                url.protocol !== 'https:' ||
                url.hostname !== 'bsky.app' ||
                url.port ||
                url.username ||
                url.password ||
                url.search ||
                url.hash
            ) {
                return input;
            }

            const path = input.replace(/^(?:https:\/\/)?bsky\.app/i, '');
            const profile = path.match(/^\/profile\/([^/]+)\/?$/);

            if (!profile) {
                return input;
            }

            const actor = decodeURIComponent(profile[1]).replace(/^@/, '');

            return actor.startsWith('did:') ? actor : actor.toLowerCase();
        } catch {
            return input;
        }
    }

    const actor = input.replace(/^@/, '');

    return actor.startsWith('did:') ? actor : actor.toLowerCase();
};
