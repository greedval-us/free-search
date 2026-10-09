import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type * as Vue from 'vue';
import { ApiError } from '@/lib/api';
import type * as Api from '@/lib/api';
import type { ReportSchedule, ReportsList } from './types';
import { useYouTubeReports } from './useYouTubeReports';

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
    name: 'News',
    channels: ['@news'],
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
    maxChannels: 3,
    maxSchedules: 5,
    timezone: 'Europe/Moscow',
};

describe('YouTube scheduled reports', () => {
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

    it('normalizes public links and sends the selected monthly cadence', async () => {
        hooks.request.mockResolvedValue(listing);
        const state = useYouTubeReports();
        await state.refresh();
        state.form.value = {
            name: ' News reports ',
            channels:
                ' @Example \r\nhttps://youtube.com/@Second_Channel/\nhttps://www.youtube.com/channel/UCAbCdEfGhIjKlMnOpQrStUv/\n\n',
            interval: 'month',
            sendTime: '18:30',
            timezone: ' Europe/Moscow ',
            sendToBot: true,
        };
        expect(await state.create()).toBe(true);
        expect(hooks.request).toHaveBeenCalledWith(
            '/youtube/analytics/reports/schedules',
            expect.objectContaining({
                method: 'POST',
                body: {
                    name: 'News reports',
                    channels: [
                        '@example',
                        '@second_channel',
                        'UCAbCdEfGhIjKlMnOpQrStUv',
                    ],
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
        expect(state.form.value.channels).toBe('');
    });

    it('rejects duplicate normalized channels and pasted space-separated channels', async () => {
        const state = useYouTubeReports();
        state.form.value.channels = '@Example\nhttps://youtube.com/@example/';
        expect(state.channelsError.value).toBe(
            'youtubeReports.duplicateChannels'
        );
        state.form.value.channels = '@first @second';
        expect(state.channelsError.value).toBe(
            'youtubeReports.channelsOnePerLine'
        );
        expect(await state.create()).toBe(false);
        expect(hooks.request).not.toHaveBeenCalled();
    });

    it('keeps the schedule form and displays validation errors when creation fails', async () => {
        hooks.request.mockResolvedValueOnce(listing).mockRejectedValueOnce(
            new ApiError({
                ok: false,
                status: 422,
                message: 'Invalid schedule',
                errors: { channels: ['Public channels only.'] },
            })
        );
        const state = useYouTubeReports();
        await state.refresh();
        state.form.value.name = 'News';
        state.form.value.channels = '@news';
        await state.create();
        expect(state.form.value.name).toBe('News');
        expect(state.form.value.channels).toBe('@news');
        expect(state.error.value).toBe('Public channels only.');
    });

    it('enforces the reported channel and schedule limits before submission', async () => {
        hooks.request.mockResolvedValue({ ...listing, maxSchedules: 1 });
        const state = useYouTubeReports();
        await state.refresh();
        state.form.value.name = 'News';
        state.form.value.channels = '@news';
        expect(state.canCreate.value).toBe(false);
        expect(await state.create()).toBe(false);
        state.form.value.channels = '@first\n@second\n@third\n@fourth';
        expect(state.channelsError.value).toBe(
            'youtubeReports.tooManyChannels'
        );
        expect(hooks.request).toHaveBeenCalledTimes(1);
    });

    it('rejects video links, ambiguous URLs and malformed channel identifiers', async () => {
        hooks.request.mockResolvedValue(listing);
        const state = useYouTubeReports();
        await state.refresh();
        state.form.value.name = 'News';

        for (const channel of [
            'https://youtube.com/watch?v=AbCdEfGhIjK',
            'https://youtube.com/c/news',
            'https://youtube.com/@news/videos',
            'https://youtube.com/@news?feature=shared',
            'https://youtube.com/@news#about',
            'https://youtube.com:8080/@news',
            'https://someone@youtube.com/@news',
            'https://youtube.com.example.org/@news',
            'UCshort',
            'news',
            '@',
        ]) {
            state.form.value.channels = channel;
            expect(state.channelsError.value).toBe(
                'youtubeReports.invalidChannels'
            );
            expect(state.canCreate.value).toBe(false);
        }
    });

    it('preserves case-sensitive channel IDs and supports international handles', async () => {
        hooks.request.mockResolvedValue(listing);
        const state = useYouTubeReports();
        await state.refresh();
        state.form.value.name = 'Channels';
        state.form.value.channels =
            'UCAbCdEfGhIjKlMnOpQrStUv\nUCabCdEfGhIjKlMnOpQrStUv\nhttps://m.youtube.com/@Новости';
        expect(state.channelsError.value).toBe('');
        expect(await state.create()).toBe(true);
        expect(hooks.request).toHaveBeenCalledWith(
            '/youtube/analytics/reports/schedules',
            expect.objectContaining({
                body: expect.objectContaining({
                    channels: [
                        'UCAbCdEfGhIjKlMnOpQrStUv',
                        'UCabCdEfGhIjKlMnOpQrStUv',
                        '@новости',
                    ],
                }),
            })
        );
    });

    it('preserves the loaded history and hides technical errors when refresh fails', async () => {
        hooks.request.mockResolvedValueOnce(listing).mockRejectedValueOnce(
            new ApiError({
                ok: false,
                message: 'SQLSTATE database exception',
            })
        );
        const state = useYouTubeReports();
        await state.refresh();
        await state.refresh();
        expect(state.list.value?.schedules).toEqual([schedule]);
        expect(state.error.value).toBe('youtubeReports.error');
    });

    it('lets users schedule Telegram delivery and link their bot later', async () => {
        hooks.request.mockResolvedValue({ ...listing, botLinked: false });
        const state = useYouTubeReports();
        await state.refresh();
        state.form.value.name = 'News';
        state.form.value.channels = '@news';
        state.form.value.sendToBot = true;
        expect(state.canCreate.value).toBe(true);
        expect(await state.create()).toBe(true);
        expect(hooks.request).toHaveBeenCalledWith(
            '/youtube/analytics/reports/schedules',
            expect.objectContaining({
                body: expect.objectContaining({ sendToBot: true }),
            })
        );
    });

    it('keeps a chosen timezone while refreshing report history pages', async () => {
        hooks.request.mockResolvedValueOnce(listing).mockResolvedValueOnce({
            ...listing,
            reports: { ...listing.reports, currentPage: 2, lastPage: 3 },
        });
        const state = useYouTubeReports();
        await state.refresh();
        expect(state.form.value.timezone).toBe('Europe/Moscow');
        state.form.value.timezone = 'UTC';
        await state.refresh(2);
        expect(state.form.value.timezone).toBe('UTC');
        expect(state.page.value).toBe(2);
        expect(hooks.request).toHaveBeenLastCalledWith(
            '/youtube/analytics/reports',
            expect.objectContaining({ query: { page: 2, locale: 'ru' } })
        );
    });

    it('prevents repeated mutations while report generation is queued', async () => {
        let finish!: (value: ReportsList) => void;
        hooks.request.mockReturnValueOnce(
            new Promise<ReportsList>((resolve) => {
                finish = resolve;
            })
        );
        hooks.request.mockResolvedValueOnce(listing);
        const state = useYouTubeReports();
        const pending = state.run(schedule);
        await state.run(schedule);
        expect(hooks.request).toHaveBeenCalledTimes(1);
        expect(hooks.request).toHaveBeenCalledWith(
            '/youtube/analytics/reports/schedules/4/run',
            expect.objectContaining({ method: 'POST', retry: { attempts: 0 } })
        );
        finish(listing);
        await pending;
        expect(state.notice.value).toBe('youtubeReports.queued');
        expect(state.busy.value).toBe(false);
    });

    it('pauses and resumes schedules with explicit actions', async () => {
        hooks.request.mockResolvedValue(listing);
        const state = useYouTubeReports();
        await state.change(schedule);
        expect(hooks.request).toHaveBeenCalledWith(
            '/youtube/analytics/reports/schedules/4',
            expect.objectContaining({
                method: 'PATCH',
                body: { action: 'pause' },
            })
        );
        await state.change({ ...schedule, enabled: false });
        expect(hooks.request).toHaveBeenCalledWith(
            '/youtube/analytics/reports/schedules/4',
            expect.objectContaining({
                method: 'PATCH',
                body: { action: 'resume' },
            })
        );
    });

    it('creates report links with the active language', () => {
        const state = useYouTubeReports();
        expect(state.reportViewUrl(23)).toBe(
            '/youtube/analytics/reports/23/view?locale=ru'
        );
        expect(state.reportDownloadUrl(23, 'html')).toBe(
            '/youtube/analytics/reports/23/download/html?locale=ru'
        );
        expect(state.reportDownloadUrl(23, 'json')).toBe(
            '/youtube/analytics/reports/23/download/json?locale=ru'
        );
    });

    it('polls visible pages and aborts requests and timers after unmount', async () => {
        hooks.request.mockResolvedValue(listing);
        const state = useYouTubeReports();
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
