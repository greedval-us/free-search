import { computed, ref } from 'vue';
import type { ComputedRef } from 'vue';
import { apiRequest } from '@/lib/api';
import type { EmailBulkIntelResult } from '../types';

type Translate = (key: string) => string;

export const useEmailBulkLookup = (
    t: Translate,
    locale: ComputedRef<string>
) => {
    const emails = ref('');
    const loading = ref(false);
    const error = ref<string | null>(null);
    const result = ref<EmailBulkIntelResult | null>(null);

    const canLookup = computed(() => emails.value.trim().length > 0);

    const reset = () => {
        emails.value = '';
        error.value = null;
        result.value = null;
        loading.value = false;
    };

    const lookup = async () => {
        if (!canLookup.value) {
            error.value = t('emailIntel.errors.emailRequired');

            return;
        }

        loading.value = true;
        error.value = null;
        result.value = null;

        try {
            const apiResult = await apiRequest<EmailBulkIntelResult>(
                '/email-intel/bulk',
                {
                    method: 'GET',
                    query: {
                        emails: emails.value,
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
        emails,
        error,
        loading,
        lookup,
        reset,
        result,
    };
};
