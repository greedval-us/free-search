import { computed, onBeforeUnmount, onMounted, ref, shallowRef } from 'vue';
import {
    change as changeSchedule,
    destroy,
    download,
    index,
    runNow,
    store,
    view,
} from '@/actions/App/Http/Controllers/SiteIntel/SiteIntelReportsController';
import { useI18n } from '@/composables/useI18n';
import {
    ApiError,
    apiRequestOrThrow,
    resolveClientErrorMessage,
} from '@/lib/api';
import type { RequestOptions } from '@/lib/api';
import { normalizeReportTarget } from './target-input';
import type { ReportSchedule, ReportScheduleForm, ReportsList } from './types';

const REFRESH_MS = 60_000;

export const useSiteIntelReports = () => {
    const { t, locale } = useI18n();
    const list = ref<ReportsList | null>(null);
    const page = shallowRef(1);
    const busy = shallowRef(false);
    const error = shallowRef('');
    const notice = shallowRef('');
    const form = ref<ReportScheduleForm>({
        name: '',
        targets: '',
        reportType: 'analytics',
        crawlLimit: 8,
        platformType: 'auto',
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
                        t('siteIntelReports.error')
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

        if (!result.availableReportTypes.includes(form.value.reportType)) {
            form.value.reportType =
                result.availableReportTypes[0] ?? 'analytics';
        }

        list.value = result;
        page.value = result.reports.currentPage;
    };

    const targets = computed(() =>
        form.value.targets
            .split(/\r\n?|\n/)
            .map((value) => value.trim())
            .filter(Boolean)
            .map((value) => normalizeReportTarget(value) ?? value)
    );
    const targetsError = computed(() => {
        if (targets.value.some((value) => /\s/u.test(value))) {
            return t('siteIntelReports.targetsOnePerLine');
        }

        if (new Set(targets.value).size !== targets.value.length) {
            return t('siteIntelReports.duplicateTargets');
        }

        if (targets.value.some((target) => !normalizeReportTarget(target))) {
            return t('siteIntelReports.invalidTargets');
        }

        if (list.value && targets.value.length > list.value.maxTargets) {
            return t('siteIntelReports.tooManyTargets', {
                max: list.value.maxTargets,
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
            targets.value.length > 0 &&
            !targetsError.value &&
            /^(?:[01]\d|2[0-3]):[0-5]\d$/.test(form.value.sendTime) &&
            form.value.timezone.trim().length > 0 &&
            Boolean(
                list.value?.availableReportTypes.includes(form.value.reportType)
            ) &&
            (form.value.reportType !== 'seo-audit' ||
                (Number.isInteger(form.value.crawlLimit) &&
                    form.value.crawlLimit >= 3 &&
                    form.value.crawlLimit <= 20 &&
                    [
                        'auto',
                        'generic',
                        'media-platform',
                        'content-site',
                        'storefront',
                    ].includes(form.value.platformType)))
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
                    targets: targets.value,
                    crawlLimit:
                        form.value.reportType === 'seo-audit'
                            ? form.value.crawlLimit
                            : 8,
                    platformType:
                        form.value.reportType === 'seo-audit'
                            ? form.value.platformType
                            : 'auto',
                },
            });
            form.value.name = '';
            form.value.targets = '';
            notice.value = t('siteIntelReports.created');
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
            notice.value = t('siteIntelReports.queued');
            await load(1);
        });
    const remove = (schedule: ReportSchedule) =>
        perform(async () => {
            await request(destroy.url(schedule.id), { method: 'DELETE' });
            notice.value = t('siteIntelReports.deleted');
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
        targetsError,
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
