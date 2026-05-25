import { computed, reactive, ref } from 'vue';
import { apiRequest } from '@/lib/api';
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
            const apiResult = await apiRequest<SiteIntelAnalyticsResult>(
                '/site-intel/analytics',
                {
                    method: 'GET',
                    query: {
                        target: form.target.trim(),
                    },
                }
            );

            if (!apiResult.ok) {
                error.value =
                    apiResult.message ??
                    t('siteIntel.analytics.errors.analyzeFailed');

                return;
            }

            result.value = apiResult.data;
        } catch (exception) {
            error.value =
                exception instanceof Error
                    ? exception.message
                    : t('siteIntel.analytics.errors.analyzeFailed');
        } finally {
            loading.value = false;
        }
    };

    const reportUrl = (download = false) => {
        const locale =
            typeof document !== 'undefined' &&
            document.documentElement.lang.toLowerCase().startsWith('ru')
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
