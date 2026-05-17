import { computed, ref, type Ref } from 'vue';
import { apiRequest } from '@/lib/api';

type UseIntelLookupOptions = {
    endpoint: string;
    minLength: number;
    queryKey: string;
    locale: Ref<string>;
    requiredError: string;
    fallbackError: string;
};

export function useIntelLookup<T>(input: Ref<string>, options: UseIntelLookupOptions) {
    const loading = ref(false);
    const error = ref<string | null>(null);
    const result = ref<T | null>(null);

    const canSearch = computed(() => input.value.trim().length >= options.minLength);

    const lookup = async () => {
        if (!canSearch.value) {
            error.value = options.requiredError;
            return;
        }

        loading.value = true;
        error.value = null;
        result.value = null;

        const res = await apiRequest<T>(options.endpoint, {
            method: 'GET',
            query: {
                [options.queryKey]: input.value.trim(),
                locale: options.locale.value,
            },
        });

        if (!res.ok) {
            error.value = res.message ?? options.fallbackError;
            loading.value = false;
            return;
        }

        result.value = res.data;
        loading.value = false;
    };

    return {
        loading,
        error,
        result,
        canSearch,
        lookup,
    };
}

