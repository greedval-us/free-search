import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type * as Vue from 'vue';
import { ApiError } from '@/lib/api';
import type * as Api from '@/lib/api';
import type { ReportSchedule, ReportsList } from './types';
import { useSiteIntelReports } from './useSiteIntelReports';

const hooks = vi.hoisted(() => ({
    mounted: [] as (() => void)[],
    unmounted: [] as (() => void)[],
    request: vi.fn(),
}));
const browserPage = { hidden: false };
vi.mock('vue', async (original) => ({
    ...(await original<typeof Vue>()),
    onMounted: (callback: () => void) => hooks.mounted.push(callback),
    onBeforeUnmount: (callback: () => void) => hooks.unmounted.push(callback),
}));
vi.mock('@/composables/useI18n', () => ({
    useI18n: () => ({ t: (key: string) => key, locale: { value: 'ru' } }),
}));
vi.mock('@/lib/api', async (original) => ({
    ...(await original<typeof Api>()),
    apiRequestOrThrow: hooks.request,
}));

const schedule: ReportSchedule = {
    id: 4,
    name: 'Websites',
    targets: ['https://example.com/'],
    reportType: 'analytics',
    crawlLimit: 8,
    platformType: 'auto',
    interval: '7',
    sendTime: '09:00',
    timezone: 'Europe/Moscow',
    sendToBot: false,
    enabled: true,
    nextRunAt: '2026-10-16T06:00:00Z',
    createdAt: '2026-10-09T06:00:00Z',
};
const listing: ReportsList = {
    schedules: [schedule],
    reports: { data: [], currentPage: 1, lastPage: 1, total: 0, perPage: 20 },
    botLinked: true,
    botExportsEnabled: true,
    maxTargets: 3,
    maxSchedules: 5,
    timezone: 'Europe/Moscow',
    availableReportTypes: ['analytics', 'seo-audit'],
};

describe('scheduled site reports', () => {
    beforeEach(() => {
        hooks.request.mockReset();
        hooks.mounted.length = 0;
        hooks.unmounted.length = 0;
        vi.useFakeTimers();
        browserPage.hidden = false;
        vi.stubGlobal('document', browserPage);
    });
    afterEach(() => {
        hooks.unmounted.forEach((callback) => callback());
        vi.useRealTimers();
        vi.unstubAllGlobals();
    });

    it('normalizes multiple sites and submits SEO options with monthly cadence', async () => {
        hooks.request.mockResolvedValue(listing);
        const state = useSiteIntelReports();
        await state.refresh();
        Object.assign(state.form.value, {
            name: ' SEO checks ',
            targets:
                ' Example.COM \r\nhttps://another.com/Catalog?Product=ABC#section\n',
            reportType: 'seo-audit',
            crawlLimit: 12,
            platformType: 'storefront',
            interval: 'month',
            sendToBot: true,
        });
        expect(await state.create()).toBe(true);
        expect(hooks.request).toHaveBeenCalledWith(
            '/site-intel/reports/schedules',
            expect.objectContaining({
                method: 'POST',
                body: expect.objectContaining({
                    name: 'SEO checks',
                    targets: [
                        'https://example.com/',
                        'https://another.com/Catalog?Product=ABC',
                    ],
                    reportType: 'seo-audit',
                    crawlLimit: 12,
                    platformType: 'storefront',
                    interval: 'month',
                    sendToBot: true,
                }),
                query: { locale: 'ru' },
                retry: { attempts: 0 },
            })
        );
        expect(state.form.value.name).toBe('');
        expect(state.form.value.targets).toBe('');
    });

    it('uses the available type for SEO-only users and handles permission changes', async () => {
        hooks.request
            .mockResolvedValueOnce({
                ...listing,
                availableReportTypes: ['seo-audit'],
            })
            .mockResolvedValueOnce({
                ...listing,
                availableReportTypes: ['analytics'],
            });
        const state = useSiteIntelReports();
        await state.refresh();
        expect(state.form.value.reportType).toBe('seo-audit');
        state.form.value.name = 'Website';
        state.form.value.targets = 'example.com';
        expect(state.canCreate.value).toBe(true);
        await state.refresh();
        expect(state.form.value.reportType).toBe('analytics');
        state.form.value.reportType = 'seo-audit';
        expect(state.canCreate.value).toBe(false);
    });

    it('blocks duplicates, invalid URLs, schedule limits, and invalid SEO crawl limits', async () => {
        hooks.request.mockResolvedValue(listing);
        const state = useSiteIntelReports();
        await state.refresh();
        state.form.value.name = 'Website';
        state.form.value.targets = 'Example.com\nhttps://example.com/';
        expect(state.targetsError.value).toBe(
            'siteIntelReports.duplicateTargets'
        );
        state.form.value.targets = 'https://user@example.com/';
        expect(state.targetsError.value).toBe(
            'siteIntelReports.invalidTargets'
        );
        state.form.value.targets = 'example.com other.com';
        expect(state.targetsError.value).toBe(
            'siteIntelReports.targetsOnePerLine'
        );
        state.form.value.targets = 'one.com\ntwo.com\nthree.com\nfour.com';
        expect(state.targetsError.value).toBe(
            'siteIntelReports.tooManyTargets'
        );
        state.form.value.targets = 'example.com';
        state.form.value.reportType = 'seo-audit';

        for (const limit of [2, 21, 3.5, Number.NaN]) {
            state.form.value.crawlLimit = limit;
            expect(state.canCreate.value).toBe(false);
        }

        expect(await state.create()).toBe(false);
        hooks.request.mockResolvedValue({ ...listing, maxSchedules: 1 });
        await state.refresh();
        state.form.value.crawlLimit = 8;
        expect(state.canCreate.value).toBe(false);
    });

    it('clears irrelevant SEO options when creating an analytics schedule', async () => {
        hooks.request.mockResolvedValue(listing);
        const state = useSiteIntelReports();
        await state.refresh();
        Object.assign(state.form.value, {
            name: 'Analytics',
            targets: 'example.com',
            crawlLimit: 20,
            platformType: 'storefront',
        });
        expect(await state.create()).toBe(true);
        expect(hooks.request).toHaveBeenCalledWith(
            '/site-intel/reports/schedules',
            expect.objectContaining({
                body: expect.objectContaining({
                    reportType: 'analytics',
                    crawlLimit: 8,
                    platformType: 'auto',
                }),
            })
        );
    });

    it('retains the form on validation errors and preserves loaded history on failures', async () => {
        hooks.request
            .mockResolvedValueOnce(listing)
            .mockRejectedValueOnce(
                new ApiError({
                    ok: false,
                    status: 422,
                    message: 'Invalid schedule',
                    errors: { targets: ['Public websites only.'] },
                })
            )
            .mockRejectedValueOnce(
                new ApiError({ ok: false, message: 'SQLSTATE database error' })
            );
        const state = useSiteIntelReports();
        await state.refresh();
        state.form.value.name = 'Website';
        state.form.value.targets = 'example.com';
        await state.create();
        expect(state.error.value).toBe('Public websites only.');
        expect(state.form.value.targets).toBe('example.com');
        await state.refresh();
        expect(state.error.value).toBe('siteIntelReports.error');
        expect(state.list.value?.schedules).toEqual([schedule]);
    });

    it('keeps the selected timezone and valid report type when paging history', async () => {
        hooks.request.mockResolvedValueOnce(listing).mockResolvedValueOnce({
            ...listing,
            reports: { ...listing.reports, currentPage: 2, lastPage: 3 },
        });
        const state = useSiteIntelReports();
        await state.refresh();
        state.form.value.timezone = 'UTC';
        state.form.value.reportType = 'seo-audit';
        await state.refresh(2);
        expect(state.form.value.timezone).toBe('UTC');
        expect(state.form.value.reportType).toBe('seo-audit');
        expect(state.page.value).toBe(2);
        expect(hooks.request).toHaveBeenLastCalledWith(
            '/site-intel/reports',
            expect.objectContaining({ query: { page: 2, locale: 'ru' } })
        );
    });

    it('serializes mutations, sends explicit pause/resume actions, and creates localized links', async () => {
        let finish!: (value: ReportsList) => void;
        hooks.request
            .mockReturnValueOnce(
                new Promise<ReportsList>((resolve) => {
                    finish = resolve;
                })
            )
            .mockResolvedValue(listing);
        const state = useSiteIntelReports();
        const pending = state.run(schedule);
        await state.run(schedule);
        expect(hooks.request).toHaveBeenCalledTimes(1);
        finish(listing);
        await pending;
        await state.change(schedule);
        expect(hooks.request).toHaveBeenCalledWith(
            '/site-intel/reports/schedules/4',
            expect.objectContaining({
                method: 'PATCH',
                body: { action: 'pause' },
            })
        );
        await state.change({ ...schedule, enabled: false });
        expect(hooks.request).toHaveBeenCalledWith(
            '/site-intel/reports/schedules/4',
            expect.objectContaining({ body: { action: 'resume' } })
        );
        expect(state.reportViewUrl(23)).toBe(
            '/site-intel/reports/23/view?locale=ru'
        );
        expect(state.reportDownloadUrl(23, 'html')).toBe(
            '/site-intel/reports/23/download/html?locale=ru'
        );
    });

    it('polls visible pages and aborts in-flight requests and timers when unmounted', async () => {
        hooks.request.mockResolvedValue(listing);
        useSiteIntelReports();
        hooks.mounted.forEach((callback) => callback());
        await vi.advanceTimersByTimeAsync(0);
        expect(hooks.request).toHaveBeenCalledTimes(1);
        browserPage.hidden = true;
        await vi.advanceTimersByTimeAsync(60_000);
        expect(hooks.request).toHaveBeenCalledTimes(1);
        browserPage.hidden = false;
        await vi.advanceTimersByTimeAsync(60_000);
        expect(hooks.request).toHaveBeenCalledTimes(2);
        const signal = hooks.request.mock.calls[0][1].signal as AbortSignal;
        hooks.unmounted.forEach((callback) => callback());
        expect(signal.aborted).toBe(true);
        await vi.advanceTimersByTimeAsync(60_000);
        expect(hooks.request).toHaveBeenCalledTimes(2);
    });
});
