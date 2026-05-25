import { computed, reactive, ref } from 'vue';
import { apiRequest } from '@/lib/api';
import type { DomainLiteResult } from '../types';

type TranslateFn = (key: string) => string;

export const useDomainLite = (t: TranslateFn) => {
    const form = reactive({
        domain: '',
    });

    const loading = ref(false);
    const error = ref<string | null>(null);
    const result = ref<DomainLiteResult | null>(null);

    const canLookup = computed(() => form.domain.trim().length >= 3);

    const lookup = async () => {
        if (!canLookup.value) {
            error.value = t('siteIntel.domainLite.errors.domainRequired');

            return;
        }

        loading.value = true;
        error.value = null;
        result.value = null;

        try {
            const apiResult = await apiRequest<DomainLiteResult>(
                '/site-intel/domain-lite',
                {
                    method: 'GET',
                    query: {
                        domain: form.domain.trim(),
                    },
                }
            );

            if (!apiResult.ok) {
                error.value =
                    apiResult.message ??
                    t('siteIntel.domainLite.errors.lookupFailed');

                return;
            }

            result.value = apiResult.data;
        } catch (exception) {
            error.value =
                exception instanceof Error
                    ? exception.message
                    : t('siteIntel.domainLite.errors.lookupFailed');
        } finally {
            loading.value = false;
        }
    };

    return {
        form,
        loading,
        error,
        result,
        canLookup,
        lookup,
    };
};
