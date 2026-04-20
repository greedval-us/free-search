import { computed, reactive, ref } from 'vue';
import type { SiteIntelAnalyticsResult } from '../types';

type TranslateFn = (key: string) => string;

export const useSiteIntelAnalytics = (t: TranslateFn) => {
    const form = reactive({
        target: '',
    });

    const loading = ref(false);
    const error = ref<string | null>(null);
    const result = ref<SiteIntelAnalyticsResult | null>(null);

    const canAnalyze = computed(() => form.target.trim().length >= 3);
    const canUseReportActions = computed(() => {
        return !loading.value && result.value !== null;
    });

    const analyze = async () => {
        if (!canAnalyze.value) {
            error.value = t('siteIntel.analytics.errors.targetRequired');

            return;
        }

        loading.value = true;
        error.value = null;
        result.value = null;

        try {
            const query = new URLSearchParams({
                target: form.target.trim(),
            });
            const response = await fetch(`/site-intel/analytics?${query.toString()}`, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
            });

            const payload = await safeJson(response);
            const apiError = resolveApiError(payload);

            if (!response.ok || !payload?.ok) {
                error.value = apiError ?? t('siteIntel.analytics.errors.analyzeFailed');

                return;
            }

            result.value = payload.data as SiteIntelAnalyticsResult;
        } catch (exception) {
            error.value = exception instanceof Error ? exception.message : t('siteIntel.analytics.errors.analyzeFailed');
        } finally {
            loading.value = false;
        }
    };

    const reportUrl = (download = false) => {
        const locale =
            typeof document !== 'undefined' && document.documentElement.lang.toLowerCase().startsWith('ru')
                ? 'ru'
                : 'en';
        const target = result.value?.target.url || form.target.trim();
        const query = new URLSearchParams({
            target,
            locale,
        });

        if (download) {
            query.set('download', '1');
        }

        return `/site-intel/report?${query.toString()}`;
    };

    const openReport = () => {
        if (!canUseReportActions.value || typeof window === 'undefined') {
            return;
        }

        window.open(reportUrl(), '_blank', 'noopener,noreferrer');
    };

    const downloadReport = () => {
        if (!canUseReportActions.value || typeof window === 'undefined') {
            return;
        }

        window.open(reportUrl(true), '_blank', 'noopener,noreferrer');
    };

    return {
        form,
        loading,
        error,
        result,
        canAnalyze,
        canUseReportActions,
        analyze,
        openReport,
        downloadReport,
    };
};

const safeJson = async (response: Response): Promise<Record<string, unknown> | null> => {
    const contentType = response.headers.get('content-type') ?? '';

    if (!contentType.includes('application/json')) {
        return null;
    }

    try {
        return (await response.json()) as Record<string, unknown>;
    } catch {
        return null;
    }
};

const resolveApiError = (payload: Record<string, unknown> | null): string | null => {
    if (!payload || typeof payload !== 'object') {
        return null;
    }

    const message = payload.message;

    if (typeof message === 'string' && message.trim() !== '') {
        return message;
    }

    const errors = payload.errors;

    if (errors && typeof errors === 'object') {
        for (const value of Object.values(errors as Record<string, unknown>)) {
            if (Array.isArray(value) && value.length > 0 && typeof value[0] === 'string') {
                return value[0];
            }
        }
    }

    return null;
};
