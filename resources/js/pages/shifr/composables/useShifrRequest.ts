import { computed, ref  } from 'vue';
import type {Ref} from 'vue';

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
  hasInput: Ref<boolean>,
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
      const response = await fetch(`${endpoint}?${params.toString()}`, {
        headers: { Accept: 'application/json' },
      });
      const payload = await response.json();

      if (!response.ok || !payload?.ok) {
        error.value = payload?.message ?? requestFailedMessage();

        return;
      }

      result.value = payload.data as Record<string, unknown>;
    } catch (exception) {
      error.value = exception instanceof Error ? exception.message : requestFailedMessage();
    } finally {
      loading.value = false;
    }
  };

  return { loading, error, result, canRun, run, reset };
};
