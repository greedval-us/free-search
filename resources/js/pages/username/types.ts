export type UsernameTabValue = 'search';

export type UsernameSearchStatus = 'found' | 'not_found' | 'unknown';

export type UsernameSearchItem = {
    key: string;
    name: string;
    profileUrl: string;
    regionGroup: string;
    primaryUsersRegion: string;
    status: UsernameSearchStatus;
    httpStatus: number | null;
    error: string | null;
};

export type UsernameSearchResponse = {
    ok: boolean;
    username: string;
    checkedAt: string;
    summary: {
        checked: number;
        found: number;
        notFound: number;
        unknown: number;
    };
    items: UsernameSearchItem[];
};
