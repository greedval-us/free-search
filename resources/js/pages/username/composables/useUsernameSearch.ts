import { computed, reactive, ref } from 'vue';
import type { UsernameAnalytics, UsernameSearchResponse } from '../types';

type TranslateFn = (key: string) => string;

export const useUsernameSearch = (t: TranslateFn) => {
    const form = reactive({
        username: '',
    });

    const loading = ref(false);
    const error = ref<string | null>(null);
    const items = ref<UsernameSearchResponse['items']>([]);
    const checkedAt = ref<string | null>(null);
    const summary = ref<UsernameSearchResponse['summary'] | null>(null);
    const analytics = ref<UsernameAnalytics | null>(null);
    const localDiff = ref<{
        hasPrevious: boolean;
        changedCount: number;
        newlyFound: string[];
        becameNotFound: string[];
        becameUnknown: string[];
    } | null>(null);

    const canSearch = computed(() => form.username.trim().length >= 2);

    const normalizedUsername = computed(() => form.username.trim().replace(/^@+/, ''));

    const reportUrl = (download = false) => {
        const locale =
            typeof document !== 'undefined' && document.documentElement.lang.toLowerCase().startsWith('ru')
                ? 'ru'
                : 'en';
        const query = new URLSearchParams({
            username: normalizedUsername.value,
            locale,
        });

        if (download) {
            query.set('download', '1');
        }

        return `/username/report?${query.toString()}`;
    };

    const search = async () => {
        if (!canSearch.value) {
            error.value = t('username.errors.usernameRequired');

            return;
        }

        loading.value = true;
        error.value = null;
        items.value = [];
        checkedAt.value = null;
        summary.value = null;
        analytics.value = null;
        localDiff.value = null;

        try {
            const endpoint = `/username/search?username=${encodeURIComponent(normalizedUsername.value)}`;
            const response = await fetch(endpoint, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
            });

            const payload = await safeJson(response);
            const apiError = resolveApiError(payload);

            if (!response.ok) {
                error.value = apiError ?? t('username.errors.searchFailed');
                return;
            }

            if (!payload || !payload.ok) {
                error.value = apiError ?? t('username.errors.searchFailed');

                return;
            }

            items.value = payload.items ?? [];
            checkedAt.value = payload.checkedAt ?? null;
            summary.value = payload.summary ?? null;
            analytics.value = payload.analytics ?? null;
            localDiff.value = computeLocalDiff(normalizedUsername.value, payload.items ?? []);
        } catch (exception) {
            error.value = exception instanceof Error ? exception.message : t('username.errors.searchFailed');
        } finally {
            loading.value = false;
        }
    };

    const openReport = () => {
        if (typeof window === 'undefined' || !normalizedUsername.value) {
            return;
        }

        window.open(reportUrl(), '_blank', 'noopener,noreferrer');
    };

    const downloadReport = () => {
        if (typeof window === 'undefined' || !normalizedUsername.value) {
            return;
        }

        window.open(reportUrl(true), '_blank', 'noopener,noreferrer');
    };

    const canUseReportActions = computed(
        () => !loading.value && normalizedUsername.value.length >= 2 && analytics.value !== null
    );

    return {
        form,
        loading,
        error,
        items,
        checkedAt,
        summary,
        analytics,
        localDiff,
        canSearch,
        normalizedUsername,
        search,
        openReport,
        downloadReport,
        canUseReportActions,
    };
};

const STORAGE_PREFIX = 'username:snapshot:';

const safeJson = async (response: Response): Promise<UsernameSearchResponse | null> => {
    const contentType = response.headers.get('content-type') ?? '';

    if (!contentType.includes('application/json')) {
        return null;
    }

    try {
        return (await response.json()) as UsernameSearchResponse;
    } catch {
        return null;
    }
};

const resolveApiError = (payload: UsernameSearchResponse | null): string | null => {
    if (!payload || typeof payload !== 'object') {
        return null;
    }

    const anyPayload = payload as Record<string, unknown>;
    const message = anyPayload.message;

    if (typeof message === 'string' && message.trim() !== '') {
        return message;
    }

    const errors = anyPayload.errors;

    if (errors && typeof errors === 'object') {
        for (const value of Object.values(errors as Record<string, unknown>)) {
            if (Array.isArray(value) && value.length > 0 && typeof value[0] === 'string') {
                return value[0];
            }
        }
    }

    return null;
};

const computeLocalDiff = (
    username: string,
    items: UsernameSearchResponse['items']
) => {
    if (typeof window === 'undefined') {
        return {
            hasPrevious: false,
            changedCount: 0,
            newlyFound: [] as string[],
            becameNotFound: [] as string[],
            becameUnknown: [] as string[],
        };
    }

    const key = STORAGE_PREFIX + username.toLowerCase();
    const currentMap: Record<string, { name: string; status: string }> = {};

    for (const item of items) {
        currentMap[item.key] = {
            name: item.name,
            status: item.status,
        };
    }

    const raw = window.localStorage.getItem(key);
    const previousMap = raw ? (JSON.parse(raw) as Record<string, { name: string; status: string }>) : null;

    const diff = {
        hasPrevious: Boolean(previousMap),
        changedCount: 0,
        newlyFound: [] as string[],
        becameNotFound: [] as string[],
        becameUnknown: [] as string[],
    };

    if (previousMap) {
        for (const [platformKey, current] of Object.entries(currentMap)) {
            const previous = previousMap[platformKey];

            if (!previous || previous.status === current.status) {
                continue;
            }

            diff.changedCount += 1;

            if (current.status === 'found') {
                diff.newlyFound.push(current.name);
            } else if (current.status === 'not_found') {
                diff.becameNotFound.push(current.name);
            } else {
                diff.becameUnknown.push(current.name);
            }
        }
    }

    window.localStorage.setItem(key, JSON.stringify(currentMap));

    return diff;
};
