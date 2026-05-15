export const buildQueryString = (query?: Record<string, string | number | boolean | null | undefined>) => {
    if (!query) {
        return '';
    }

    const params = new URLSearchParams();

    for (const [key, value] of Object.entries(query)) {
        if (value === null || value === undefined) {
            continue;
        }

        params.append(key, String(value));
    }

    const serialized = params.toString();

    return serialized ? `?${serialized}` : '';
};

