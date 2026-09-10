import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { ApiError, apiRequestOrThrow } from '@/lib/api';
import type { RequestOptions } from '@/lib/api';
import type {
    TrackingAction,
    TrackingForm,
    TrackingList,
    TrackingMessage,
    TrackingTask,
} from './types';

const ENDPOINT = '/telegram/tracking';
const REFRESH_MS = 60_000;

export const useTelegramTracking = () => {
    const { t, locale } = useI18n();
    const list = ref<TrackingList | null>(null);
    const page = ref(1);
    const selected = ref<TrackingTask | null>(null);
    const messages = ref<TrackingMessage[]>([]);
    const messagePage = ref(1);
    const hasMoreMessages = ref(false);
    const busy = ref(false);
    const error = ref('');
    const validatedGroups = ref<string[]>([]);
    const form = ref<TrackingForm>({
        name: '',
        groups: '',
        mode: 'keyword',
        query: '',
        notify_bot: false,
    });
    const controller = new AbortController();
    let timer: ReturnType<typeof setInterval> | undefined;

    const request = <T>(path = '', options: RequestOptions = {}) =>
        apiRequestOrThrow<T>(ENDPOINT + path, {
            ...options,
            query: { ...options.query, locale: locale.value },
            headers: {
                'X-CSRF-TOKEN':
                    document.querySelector<HTMLMetaElement>(
                        'meta[name="csrf-token"]'
                    )?.content ?? '',
            },
            signal: controller.signal,
            retry: { attempts: 0 },
        });

    const perform = async (operation: () => Promise<void>) => {
        if (busy.value) {
            return;
        }

        busy.value = true;
        error.value = '';

        try {
            await operation();
        } catch (exception) {
            if (!controller.signal.aborted) {
                error.value =
                    exception instanceof ApiError
                        ? Object.values(exception.errors ?? {})
                              .flat()
                              .join(' ') || exception.message
                        : t('telegramTracking.error');
            }
        } finally {
            busy.value = false;
        }
    };

    const loadMessages = async (task: TrackingTask, nextPage = 1) => {
        const result = await request<{
            items: TrackingMessage[];
            has_more: boolean;
        }>(`/${task.id}/messages`, { query: { page: nextPage } });
        selected.value = task;
        messages.value = result.items;
        messagePage.value = nextPage;
        hasMoreMessages.value = result.has_more;
    };

    const load = async (nextPage = page.value) => {
        list.value = await request<TrackingList>('', {
            query: { page: nextPage },
        });
        page.value = nextPage;

        if (selected.value) {
            const updated = list.value.items.find(
                (task) => task.id === selected.value?.id
            );

            if (updated) {
                await loadMessages(updated, messagePage.value);
            } else {
                selected.value = null;
                messages.value = [];
            }
        }
    };

    const groups = () =>
        form.value.groups
            .split(/\r?\n/)
            .map((value) => value.trim())
            .filter(Boolean);
    const validateGroups = () =>
        perform(async () => {
            const result = await request<{ groups: { title: string }[] }>(
                '/validate',
                { method: 'POST', body: { groups: groups() } }
            );
            validatedGroups.value = result.groups.map((group) => group.title);
        });
    const create = () =>
        perform(async () => {
            const created = await request<{ id: number }>('', {
                method: 'POST',
                body: { ...form.value, groups: groups() },
            });
            form.value.name = '';
            form.value.groups = '';
            form.value.query = '';
            await load(1);
            const task = list.value?.items.find(
                (item) => item.id === created.id
            );

            if (task) {
                await loadMessages(task);
            }
        });
    const change = (task: TrackingTask, action: TrackingAction) =>
        perform(async () => {
            await request(`/${task.id}`, {
                method: 'PATCH',
                body: { action, notify_bot: !task.notify_bot },
            });
            await load();
        });
    const show = (task: TrackingTask, nextPage = 1) =>
        perform(() => loadMessages(task, nextPage));
    const refresh = (nextPage = page.value) => perform(() => load(nextPage));

    watch(
        () => form.value.groups,
        () => {
            validatedGroups.value = [];
        }
    );
    onMounted(() => {
        void refresh();
        timer = setInterval(() => {
            if (!document.hidden) {
                void refresh();
            }
        }, REFRESH_MS);
    });
    onBeforeUnmount(() => {
        controller.abort();
        clearInterval(timer);
    });

    return {
        list,
        page,
        selected,
        messages,
        messagePage,
        hasMoreMessages,
        busy,
        error,
        form,
        validatedGroups,
        validateGroups,
        create,
        change,
        show,
        refresh,
    };
};
