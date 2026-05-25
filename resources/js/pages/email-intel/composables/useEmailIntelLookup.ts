import { computed, reactive, ref } from 'vue';
import type { Ref } from 'vue';
import { apiRequest } from '@/lib/api';
import type { EmailIntelResult } from '../types';

type TranslateFn = (key: string) => string;

export const useEmailIntelLookup = (
    t: TranslateFn,
    locale: Ref<'en' | 'ru'>
) => {
    const form = reactive({
        email: '',
    });

    const loading = ref(false);
    const error = ref<string | null>(null);
    const result = ref<EmailIntelResult | null>(null);

    const canLookup = computed(() =>
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())
    );

    const reset = () => {
        form.email = '';
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
            const apiResult = await apiRequest<EmailIntelResult>(
                '/email-intel/lookup',
                {
                    method: 'GET',
                    query: {
                        email: form.email.trim(),
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
        form,
        loading,
        error,
        result,
        canLookup,
        lookup,
        reset,
    };
};
