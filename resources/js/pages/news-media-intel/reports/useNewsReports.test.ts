import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type * as Vue from 'vue';
import { ApiError } from '@/lib/api';
import type * as Api from '@/lib/api';
import type { NewsOptions } from '../types';
import type { ReportSchedule, ReportsList } from './types';
import { useNewsReports } from './useNewsReports';

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
    useI18n: () => ({ t: (key: string) => key, locale: { value: 'ru' } }),
}));
vi.mock('@/lib/api', async (original) => ({
    ...(await original<typeof Api>()),
    apiRequestOrThrow: hooks.request,
}));

const options: NewsOptions = {
    categories: ['news', 'general'],
    languages: ['ru', 'en'],
    engines: { news: ['bing news'], general: ['bing'] },
    maxPages: 3,
    defaults: {
        language: 'ru',
        timeRange: 'month',
        safeSearch: 1,
        maxPages: 3,
    },
};
const schedule: ReportSchedule = {
    id: 4,
    name: 'Topics',
    queries: ['Industry news'],
    brand: 'Brand',
    competitors: ['Competitor'],
    domain: 'example.com',
    searchOptions: {
        ...options.defaults,
        categories: ['news', 'general'],
        engines: [],
    },
    interval: '7',
    sendTime: '09:00',
    timezone: 'Europe/Moscow',
    sendToBot: true,
    enabled: true,
    nextRunAt: '2026-10-16T06:00:00Z',
    createdAt: '2026-10-09T06:00:00Z',
};
const listing: ReportsList = {
    schedules: [schedule],
    reports: { data: [], currentPage: 1, lastPage: 1, total: 0, perPage: 20 },
    botLinked: true,
    botExportsEnabled: true,
    timezone: 'Europe/Moscow',
    maxQueries: 3,
    maxSchedules: 5,
};
const browserPage = { hidden: false };
const defaultResponse = (url: string) =>
    Promise.resolve(url === '/news-media-intel/options' ? options : listing);

describe('scheduled news analytics reports', () => {
    beforeEach(() => {
        hooks.request.mockReset().mockImplementation(defaultResponse);
        hooks.mounted.length = 0;
        hooks.unmounted.length = 0;
        browserPage.hidden = false;
        vi.useFakeTimers();
        vi.stubGlobal('document', browserPage);
    });
    afterEach(() => {
        hooks.unmounted.forEach((callback) => callback());
        vi.useRealTimers();
        vi.unstubAllGlobals();
    });

    it('submits normalized topics and analytics with an independent monthly cadence and all-time filter', async () => {
        const state = useNewsReports();
        await state.refresh();
        Object.assign(state.form.value, {
            name: ' Research ',
            queries: ' Industry news \r\n Product launch \n',
            brand: ' Brand ',
            competitors: ' Other \n Third ',
            domain: 'https://www.example.com/catalog',
            interval: 'month',
            sendToBot: true,
        });
        state.form.value.filters.timeRange = '';
        state.form.value.filters.engines = ['bing'];
        expect(await state.create()).toBe(true);
        expect(hooks.request).toHaveBeenCalledWith(
            '/news-media-intel/reports/schedules',
            expect.objectContaining({
                method: 'POST',
                body: {
                    name: 'Research',
                    queries: ['Industry news', 'Product launch'],
                    brand: 'Brand',
                    competitors: ['Other', 'Third'],
                    domain: 'example.com',
                    language: 'ru',
                    timeRange: '',
                    safeSearch: 1,
                    engines: ['bing'],
                    maxPages: 3,
                    interval: 'month',
                    sendTime: '09:00',
                    timezone: 'Europe/Moscow',
                    sendToBot: true,
                },
            })
        );
        expect(state.form.value.queries).toBe('');
        expect(state.notice.value).toBe('newsMediaReports.created');
    });

    it('does not submit invalid topics, and enforces loaded schedule limits', async () => {
        const state = useNewsReports();
        await state.refresh();
        Object.assign(state.form.value, {
            name: 'Topics',
            queries: '!google Brand',
        });
        expect(state.canCreate.value).toBe(false);
        expect(await state.create()).toBe(false);
        expect(
            hooks.request.mock.calls.filter(
                ([, request]) => request.method === 'POST'
            )
        ).toHaveLength(0);
        state.form.value.queries = 'Brand';
        hooks.request.mockImplementation((url: string) =>
            Promise.resolve(
                url === '/news-media-intel/options'
                    ? options
                    : { ...listing, maxSchedules: 1 }
            )
        );
        await state.refresh();
        expect(state.canCreate.value).toBe(false);
    });

    it('preserves the form on server validation failure and retains previously loaded history', async () => {
        const state = useNewsReports();
        await state.refresh();
        Object.assign(state.form.value, { name: 'Topics', queries: 'Brand' });
        hooks.request.mockRejectedValueOnce(
            new ApiError({
                ok: false,
                status: 422,
                message: 'Invalid',
                errors: { queries: ['Use plain text.'] },
            })
        );
        await state.create();
        expect(state.error.value).toBe('Use plain text.');
        expect(state.form.value.queries).toBe('Brand');
        hooks.request.mockRejectedValueOnce(
            new ApiError({ ok: false, message: 'SQLSTATE internal failure' })
        );
        await state.refresh();
        expect(state.list.value).toEqual(listing);
        expect(state.error.value).toBe('newsMediaReports.error');
    });

    it('retries filter loading after failure without losing the report list or edited timezone', async () => {
        hooks.request.mockImplementation((url: string) =>
            url === '/news-media-intel/options'
                ? Promise.reject(new Error('Failed to fetch'))
                : Promise.resolve(listing)
        );
        const state = useNewsReports();
        await state.refresh();
        expect(state.list.value).toEqual(listing);
        expect(state.options.value).toBeNull();
        expect(state.optionsError.value).toBe('newsMediaReports.optionsError');
        state.form.value.timezone = 'UTC';
        hooks.request.mockImplementation(defaultResponse);
        await state.refresh();
        expect(state.options.value).toEqual(options);
        expect(state.optionsError.value).toBe('');
        expect(state.form.value.timezone).toBe('UTC');
    });

    it('serializes mutations, sends explicit pause/resume, and generates localized saved report links', async () => {
        const state = useNewsReports();
        await state.refresh();
        let finish!: () => void;
        hooks.request.mockReturnValueOnce(
            new Promise<void>((resolve) => {
                finish = resolve;
            })
        );
        const pending = state.run(schedule);
        await state.remove(schedule);
        expect(
            hooks.request.mock.calls.filter(
                ([, request]) => request.method === 'DELETE'
            )
        ).toHaveLength(0);
        finish();
        await pending;
        await state.change(schedule);
        await state.change({ ...schedule, enabled: false });

        for (const action of ['pause', 'resume']) {
            expect(hooks.request).toHaveBeenCalledWith(
                '/news-media-intel/reports/schedules/4',
                expect.objectContaining({ method: 'PATCH', body: { action } })
            );
        }

        expect(state.reportViewUrl(8)).toBe(
            '/news-media-intel/reports/8/view?locale=ru'
        );
        expect(state.reportDownloadUrl(8, 'json')).toBe(
            '/news-media-intel/reports/8/download/json?locale=ru'
        );
    });

    it('polls only visible pages and aborts requests and timers on disposal', async () => {
        useNewsReports();
        hooks.mounted.forEach((callback) => callback());
        await vi.advanceTimersByTimeAsync(0);
        expect(hooks.request).toHaveBeenCalledTimes(2);
        browserPage.hidden = true;
        await vi.advanceTimersByTimeAsync(60_000);
        expect(hooks.request).toHaveBeenCalledTimes(2);
        browserPage.hidden = false;
        await vi.advanceTimersByTimeAsync(60_000);
        expect(hooks.request).toHaveBeenCalledTimes(3);
        const signal = hooks.request.mock.calls[0][1].signal as AbortSignal;
        hooks.unmounted.forEach((callback) => callback());
        expect(signal.aborted).toBe(true);
        await vi.advanceTimersByTimeAsync(60_000);
        expect(hooks.request).toHaveBeenCalledTimes(3);
    });

    it('ignores a late successful response after the reports tab was unmounted', async () => {
        let finish!: (value: ReportsList) => void;
        hooks.request.mockImplementation((url: string) =>
            url === '/news-media-intel/options'
                ? Promise.resolve(options)
                : new Promise<ReportsList>((resolve) => {
                      finish = resolve;
                  })
        );
        const state = useNewsReports();
        const pending = state.refresh();
        hooks.unmounted.forEach((callback) => callback());
        finish(listing);
        await pending;
        expect(state.list.value).toBeNull();
        expect(state.error.value).toBe('');
        await state.run(schedule);
        expect(hooks.request).toHaveBeenCalledTimes(2);
    });
});
