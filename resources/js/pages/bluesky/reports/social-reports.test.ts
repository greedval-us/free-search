import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type * as Vue from 'vue';
import { ApiError } from '@/lib/api';
import type * as Api from '@/lib/api';
import { useMastodonReports } from '../../mastodon/reports/useMastodonReports';
import type { ReportSchedule, ReportsList } from './types';
import { useBlueskyReports } from './useBlueskyReports';

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

const modules = [
    {
        module: 'bluesky',
        useReports: useBlueskyReports,
        account: 'news.bsky.social',
        input: '@News.Bsky.Social\r\nhttps://bsky.app/profile/Another.Bsky.Social/',
        normalized: ['news.bsky.social', 'another.bsky.social'],
        duplicate:
            '@News.Bsky.Social\nhttps://bsky.app/profile/news.bsky.social/',
        four: 'one.bsky.social\ntwo.bsky.social\nthree.bsky.social\nfour.bsky.social',
    },
    {
        module: 'mastodon',
        useReports: useMastodonReports,
        account: 'news@mastodon.social',
        input: '@News@Mastodon.Social\r\nhttps://mastodon.social/users/Another/',
        normalized: ['news@mastodon.social', 'another@mastodon.social'],
        duplicate: '@News@Mastodon.Social\nhttps://mastodon.social/@news/',
        four: 'one@mastodon.social\ntwo@mastodon.social\nthree@mastodon.social\nfour@mastodon.social',
    },
];

describe.each(modules)('$module scheduled reports', (config) => {
    const prefix = `${config.module}Reports`;
    const baseUrl = `/${config.module}/analytics/reports`;
    const schedule: ReportSchedule = {
        id: 4,
        name: 'News',
        accounts: [config.account],
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
        reports: {
            data: [],
            currentPage: 1,
            lastPage: 1,
            total: 0,
            perPage: 20,
        },
        botLinked: true,
        botExportsEnabled: true,
        maxAccounts: 3,
        maxSchedules: 5,
        timezone: 'Europe/Moscow',
    };

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

    it('normalizes profile links and sends the selected cadence and bot preference', async () => {
        hooks.request.mockResolvedValue({ ...listing, botLinked: false });
        const state = config.useReports();
        await state.refresh();
        state.form.value = {
            name: ' News reports ',
            accounts: config.input,
            interval: 'month',
            sendTime: '18:30',
            timezone: ' Europe/Moscow ',
            sendToBot: true,
        };
        expect(state.canCreate.value).toBe(true);
        expect(await state.create()).toBe(true);
        expect(hooks.request).toHaveBeenCalledWith(
            `${baseUrl}/schedules`,
            expect.objectContaining({
                method: 'POST',
                body: {
                    name: 'News reports',
                    accounts: config.normalized,
                    interval: 'month',
                    sendTime: '18:30',
                    timezone: 'Europe/Moscow',
                    sendToBot: true,
                },
                query: { locale: 'ru' },
                retry: { attempts: 0 },
            })
        );
        expect(state.form.value.name).toBe('');
        expect(state.form.value.accounts).toBe('');
    });

    it('rejects duplicate accounts and inputs pasted on the same line', async () => {
        const state = config.useReports();
        state.form.value.accounts = config.duplicate;
        expect(state.accountsError.value).toBe(`${prefix}.duplicateAccounts`);
        state.form.value.accounts = `${config.account} ${config.account}`;
        expect(state.accountsError.value).toBe(`${prefix}.accountsOnePerLine`);
        expect(await state.create()).toBe(false);
        expect(hooks.request).not.toHaveBeenCalled();
    });

    it('checks server limits before allowing creation', async () => {
        hooks.request.mockResolvedValue({ ...listing, maxSchedules: 1 });
        const state = config.useReports();
        await state.refresh();
        state.form.value.name = 'News';
        state.form.value.accounts = config.account;
        expect(state.canCreate.value).toBe(false);
        expect(await state.create()).toBe(false);
        state.form.value.accounts = config.four;
        expect(state.accountsError.value).toBe(`${prefix}.tooManyAccounts`);
        expect(hooks.request).toHaveBeenCalledTimes(1);
    });

    it('preserves the form and displays useful server validation errors', async () => {
        hooks.request.mockResolvedValueOnce(listing).mockRejectedValueOnce(
            new ApiError({
                ok: false,
                status: 422,
                message: 'Invalid schedule',
                errors: { accounts: ['Public accounts only.'] },
            })
        );
        const state = config.useReports();
        await state.refresh();
        state.form.value.name = 'News';
        state.form.value.accounts = config.account;
        await state.create();
        expect(state.form.value.name).toBe('News');
        expect(state.form.value.accounts).toBe(config.account);
        expect(state.error.value).toBe('Public accounts only.');
    });

    it('preserves loaded data and hides technical errors after a failed refresh', async () => {
        hooks.request.mockResolvedValueOnce(listing).mockRejectedValueOnce(
            new ApiError({
                ok: false,
                message: 'SQLSTATE database exception',
            })
        );
        const state = config.useReports();
        await state.refresh();
        await state.refresh();
        expect(state.list.value?.schedules).toEqual([schedule]);
        expect(state.error.value).toBe(`${prefix}.error`);
    });

    it('preserves the selected timezone while navigating report history', async () => {
        hooks.request.mockResolvedValueOnce(listing).mockResolvedValueOnce({
            ...listing,
            reports: { ...listing.reports, currentPage: 2, lastPage: 3 },
        });
        const state = config.useReports();
        await state.refresh();
        expect(state.form.value.timezone).toBe('Europe/Moscow');
        state.form.value.timezone = 'UTC';
        await state.refresh(2);
        expect(state.form.value.timezone).toBe('UTC');
        expect(state.page.value).toBe(2);
        expect(hooks.request).toHaveBeenLastCalledWith(
            baseUrl,
            expect.objectContaining({ query: { page: 2, locale: 'ru' } })
        );
    });

    it('prevents duplicate mutations while generation is queued', async () => {
        let finish!: (value: ReportsList) => void;
        hooks.request
            .mockReturnValueOnce(
                new Promise<ReportsList>((resolve) => {
                    finish = resolve;
                })
            )
            .mockResolvedValueOnce(listing);
        const state = config.useReports();
        const pending = state.run(schedule);
        await state.run(schedule);
        expect(hooks.request).toHaveBeenCalledTimes(1);
        expect(hooks.request).toHaveBeenCalledWith(
            `${baseUrl}/schedules/4/run`,
            expect.objectContaining({ method: 'POST', retry: { attempts: 0 } })
        );
        finish(listing);
        await pending;
        expect(state.notice.value).toBe(`${prefix}.queued`);
        expect(state.busy.value).toBe(false);
    });

    it('pauses, resumes and deletes schedules through the module routes', async () => {
        hooks.request.mockResolvedValue(listing);
        const state = config.useReports();
        await state.change(schedule);
        expect(hooks.request).toHaveBeenCalledWith(
            `${baseUrl}/schedules/4`,
            expect.objectContaining({
                method: 'PATCH',
                body: { action: 'pause' },
            })
        );
        await state.change({ ...schedule, enabled: false });
        expect(hooks.request).toHaveBeenCalledWith(
            `${baseUrl}/schedules/4`,
            expect.objectContaining({
                method: 'PATCH',
                body: { action: 'resume' },
            })
        );
        await state.remove(schedule);
        expect(hooks.request).toHaveBeenCalledWith(
            `${baseUrl}/schedules/4`,
            expect.objectContaining({ method: 'DELETE' })
        );
        expect(state.notice.value).toBe(`${prefix}.deleted`);
    });

    it('builds saved HTML and JSON links with the current language', () => {
        const state = config.useReports();
        expect(state.reportViewUrl(23)).toBe(`${baseUrl}/23/view?locale=ru`);
        expect(state.reportDownloadUrl(23, 'html')).toBe(
            `${baseUrl}/23/download/html?locale=ru`
        );
        expect(state.reportDownloadUrl(23, 'json')).toBe(
            `${baseUrl}/23/download/json?locale=ru`
        );
    });

    it('polls visible pages and cleans up requests and timers on unmount', async () => {
        hooks.request.mockResolvedValue(listing);
        const state = config.useReports();
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
        expect(state.error.value).toBe('');
    });
});
