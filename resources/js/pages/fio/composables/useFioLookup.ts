import { computed, reactive, ref } from 'vue';
import type { Ref } from 'vue';
import { apiRequest } from '@/lib/api';
import type { FioLookupResult } from '../types';

type TranslateFn = (key: string) => string;

export const useFioLookup = (t: TranslateFn, locale: Ref<'en' | 'ru'>) => {
    const form = reactive({
        fullName: '',
        qualifier: '',
    });

    const loading = ref(false);
    const error = ref<string | null>(null);
    const result = ref<FioLookupResult | null>(null);

    const canLookup = computed(() => form.fullName.trim().length >= 3);

    const lookup = async () => {
        if (!canLookup.value) {
            error.value = t('fio.lookup.errors.fullNameRequired');

            return;
        }

        loading.value = true;
        error.value = null;
        result.value = null;

        try {
            const apiResult = await apiRequest<FioLookupResult>('/fio/lookup', {
                method: 'GET',
                query: {
                    full_name: form.fullName.trim(),
                    qualifier:
                        form.qualifier.trim().length > 0
                            ? form.qualifier.trim()
                            : undefined,
                    locale: locale.value,
                },
            });

            if (!apiResult.ok) {
                error.value =
                    apiResult.message ?? t('fio.lookup.errors.lookupFailed');

                return;
            }

            result.value = apiResult.data;
        } catch (exception) {
            error.value =
                exception instanceof Error
                    ? exception.message
                    : t('fio.lookup.errors.lookupFailed');
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
