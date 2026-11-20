import { computed, reactive, ref } from 'vue';
import { apiRequest, resolveClientErrorMessage } from '@/lib/api';
import { getDocumentLocale } from '@/lib/locale';
import { seoAudit, seoReport } from '@/routes/site-intel';
import { DEFAULT_SEO_AUDIT_OPTIONS } from '../lib/seo-audit-options';
import type { SiteIntelSeoAuditResult } from '../types';

type TranslateFn = (key: string) => string;

export const useSeoAudit = (t: TranslateFn) => {
    const form = reactive({
        target: '',
        ...DEFAULT_SEO_AUDIT_OPTIONS,
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
                seoAudit.url(),
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

    const showReport = (download: boolean) => {
        if (typeof window === 'undefined') {
            return;
        }

        const url = seoReport.url({
            query: {
                target: result.value?.target.finalUrl || form.target.trim(),
                locale: getDocumentLocale(),
                crawl_limit: String(form.crawlLimit),
                platform_type: form.platformType,
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
