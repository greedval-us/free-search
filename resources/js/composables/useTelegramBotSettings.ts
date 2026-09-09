import { router } from '@inertiajs/vue3';
import { onMounted, onScopeDispose, ref } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { ApiError, apiRequestOrThrow } from '@/lib/api';
import type {
    TelegramBotPreferences,
    TelegramBotState,
} from '@/types/telegramBot';

const LINK_POLL_INTERVAL_MS = 5000;

export const useTelegramBotSettings = (initial: TelegramBotState) => {
    const { t } = useI18n();
    const state = ref<TelegramBotState>(initial);
    const linkUrl = ref('');
    const busy = ref(false);
    const error = ref('');
    let disposed = false;
    let version = 0;
    let controller: AbortController | null = null;
    let timer: ReturnType<typeof setTimeout> | undefined;

    const schedulePoll = () => {
        clearTimeout(timer);

        if (
            !disposed &&
            state.value.available &&
            state.value.pending &&
            !state.value.pending.telegram_id &&
            !state.value.link
        ) {
            timer = setTimeout(() => void run('status'), LINK_POLL_INTERVAL_MS);
        }
    };

    const run = async (
        action: keyof TelegramBotState['routes'],
        body?: unknown
    ) => {
        clearTimeout(timer);
        controller?.abort();
        controller = new AbortController();
        const current = ++version;
        busy.value = action !== 'status';
        error.value = '';

        try {
            const result = await apiRequestOrThrow<
                TelegramBotState & { url?: string }
            >(state.value.routes[action], {
                method:
                    action === 'status'
                        ? 'GET'
                        : action === 'disconnect'
                          ? 'DELETE'
                          : action === 'preferences'
                            ? 'PATCH'
                            : 'POST',
                body,
                headers: {
                    'X-CSRF-TOKEN':
                        document.querySelector<HTMLMetaElement>(
                            'meta[name="csrf-token"]'
                        )?.content ?? '',
                },
                signal: controller.signal,
                retry: { attempts: 0 },
            });

            if (disposed || current !== version) {
                return;
            }

            const { url, ...next } = result;
            state.value = next;

            if (url) {
                linkUrl.value = url;
            }

            if (!next.pending) {
                linkUrl.value = '';
            }

            if (action === 'confirm' || action === 'disconnect') {
                router.reload({ only: ['auth'] });
            }
        } catch (exception) {
            if (disposed || current !== version) {
                return;
            }

            error.value =
                exception instanceof ApiError
                    ? (exception.errors?.telegram?.[0] ??
                      t('telegramBot.error'))
                    : t('telegramBot.error');
        } finally {
            if (!disposed && current === version) {
                busy.value = false;
                schedulePoll();
            }
        }
    };

    onMounted(schedulePoll);
    onScopeDispose(() => {
        disposed = true;
        version++;
        clearTimeout(timer);
        controller?.abort();
        linkUrl.value = '';
    });

    return {
        state,
        linkUrl,
        busy,
        error,
        refresh: () => run('status'),
        issue: () => run('issue'),
        confirm: () => run('confirm', { request_id: state.value.pending?.id }),
        disconnect: () => run('disconnect'),
        save: (preferences: TelegramBotPreferences) =>
            run('preferences', preferences),
    };
};
