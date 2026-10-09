import { computed, onBeforeUnmount, onMounted, ref, shallowRef } from 'vue';
import {
    change as changeSchedule,
    destroy,
    download,
    index,
    runNow,
    store,
    view,
} from '@/actions/App/Http/Controllers/NewsMediaIntel/NewsMediaReportsController';
import { useI18n } from '@/composables/useI18n';
import {
    ApiError,
    apiRequestOrThrow,
    resolveClientErrorMessage,
} from '@/lib/api';
import type { RequestOptions } from '@/lib/api';
import { options as searchOptions } from '@/routes/news-media-intel';
import {
    competitorNames,
    initialNewsFilters,
    normalizeVisibilityDomain,
} from '../form';
import type { NewsOptions } from '../types';
import { reportFormError, reportQueries } from './form';
import type { ReportSchedule, ReportScheduleForm, ReportsList } from './types';

export const useNewsReports = () => {
    const { t, locale } = useI18n();
    const list = shallowRef<ReportsList | null>(null);
    const options = shallowRef<NewsOptions | null>(null);
    const optionsError = shallowRef('');
    const page = shallowRef(1);
    const busy = shallowRef(false);
    const error = shallowRef('');
    const notice = shallowRef('');
    const form = ref<ReportScheduleForm>({
        name: '',
        queries: '',
        brand: '',
        competitors: '',
        domain: '',
        filters: { ...initialNewsFilters(), categories: ['news', 'general'] },
        interval: '7',
        sendTime: '09:00',
        timezone: '',
        sendToBot: false,
    });
    const controller = new AbortController();
    let disposed = false;
    let timer: ReturnType<typeof setInterval> | undefined;

    const request = <T>(url: string, requestOptions: RequestOptions = {}) =>
        apiRequestOrThrow<T>(url, {
            ...requestOptions,
            query: { ...requestOptions.query, locale: locale.value },
            signal: controller.signal,
            retry: { attempts: 0 },
        });
    const perform = async <T>(operation: () => Promise<T>) => {
        if (busy.value || disposed) {
            return;
        }

        busy.value = true;
        error.value = '';
        notice.value = '';

        try {
            return await operation();
        } catch (exception) {
            if (!disposed) {
                const fields =
                    exception instanceof ApiError && exception.status === 422
                        ? Object.values(exception.errors ?? {})
                              .flat()
                              .join(' ')
                        : '';
                error.value =
                    fields ||
                    resolveClientErrorMessage(
                        exception,
                        t('newsMediaReports.error')
                    );
            }
        } finally {
            if (!disposed) {
                busy.value = false;
            }
        }
    };
    const loadOptions = async () => {
        optionsError.value = '';

        try {
            const result = await request<NewsOptions>(searchOptions.url());

            if (!disposed) {
                if (!options.value) {
                    Object.assign(form.value.filters, result.defaults);
                }

                options.value = result;
            }
        } catch (exception) {
            if (!disposed) {
                optionsError.value = resolveClientErrorMessage(
                    exception,
                    t('newsMediaReports.optionsError')
                );
            }
        }
    };
    const load = async (nextPage = page.value) => {
        if (disposed) {
            return;
        }

        const result = await request<ReportsList>(index.url(), {
            query: { page: nextPage },
        });

        if (disposed) {
            return;
        }

        if (!list.value) {
            form.value.timezone = result.timezone;
        }

        list.value = result;
        page.value = result.reports.currentPage;
    };
    const queries = computed(() => reportQueries(form.value.queries));
    const validationError = computed(() => {
        const code = reportFormError(form.value, list.value?.maxQueries ?? 3);

        if (!code) {
            return '';
        }

        const namespace = [
            'competitorLimit',
            'duplicateCompetitors',
            'invalidDomain',
        ].includes(code)
            ? 'newsMediaIntel.errors'
            : 'newsMediaReports';

        return t(`${namespace}.${code}`, { max: list.value?.maxQueries ?? 3 });
    });
    const canCreate = computed(() =>
        Boolean(
            list.value &&
            options.value &&
            list.value.schedules.length < list.value.maxSchedules &&
            form.value.name.trim().length > 0 &&
            form.value.name.trim().length <= 100 &&
            queries.value.length &&
            !validationError.value &&
            ['1', '3', '7', 'month'].includes(form.value.interval) &&
            /^(?:[01]\d|2[0-3]):[0-5]\d$/u.test(form.value.sendTime) &&
            form.value.timezone.trim()
        )
    );
    const create = () =>
        perform(async () => {
            if (!canCreate.value) {
                return false;
            }

            await request(store.url(), {
                method: 'POST',
                body: {
                    name: form.value.name.trim(),
                    queries: queries.value,
                    brand: form.value.brand.trim() || null,
                    competitors: competitorNames(form.value.competitors),
                    domain: normalizeVisibilityDomain(form.value.domain),
                    language: form.value.filters.language,
                    timeRange: form.value.filters.timeRange,
                    safeSearch: form.value.filters.safeSearch,
                    engines: [...form.value.filters.engines],
                    maxPages: form.value.filters.maxPages,
                    interval: form.value.interval,
                    sendTime: form.value.sendTime,
                    timezone: form.value.timezone.trim(),
                    sendToBot: form.value.sendToBot,
                },
            });

            if (disposed) {
                return false;
            }

            form.value.name = '';
            form.value.queries = '';
            notice.value = t('newsMediaReports.created');
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

            if (disposed) {
                return;
            }

            notice.value = t('newsMediaReports.queued');
            await load(1);
        });
    const remove = (schedule: ReportSchedule) =>
        perform(async () => {
            await request(destroy.url(schedule.id), { method: 'DELETE' });

            if (disposed) {
                return;
            }

            notice.value = t('newsMediaReports.deleted');
            await load();
        });
    const refresh = (nextPage = page.value) =>
        perform(async () => {
            await Promise.all([
                load(nextPage),
                options.value ? Promise.resolve() : loadOptions(),
            ]);
        });
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
        }, 60_000);
    });
    onBeforeUnmount(() => {
        disposed = true;
        controller.abort();
        clearInterval(timer);
    });

    return {
        list,
        options,
        optionsError,
        page,
        busy,
        error,
        notice,
        form,
        queries,
        validationError,
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
