import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type * as Vue from 'vue';
import type * as Api from '@/lib/api';
import type { ReportRoutes } from './types';
import { useScheduledReports } from './useScheduledReports';

const hooks = vi.hoisted(() => ({
    mounted: [] as (() => void)[],
    unmounted: [] as (() => void)[],
    request: vi.fn(),
}));
vi.mock('vue', async (original) => ({
    ...(await original<typeof Vue>()),
    onMounted: (callback: () => void) => hooks.mounted.push(callback),
    onBeforeUnmount: (callback: () => void) => hooks.unmounted.push(callback),
}));
vi.mock('@/composables/useI18n', () => ({
    useI18n: () => ({ t: (key: string) => key, locale: { value: 'en' } }),
}));
vi.mock('@/lib/api', async (original) => ({
    ...(await original<typeof Api>()),
    apiRequestOrThrow: hooks.request,
}));

const routes: ReportRoutes = {
    index: { url: () => '/reports' },
    store: { url: () => '/reports/schedules' },
    change: { url: (id) => `/reports/schedules/${id}` },
    runNow: { url: (id) => `/reports/schedules/${id}/run` },
    destroy: { url: (id) => `/reports/schedules/${id}` },
    view: { url: (id) => `/reports/${id}/view` },
    download: {
        url: ({ report, format }) => `/reports/${report}/download/${format}`,
    },
};
const listing = { timezone: 'Europe/Moscow', reports: { currentPage: 1 } };

describe('shared scheduled report lifecycle', () => {
    beforeEach(() => {
        hooks.request.mockReset();
        hooks.mounted.length = 0;
        hooks.unmounted.length = 0;
    });
    afterEach(() => {
        hooks.unmounted.forEach((callback) => callback());
    });

    it('ignores late successful loads and stops all operations after unmount', async () => {
        let finish!: (result: typeof listing) => void;
        const onLoaded = vi.fn();
        hooks.request.mockReturnValue(
            new Promise<typeof listing>((resolve) => {
                finish = resolve;
            })
        );
        const state = useScheduledReports({
            namespace: 'reports',
            routes,
            onLoaded,
        });
        const pending = state.refresh();
        const signal = hooks.request.mock.calls[0][1].signal as AbortSignal;
        hooks.unmounted.forEach((callback) => callback());
        finish(listing);
        await pending;
        expect(signal.aborted).toBe(true);
        expect(state.list.value).toBeNull();
        expect(onLoaded).not.toHaveBeenCalled();
        await state.run({ id: 1, enabled: true });
        await state.refresh();
        expect(hooks.request).toHaveBeenCalledTimes(1);
        expect(state.error.value).toBe('');
    });

    it('does not reset a form or announce creation when its request finishes after disposal', async () => {
        let finish!: () => void;
        hooks.request.mockReturnValue(
            new Promise<void>((resolve) => {
                finish = resolve;
            })
        );
        const state = useScheduledReports({ namespace: 'reports', routes });
        const reset = vi.fn();
        const pending = state.create(true, { name: 'Monitor' }, reset);
        hooks.unmounted.forEach((callback) => callback());
        finish();
        await pending;
        expect(reset).not.toHaveBeenCalled();
        expect(state.notice.value).toBe('');
        expect(hooks.request).toHaveBeenCalledTimes(1);
    });

    it('preserves a successful operation notice when the list is refreshed', async () => {
        hooks.request.mockResolvedValue(listing);
        const state = useScheduledReports({ namespace: 'reports', routes });
        await state.run({ id: 1, enabled: true });
        expect(state.notice.value).toBe('reports.queued');
        await state.refresh();
        expect(state.notice.value).toBe('reports.queued');
        expect(state.list.value).toEqual(listing);
    });
});
