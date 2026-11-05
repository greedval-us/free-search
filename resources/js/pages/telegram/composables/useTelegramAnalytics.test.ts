import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useTelegramAnalytics } from './useTelegramAnalytics';

const { apiRequest } = vi.hoisted(() => ({ apiRequest: vi.fn() }));

vi.mock('@/lib/api', () => ({
    apiRequest,
    resolveClientErrorMessage: () => 'Request failed',
}));

describe('useTelegramAnalytics', () => {
    beforeEach(() => apiRequest.mockReset());
    afterEach(() => vi.unstubAllGlobals());

    it('uses the server comparison without a second collection request', async () => {
        const previous = { range: { chatUsername: 'example' } };
        apiRequest.mockResolvedValue({
            ok: true,
            data: {
                range: { chatUsername: 'example' },
                previousReport: previous,
            },
        });
        const analytics = useTelegramAnalytics(
            (key) => key,
            () => 7
        );
        analytics.form.chatUsername = '@example';

        await analytics.loadAnalytics();

        expect(apiRequest).toHaveBeenCalledOnce();
        expect(apiRequest.mock.calls[0][0]).not.toContain('snapshotRole');
        expect(analytics.previousPayload.value).toEqual(previous);
        expect(analytics.loading.value).toBe(false);
    });

    it('exports the displayed report after the user edits the search form', async () => {
        const open = vi.fn();
        vi.stubGlobal('window', { open });
        vi.stubGlobal('document', { documentElement: { lang: 'ru-RU' } });
        apiRequest.mockResolvedValue({
            ok: true,
            data: {
                range: {
                    chatUsername: 'public_channel',
                    keyword: 'A & B',
                    dateFrom: '2026-06-01T00:00:00Z',
                    dateTo: '2026-06-07T23:59:59Z',
                },
                score: { priority: 'reach' },
            },
        });
        const analytics = useTelegramAnalytics(
            (key) => key,
            () => 7
        );
        analytics.form.chatUsername = '@public_channel';
        await analytics.loadAnalytics();
        analytics.form.chatUsername = '@another_channel';
        analytics.form.keyword = 'different';
        analytics.downloadReport();

        const url = new URL(open.mock.calls[0][0], 'https://example.org');
        expect(url.pathname).toBe('/telegram/analytics/report');
        expect(Object.fromEntries(url.searchParams)).toEqual({
            chatUsername: 'public_channel',
            keyword: 'A & B',
            dateFrom: '2026-06-01',
            dateTo: '2026-06-07',
            locale: 'ru',
            scorePriority: 'reach',
            download: '1',
        });
    });

    it('keeps the current result if a comparison is unavailable', async () => {
        apiRequest.mockResolvedValue({
            ok: true,
            data: { range: { chatUsername: 'example' }, previousReport: null },
        });
        const analytics = useTelegramAnalytics(
            (key) => key,
            () => 7
        );
        analytics.form.chatUsername = 'example';

        await analytics.loadAnalytics();

        expect(analytics.payload.value?.range.chatUsername).toBe('example');
        expect(analytics.previousPayload.value).toBeNull();
        expect(analytics.error.value).toBeNull();
        expect(apiRequest).toHaveBeenCalledOnce();
    });

    it('uses the configured inclusive range in the date inputs and request validation', async () => {
        const translate = vi.fn((key: string) => key);
        const analytics = useTelegramAnalytics(translate, () => 2);
        analytics.form.chatUsername = 'example';
        analytics.form.dateFrom = '2026-03-28';
        analytics.form.dateTo = '2026-03-30';

        expect(analytics.dateLimits.value.fromMin).toBe('2026-03-29');
        expect(analytics.dateLimits.value.toMax).toBe('2026-03-29');
        await analytics.loadAnalytics();
        expect(apiRequest).not.toHaveBeenCalled();
        expect(translate).toHaveBeenCalledWith('telegram.customRangeTooLong', {
            days: 2,
        });

        apiRequest.mockResolvedValue({
            ok: true,
            data: { previousReport: null },
        });
        analytics.form.dateTo = '2026-03-29';
        await analytics.loadAnalytics();
        expect(apiRequest).toHaveBeenCalledOnce();
    });

    it('rejects nonexistent calendar dates and keeps years below 100 literal', async () => {
        const analytics = useTelegramAnalytics(
            (key) => key,
            () => 2
        );
        analytics.form.chatUsername = 'example';
        analytics.form.dateFrom = '2026-02-31';
        analytics.form.dateTo = '2026-03-04';
        await analytics.loadAnalytics();
        expect(analytics.error.value).toBe('telegram.customRangeInvalid');
        expect(apiRequest).not.toHaveBeenCalled();

        analytics.form.dateFrom = '0099-12-31';
        analytics.form.dateTo = '0100-01-01';
        expect(analytics.dateLimits.value.toMax).toBe('0100-01-01');
        expect(analytics.dateLimits.value.fromMin).toBe('0099-12-31');
        apiRequest.mockResolvedValue({
            ok: true,
            data: { previousReport: null },
        });
        await analytics.loadAnalytics();
        expect(apiRequest).toHaveBeenCalledOnce();
    });
});
