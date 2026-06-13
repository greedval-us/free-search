import { computed, ref } from 'vue';
import type { Ref } from 'vue';
import { apiRequest, resolveClientErrorMessage } from '@/lib/api';

export interface ShifrRequestState {
    loading: Ref<boolean>;
    error: Ref<string | null>;
    result: Ref<Record<string, unknown> | null>;
    canRun: Ref<boolean>;
    run: (params: URLSearchParams) => Promise<void>;
    reset: () => void;
}

export const useShifrRequest = (
    endpoint: string,
    requestFailedMessage: () => string,
    hasInput: Ref<boolean>
): ShifrRequestState => {
    const loading = ref(false);
    const error = ref<string | null>(null);
    const result = ref<Record<string, unknown> | null>(null);

    const canRun = computed(() => hasInput.value && !loading.value);

    const reset = (): void => {
        error.value = null;
        result.value = null;
    };

    const run = async (params: URLSearchParams): Promise<void> => {
        if (!canRun.value) {
            return;
        }

        loading.value = true;
        reset();

        try {
            const query = Object.fromEntries(params.entries());
            const apiResult = await apiRequest<Record<string, unknown>>(
                endpoint,
                {
                    method: 'GET',
                    query,
                }
            );

            if (!apiResult.ok) {
                error.value = apiResult.message ?? requestFailedMessage();

                return;
            }

            result.value = apiResult.data;
        } catch (exception) {
            error.value = resolveClientErrorMessage(
                exception,
                requestFailedMessage()
            );
        } finally {
            loading.value = false;
        }
    };

    return { loading, error, result, canRun, run, reset };
};
