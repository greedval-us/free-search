import { computed, reactive, ref } from 'vue';
import { apiRequest, resolveClientErrorMessage } from '@/lib/api';
import type { SiteHealthResult } from '../types';

type TranslateFn = (key: string) => string;

export const useSiteHealth = (t: TranslateFn) => {
    const form = reactive({
        target: '',
    });

    const loading = ref(false);
    const error = ref<string | null>(null);
    const result = ref<SiteHealthResult | null>(null);

    const canCheck = computed(() => form.target.trim().length >= 3);

    const check = async () => {
        if (!canCheck.value) {
            error.value = t('siteIntel.siteHealth.errors.targetRequired');

            return;
        }

        loading.value = true;
        error.value = null;
        result.value = null;

        try {
            const apiResult = await apiRequest<SiteHealthResult>(
                '/site-intel/site-health',
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
                    t('siteIntel.siteHealth.errors.checkFailed');

                return;
            }

            result.value = apiResult.data;
        } catch (exception) {
            error.value = resolveClientErrorMessage(
                exception,
                t('siteIntel.siteHealth.errors.checkFailed')
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
        canCheck,
        check,
    };
};
