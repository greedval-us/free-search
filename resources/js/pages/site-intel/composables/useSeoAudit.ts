import { computed, reactive, ref } from 'vue';
import { apiRequest, resolveClientErrorMessage } from '@/lib/api';
import type { SiteIntelSeoAuditResult } from '../types';

type TranslateFn = (key: string) => string;

export const useSeoAudit = (t: TranslateFn) => {
    const form = reactive({
        target: '',
        crawlLimit: 8,
        platformType: 'auto',
    });

    const loading = ref(false);
    const error = ref<string | null>(null);
    const result = ref<SiteIntelSeoAuditResult | null>(null);

    const canAnalyze = computed(() => form.target.trim().length >= 3);
    const canUseReportActions = computed(
        () => !loading.value && result.value !== null
    );

    const analyze = async () => {
        if (!canAnalyze.value) {
            error.value = t('siteIntel.seoAudit.errors.targetRequired');

            return;
        }

        loading.value = true;
        error.value = null;
        result.value = null;

        try {
            const apiResult = await apiRequest<SiteIntelSeoAuditResult>(
                '/site-intel/seo-audit',
                {
                    method: 'GET',
                    query: {
                        target: form.target.trim(),
                        crawl_limit: String(form.crawlLimit),
                        platform_type: form.platformType,
                    },
                }
            );

            if (!apiResult.ok) {
                error.value =
                    apiResult.message ??
                    t('siteIntel.seoAudit.errors.analyzeFailed');

                return;
            }

            result.value = apiResult.data;
        } catch (exception) {
            error.value = resolveClientErrorMessage(
                exception,
                t('siteIntel.seoAudit.errors.analyzeFailed')
            );
        } finally {
            loading.value = false;
        }
    };

    return {
        form,
        loading,
        error,
        result,
        canAnalyze,
        canUseReportActions,
        analyze,
        openReport: () => openReport(form, result.value),
        downloadReport: () => downloadReport(form, result.value),
    };
};

const reportUrl = (
    form: { target: string; crawlLimit: number; platformType: string },
    result: SiteIntelSeoAuditResult | null,
    download = false
) => {
    const locale =
        typeof document !== 'undefined' &&
        document.documentElement.lang.toLowerCase().startsWith('ru')
            ? 'ru'
            : 'en';
    const target = result?.target.finalUrl || form.target.trim();
    const query = new URLSearchParams({
        target,
        locale,
        crawl_limit: String(form.crawlLimit),
        platform_type: form.platformType,
    });

    if (download) {
        query.set('download', '1');
    }

    return `/site-intel/seo-report?${query.toString()}`;
};

const openReport = (
    form: { target: string; crawlLimit: number; platformType: string },
    result: SiteIntelSeoAuditResult | null
) => {
    if (typeof window === 'undefined') {
        return;
    }

    window.open(
        reportUrl(form, result, false),
        '_blank',
        'noopener,noreferrer'
    );
};

const downloadReport = (
    form: { target: string; crawlLimit: number; platformType: string },
    result: SiteIntelSeoAuditResult | null
) => {
    if (typeof window === 'undefined') {
        return;
    }

    window.open(reportUrl(form, result, true), '_blank', 'noopener,noreferrer');
};
