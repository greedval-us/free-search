import { computed, onBeforeUnmount, onMounted, ref, shallowRef } from 'vue';
import {
    change as changeSchedule,
    destroy,
    download,
    index,
    runNow,
    store,
    view,
} from '@/actions/App/Http/Controllers/Mastodon/MastodonAnalyticsReportsController';
import { useI18n } from '@/composables/useI18n';
import {
    ApiError,
    apiRequestOrThrow,
    resolveClientErrorMessage,
} from '@/lib/api';
import type { RequestOptions } from '@/lib/api';
import { isReportAccount, normalizeReportAccount } from './account-input';
import type { ReportSchedule, ReportScheduleForm, ReportsList } from './types';

const REFRESH_MS = 60_000;

export const useMastodonReports = () => {
    const { t, locale } = useI18n();
    const list = ref<ReportsList | null>(null);
    const page = shallowRef(1);
    const busy = shallowRef(false);
    const error = shallowRef('');
    const notice = shallowRef('');
    const form = ref<ReportScheduleForm>({
        name: '',
        accounts: '',
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
                        t('mastodonReports.error')
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

    const accounts = computed(() =>
        form.value.accounts
            .split(/\r\n?|\n/)
            .map((value) => value.trim())
            .filter(Boolean)
            .map(normalizeReportAccount)
    );
    const accountsError = computed(() => {
        if (accounts.value.some((value) => /\s/u.test(value))) {
            return t('mastodonReports.accountsOnePerLine');
        }

        if (new Set(accounts.value).size !== accounts.value.length) {
            return t('mastodonReports.duplicateAccounts');
        }

        if (accounts.value.some((account) => !isReportAccount(account))) {
            return t('mastodonReports.invalidAccounts');
        }

        if (list.value && accounts.value.length > list.value.maxAccounts) {
            return t('mastodonReports.tooManyAccounts', {
                max: list.value.maxAccounts,
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
            accounts.value.length > 0 &&
            !accountsError.value &&
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
                    accounts: accounts.value,
                },
            });
            form.value.name = '';
            form.value.accounts = '';
            notice.value = t('mastodonReports.created');
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
            notice.value = t('mastodonReports.queued');
            await load(1);
        });
    const remove = (schedule: ReportSchedule) =>
        perform(async () => {
            await request(destroy.url(schedule.id), { method: 'DELETE' });
            notice.value = t('mastodonReports.deleted');
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
        accountsError,
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
