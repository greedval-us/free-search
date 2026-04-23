import { computed, reactive, ref } from 'vue';
import type { DorkGoal, DorkScope, DorksApiResponse, DorksSearchPayload } from '../types';

type TranslateFn = (key: string) => string;

export const useDorksSearch = (t: TranslateFn) => {
    const translateGoal = (goal: DorkGoal): DorkGoal => {
        const key = `dorks.goals.${goal.key}`;
        const translated = t(key);

        return {
            ...goal,
            label: translated !== key ? translated : goal.label,
        };
    };

    const translateScope = (scope: DorkScope): DorkScope => {
        const key = `dorks.scopes.${scope.key}`;
        const translated = t(key);

        return {
            ...scope,
            label: translated !== key ? translated : scope.label,
        };
    };

    const form = reactive({
        target: '',
        site: '',
        scope: 'all',
        goal: 'all',
    });

    const loading = ref(false);
    const error = ref<string | null>(null);
    const payload = ref<DorksSearchPayload | null>(null);
    const goals = ref<DorkGoal[]>([
        translateGoal({ key: 'all', label: 'All goals' }),
    ]);
    const scopes = ref<DorkScope[]>([
        translateScope({ key: 'all', label: 'All scopes' }),
    ]);

    const canSearch = computed(() => form.target.trim().length >= 2 || form.site.trim().length >= 3);
    const canUseReportActions = computed(() => payload.value !== null && !loading.value);

    const normalizeTarget = () => form.target.trim();
    const normalizeGoal = () => (form.goal.trim() !== '' ? form.goal.trim() : 'all');
    const normalizeSite = () => form.site.trim();
    const normalizeScope = () => (form.scope.trim() !== '' ? form.scope.trim() : 'all');

    const locale = () =>
        typeof document !== 'undefined' && document.documentElement.lang.toLowerCase().startsWith('ru')
            ? 'ru'
            : 'en';

    const reportUrl = (download = false): string => {
        const params = new URLSearchParams({
            target: normalizeTarget(),
            site: normalizeSite(),
            scope: normalizeScope(),
            goal: normalizeGoal(),
            locale: locale(),
        });

        if (download) {
            params.set('download', '1');
        }

        return `/dorks/report?${params.toString()}`;
    };

    const loadGoals = async () => {
        try {
            const response = await fetch(`/dorks/goals?locale=${locale()}`, {
                method: 'GET',
                headers: { Accept: 'application/json' },
            });
            const data = (await response.json()) as DorksApiResponse;
            if (response.ok && data.ok && Array.isArray(data.goals) && data.goals.length > 0) {
                goals.value = data.goals.map(translateGoal);
            }
            if (response.ok && data.ok && Array.isArray(data.scopes) && data.scopes.length > 0) {
                scopes.value = data.scopes.map(translateScope);
            }
        } catch {
            // ignore and keep defaults
        }
    };

    const search = async () => {
        if (!canSearch.value) {
            error.value = t('dorks.errors.targetRequired');
            return;
        }

        loading.value = true;
        error.value = null;
        payload.value = null;

        try {
            const params = new URLSearchParams({
                target: normalizeTarget(),
                site: normalizeSite(),
                scope: normalizeScope(),
                goal: normalizeGoal(),
                locale: locale(),
            });

            const response = await fetch(`/dorks/search?${params.toString()}`, {
                method: 'GET',
                headers: { Accept: 'application/json' },
            });
            const data = (await response.json()) as DorksApiResponse;

            if (!response.ok || !data.ok) {
                error.value = resolveError(data) ?? t('dorks.errors.searchFailed');
                return;
            }

            payload.value = data as unknown as DorksSearchPayload;
            if (Array.isArray(data.availableGoals) && data.availableGoals.length > 0) {
                goals.value = data.availableGoals.map(translateGoal);
            }
            if (Array.isArray(data.availableScopes) && data.availableScopes.length > 0) {
                scopes.value = data.availableScopes.map(translateScope);
            }
        } catch (exception) {
            error.value = exception instanceof Error ? exception.message : t('dorks.errors.searchFailed');
        } finally {
            loading.value = false;
        }
    };

    const openReport = () => {
        if (typeof window === 'undefined' || !canUseReportActions.value) {
            return;
        }

        window.open(reportUrl(), '_blank', 'noopener,noreferrer');
    };

    const downloadReport = () => {
        if (typeof window === 'undefined' || !canUseReportActions.value) {
            return;
        }

        window.open(reportUrl(true), '_blank', 'noopener,noreferrer');
    };

    return {
        form,
        loading,
        error,
        payload,
        goals,
        scopes,
        canSearch,
        canUseReportActions,
        loadGoals,
        search,
        openReport,
        downloadReport,
    };
};

const resolveError = (payload: DorksApiResponse | null): string | null => {
    if (!payload) {
        return null;
    }

    if (typeof payload.message === 'string' && payload.message.trim() !== '') {
        return payload.message;
    }

    if (payload.errors && typeof payload.errors === 'object') {
        for (const fieldErrors of Object.values(payload.errors)) {
            if (Array.isArray(fieldErrors) && fieldErrors.length > 0 && typeof fieldErrors[0] === 'string') {
                return fieldErrors[0];
            }
        }
    }

    return null;
};
