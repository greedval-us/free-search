const decodeParam = (value: string | null): string => {
    if (typeof value !== 'string') {
        return '';
    }

    return value.trim();
};

export const getRepeatQueryParams = (): URLSearchParams | null => {
    if (typeof window === 'undefined') {
        return null;
    }

    return new URLSearchParams(window.location.search);
};

export const readRepeatQueryParam = (params: URLSearchParams, keys: string[]): string => {
    for (const key of keys) {
        const value = decodeParam(params.get(key));
        if (value !== '') {
            return value;
        }
    }

    return '';
};

export const readRepeatQueryInt = (params: URLSearchParams, key: string): number | null => {
    const value = decodeParam(params.get(key));
    if (!/^\d+$/.test(value)) {
        return null;
    }

    const numeric = Number(value);

    if (!Number.isFinite(numeric)) {
        return null;
    }

    return Math.trunc(numeric);
};

export const isRepeatAutorunEnabled = (params: URLSearchParams): boolean =>
    readRepeatQueryParam(params, ['autorun']) === '1';

