import { computed, ref } from 'vue';
import type { ComputedRef } from 'vue';
import { apiRequest } from '@/lib/api';
import type { DomainMailPostureResult } from '../types';

type Translate = (key: string) => string;

const domainPattern = /^(?!-)(?:[a-z0-9-]{1,63}\.)+[a-z]{2,63}$/i;

export const useDomainMailPosture = (
    t: Translate,
    locale: ComputedRef<string>
) => {
    const domain = ref('');
    const loading = ref(false);
    const error = ref<string | null>(null);
    const result = ref<DomainMailPostureResult | null>(null);

    const canLookup = computed(() => domainPattern.test(domain.value.trim()));

    const reset = () => {
        domain.value = '';
        error.value = null;
        result.value = null;
        loading.value = false;
    };

    const lookup = async () => {
        if (!canLookup.value) {
            error.value = t('emailIntel.errors.domainRequired');

            return;
        }

        loading.value = true;
        error.value = null;
        result.value = null;

        try {
            const apiResult = await apiRequest<DomainMailPostureResult>(
                '/email-intel/domain-posture',
                {
                    method: 'GET',
                    query: {
                        domain: domain.value.trim(),
                        locale: locale.value,
                    },
                }
            );

            if (!apiResult.ok) {
                error.value =
                    apiResult.message ?? t('emailIntel.errors.lookupFailed');

                return;
            }

            result.value = apiResult.data;
        } catch (exception) {
            error.value =
                exception instanceof Error
                    ? exception.message
                    : t('emailIntel.errors.lookupFailed');
        } finally {
            loading.value = false;
        }
    };

    return {
        canLookup,
        domain,
        error,
        loading,
        lookup,
        reset,
        result,
    };
};
