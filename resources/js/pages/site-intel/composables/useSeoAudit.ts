import { computed, reactive, ref } from 'vue';
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
    const canUseReportActions = computed(() => !loading.value && result.value !== null);

    const analyze = async () => {
        if (!canAnalyze.value) {
            error.value = t('siteIntel.seoAudit.errors.targetRequired');

            return;
        }

        loading.value = true;
        error.value = null;
        result.value = null;

        try {
            const query = new URLSearchParams({
                target: form.target.trim(),
                crawl_limit: String(form.crawlLimit),
                platform_type: form.platformType,
            });

            const response = await fetch(`/site-intel/seo-audit?${query.toString()}`, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
            });

            const payload = await safeJson(response);
            const apiError = resolveApiError(payload);

            if (!response.ok || !payload?.ok) {
                error.value = apiError ?? t('siteIntel.seoAudit.errors.analyzeFailed');

                return;
            }

            result.value = payload.data as SiteIntelSeoAuditResult;
        } catch (exception) {
            error.value = exception instanceof Error ? exception.message : t('siteIntel.seoAudit.errors.analyzeFailed');
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
        typeof document !== 'undefined' && document.documentElement.lang.toLowerCase().startsWith('ru')
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

const openReport = (form: { target: string; crawlLimit: number; platformType: string }, result: SiteIntelSeoAuditResult | null) => {
    if (typeof window === 'undefined') {
        return;
    }
    window.open(reportUrl(form, result, false), '_blank', 'noopener,noreferrer');
};

const downloadReport = (form: { target: string; crawlLimit: number; platformType: string }, result: SiteIntelSeoAuditResult | null) => {
    if (typeof window === 'undefined') {
        return;
    }
    window.open(reportUrl(form, result, true), '_blank', 'noopener,noreferrer');
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
