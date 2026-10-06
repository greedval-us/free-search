import { onScopeDispose, ref, watch } from 'vue';
import type { Ref } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { ApiError, apiRequestOrThrow } from '@/lib/api';

export const useMonitoringState = <T>(
    initial: Ref<T>,
    endpoint: Ref<string>,
    shouldPoll: (state: T) => boolean
) => {
    const { t } = useI18n();
    const state = ref(initial.value) as Ref<T>;
    const busy = ref(false);
    const error = ref('');
    let disposed = false;
    let generation = 0;
    let identity = 0;
    let timer: ReturnType<typeof setTimeout> | undefined;
    let controller: AbortController | undefined;
    const schedule = () => {
        clearTimeout(timer);

        if (!disposed && shouldPoll(state.value)) {
            timer = setTimeout(() => void refresh(), 5000);
        }
    };
    const refresh = async () => {
        clearTimeout(timer);
        controller?.abort();
        controller = new AbortController();
        const current = ++generation;

        try {
            const result = await apiRequestOrThrow<T>(endpoint.value, {
                signal: controller.signal,
                retry: { attempts: 0 },
            });

            if (!disposed && current === generation) {
                state.value = result;
            }
        } catch (exception) {
            if (!disposed && current === generation) {
                error.value =
                    exception instanceof ApiError
                        ? exception.message
                        : t('monitoring.error');
            }
        } finally {
            if (!disposed && current === generation) {
                schedule();
            }
        }
    };
    const mutate = async <R>(
        url: string,
        method: 'POST' | 'PATCH' | 'DELETE',
        body?: unknown,
        after?: () => Promise<void>
    ): Promise<R | undefined> => {
        if (busy.value || disposed) {
            return;
        }

        busy.value = true;
        error.value = '';
        const current = identity;

        try {
            const result = await apiRequestOrThrow<R>(url, {
                method,
                body,
                retry: { attempts: 0 },
            });

            if (!disposed && current === identity) {
                await after?.();

                return result;
            }
        } catch (exception) {
            if (!disposed && current === identity) {
                error.value =
                    exception instanceof ApiError
                        ? (Object.values(exception.errors ?? {}).flat()[0] ??
                          exception.message)
                        : t('monitoring.error');
            }
        } finally {
            if (!disposed && current === identity) {
                busy.value = false;
            }
        }
    };
    watch([initial, endpoint], () => {
        generation++;
        identity++;
        busy.value = false;
        controller?.abort();
        state.value = initial.value;
        error.value = '';
        schedule();
    });
    schedule();
    onScopeDispose(() => {
        disposed = true;
        generation++;
        controller?.abort();
        clearTimeout(timer);
    });

    return { state, busy, error, refresh, mutate };
};
