import { onBeforeUnmount, onMounted, shallowRef } from 'vue';
import { useI18n } from '@/composables/useI18n';
import {
    ApiError,
    apiRequestOrThrow,
    resolveClientErrorMessage,
} from '@/lib/api';
import type { RequestOptions } from '@/lib/api';
import { REPORT_POLL_INTERVAL_MS } from './constants';
import type { ReportFormat, ReportRoutes, ScheduleIdentity } from './types';

type Listing = { timezone: string; reports: { currentPage: number } };
type Options<TList extends Listing> = {
    namespace: string;
    routes: ReportRoutes;
    onLoaded?: (result: TList, firstLoad: boolean) => void;
};

export const useScheduledReports = <
    TList extends Listing,
    TSchedule extends ScheduleIdentity,
>(
    configuration: Options<TList>
) => {
    const { t, locale } = useI18n();
    const list = shallowRef<TList | null>(null);
    const page = shallowRef(1);
    const busy = shallowRef(false);
    const error = shallowRef('');
    const notice = shallowRef('');
    const controller = new AbortController();
    let disposed = false;
    let timer: ReturnType<typeof setInterval> | undefined;
    const isActive = () => !disposed;
    const request = <T>(url: string, options: RequestOptions = {}) =>
        apiRequestOrThrow<T>(url, {
            ...options,
            query: { ...options.query, locale: locale.value },
            signal: controller.signal,
            retry: { attempts: 0 },
        });
    const perform = async <T>(operation: () => Promise<T>) => {
        if (busy.value || disposed) {
            return;
        }

        busy.value = true;
        error.value = '';

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
                        t(`${configuration.namespace}.error`)
                    );
            }
        } finally {
            if (!disposed) {
                busy.value = false;
            }
        }
    };
    const load = async (nextPage = page.value) => {
        if (disposed) {
            return;
        }

        const result = await request<TList>(configuration.routes.index.url(), {
            query: { page: nextPage },
        });

        if (disposed) {
            return;
        }

        configuration.onLoaded?.(result, list.value === null);
        list.value = result;
        page.value = result.reports.currentPage;
    };
    const create = (valid: boolean, body: unknown, onCreated: () => void) =>
        perform(async () => {
            if (!valid) {
                return false;
            }

            await request(configuration.routes.store.url(), {
                method: 'POST',
                body,
            });

            if (disposed) {
                return false;
            }

            onCreated();
            notice.value = t(`${configuration.namespace}.created`);
            await load(1);

            return true;
        });
    const change = (schedule: TSchedule) =>
        perform(async () => {
            await request(configuration.routes.change.url(schedule.id), {
                method: 'PATCH',
                body: { action: schedule.enabled ? 'pause' : 'resume' },
            });
            await load();
        });
    const run = (schedule: TSchedule) =>
        perform(async () => {
            await request(configuration.routes.runNow.url(schedule.id), {
                method: 'POST',
            });

            if (disposed) {
                return;
            }

            notice.value = t(`${configuration.namespace}.queued`);
            await load(1);
        });
    const remove = (schedule: TSchedule) =>
        perform(async () => {
            await request(configuration.routes.destroy.url(schedule.id), {
                method: 'DELETE',
            });

            if (disposed) {
                return;
            }

            notice.value = t(`${configuration.namespace}.deleted`);
            await load();
        });
    const refresh = (nextPage = page.value) => perform(() => load(nextPage));
    const reportViewUrl = (id: number) =>
        configuration.routes.view.url(id, { query: { locale: locale.value } });
    const reportDownloadUrl = (id: number, format: ReportFormat) =>
        configuration.routes.download.url(
            { report: id, format },
            { query: { locale: locale.value } }
        );
    // Modules with additional resources (for example search filters) supply their
    // refresh operation without moving those domain resources into this core.
    const startPolling = (refreshReports: () => unknown = refresh) => {
        onMounted(() => {
            void refreshReports();
            timer = setInterval(() => {
                if (!disposed && !document.hidden) {
                    void refreshReports();
                }
            }, REPORT_POLL_INTERVAL_MS);
        });
    };
    onBeforeUnmount(() => {
        disposed = true;
        controller.abort();
        clearInterval(timer);
    });

    return {
        list,
        page,
        busy,
        error,
        notice,
        request,
        perform,
        load,
        create,
        change,
        run,
        remove,
        refresh,
        reportViewUrl,
        reportDownloadUrl,
        isActive,
        startPolling,
    };
};
