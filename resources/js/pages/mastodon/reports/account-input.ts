const USERNAME_PATTERN = /^[a-z0-9_]{1,64}$/i;
const DOMAIN_PATTERN =
    /^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z](?:[a-z0-9-]{0,61}[a-z0-9])?$/i;
const RESERVED_HOST_SUFFIX = /\.(?:localhost|local|internal|test|invalid)$/i;

const isHost = (value: string): boolean =>
    DOMAIN_PATTERN.test(value) && !RESERVED_HOST_SUFFIX.test(value);

export const isReportAccount = (value: string): boolean => {
    const parts = value.split('@');

    return (
        value.length <= 255 &&
        parts.length <= 2 &&
        USERNAME_PATTERN.test(parts[0]) &&
        (parts.length === 1 || isHost(parts[1]))
    );
};

export const normalizeReportAccount = (value: string): string => {
    const input = value.trim();

    if (input.length > 255) {
        return input;
    }

    if (/^https?:\/\//i.test(input)) {
        const url = input.match(/^https?:\/\/([^/?#]+)(\/[^?#]*)$/i);
        const profile = url?.[2].match(
            /^\/(?:@|users\/)([A-Za-z0-9_]{1,64})\/?$/
        );

        return url && profile && isHost(url[1])
            ? `${profile[1]}@${url[1]}`.toLowerCase()
            : input;
    }

    return input.replace(/^@/, '').toLowerCase();
};
