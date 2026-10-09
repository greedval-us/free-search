import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import {
    change as changeSchedule,
    destroy,
    download,
    index,
    runNow,
    store,
    view,
} from '@/actions/App/Http/Controllers/YouTube/YouTubeAnalyticsReportsController';
import { useI18n } from '@/composables/useI18n';
import {
    ApiError,
    apiRequestOrThrow,
    resolveClientErrorMessage,
} from '@/lib/api';
import type { RequestOptions } from '@/lib/api';
import {
    CHANNEL_HANDLE_PATTERN,
    CHANNEL_ID_PATTERN,
    normalizeReportChannel,
} from './channel-input';
import type { ReportSchedule, ReportScheduleForm, ReportsList } from './types';

const REFRESH_MS = 60_000;

export const useYouTubeReports = () => {
    const { t, locale } = useI18n();
    const list = ref<ReportsList | null>(null);
    const page = ref(1);
    const busy = ref(false);
    const error = ref('');
    const notice = ref('');
    const form = ref<ReportScheduleForm>({
        name: '',
        channels: '',
        interval: '7',
        sendTime: '09:00',
        timezone: '',
        sendToBot: false,
    });
    const controller = new AbortController();
    let timer: ReturnType<typeof setInterval> | undefined;

    const request = <T>(url: string, options: RequestOptions = {}) =>
        apiRequestOrThrow<T>(url, {
            ...options,
            query: { ...options.query, locale: locale.value },
            signal: controller.signal,
            retry: { attempts: 0 },
        });

    const perform = async <T>(operation: () => Promise<T>) => {
        if (busy.value) {
            return;
        }

        busy.value = true;
        error.value = '';

        try {
            return await operation();
        } catch (exception) {
            if (!controller.signal.aborted) {
                const validationErrors =
                    exception instanceof ApiError && exception.status === 422
                        ? Object.values(exception.errors ?? {})
                              .flat()
                              .join(' ')
                        : '';
                error.value =
                    validationErrors ||
                    resolveClientErrorMessage(
                        exception,
                        t('youtubeReports.error')
                    );
            }
        } finally {
            busy.value = false;
        }
    };

    const load = async (nextPage = page.value) => {
        const result = await request<ReportsList>(index.url(), {
            query: { page: nextPage },
        });

        if (!list.value) {
            form.value.timezone = result.timezone;
        }

        list.value = result;
        page.value = result.reports.currentPage;
    };

    const channels = computed(() =>
        form.value.channels
            .split(/\r\n?|\n/)
            .map((value) => value.trim())
            .filter(Boolean)
            .map(normalizeReportChannel)
    );
    const channelsError = computed(() => {
        if (channels.value.some((value) => /\s/u.test(value))) {
            return t('youtubeReports.channelsOnePerLine');
        }

        if (new Set(channels.value).size !== channels.value.length) {
            return t('youtubeReports.duplicateChannels');
        }

        if (
            channels.value.some(
                (channel) =>
                    !CHANNEL_ID_PATTERN.test(channel) &&
                    !CHANNEL_HANDLE_PATTERN.test(channel)
            )
        ) {
            return t('youtubeReports.invalidChannels');
        }

        if (list.value && channels.value.length > list.value.maxChannels) {
            return t('youtubeReports.tooManyChannels', {
                max: list.value.maxChannels,
            });
        }

        return '';
    });
    const canCreate = computed(
        () =>
            Boolean(
                list.value &&
                list.value.schedules.length < list.value.maxSchedules
            ) &&
            form.value.name.trim().length > 0 &&
            channels.value.length > 0 &&
            !channelsError.value &&
            /^(?:[01]\d|2[0-3]):[0-5]\d$/.test(form.value.sendTime) &&
            form.value.timezone.trim().length > 0
    );

    const create = () =>
        perform(async () => {
            if (!canCreate.value) {
                return false;
            }

            await request(store.url(), {
                method: 'POST',
                body: {
                    ...form.value,
                    name: form.value.name.trim(),
                    timezone: form.value.timezone.trim(),
                    channels: channels.value,
                },
            });
            form.value.name = '';
            form.value.channels = '';
            notice.value = t('youtubeReports.created');
            await load(1);

            return true;
        });
    const change = (schedule: ReportSchedule) =>
        perform(async () => {
            await request(changeSchedule.url(schedule.id), {
                method: 'PATCH',
                body: { action: schedule.enabled ? 'pause' : 'resume' },
            });
            await load();
        });
    const run = (schedule: ReportSchedule) =>
        perform(async () => {
            await request(runNow.url(schedule.id), { method: 'POST' });
            notice.value = t('youtubeReports.queued');
            await load(1);
        });
    const remove = (schedule: ReportSchedule) =>
        perform(async () => {
            await request(destroy.url(schedule.id), { method: 'DELETE' });
            notice.value = t('youtubeReports.deleted');
            await load();
        });
    const refresh = (nextPage = page.value) => perform(() => load(nextPage));
    const reportViewUrl = (id: number) =>
        view.url(id, { query: { locale: locale.value } });
    const reportDownloadUrl = (id: number, format: 'html' | 'json') =>
        download.url(
            { report: id, format },
            { query: { locale: locale.value } }
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
        busy,
        error,
        notice,
        form,
        channelsError,
        canCreate,
        create,
        change,
        run,
        remove,
        refresh,
        reportViewUrl,
        reportDownloadUrl,
    };
};
