import { computed, reactive, ref } from 'vue';
import { apiRequest, resolveClientErrorMessage } from '@/lib/api';
import { getDocumentLocale } from '@/lib/locale';
import { analytics, report } from '@/routes/site-intel';
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
                analytics.url(),
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
            error.value = resolveClientErrorMessage(
                exception,
                t('siteIntel.analytics.errors.analyzeFailed')
            );
        } finally {
            loading.value = false;
        }
    };

    const showReport = (download: boolean) => {
        if (!canUseReportActions.value || typeof window === 'undefined') {
            return;
        }

        const url = report.url({
            query: {
                target: result.value?.target.url || form.target.trim(),
                locale: getDocumentLocale(),
                download: download ? '1' : undefined,
            },
        });

        window.open(url, '_blank', 'noopener,noreferrer');
    };

    return {
        form,
        loading,
        error,
        result,
        canAnalyze,
        canUseReportActions,
        analyze,
        openReport: () => showReport(false),
        downloadReport: () => showReport(true),
    };
};
