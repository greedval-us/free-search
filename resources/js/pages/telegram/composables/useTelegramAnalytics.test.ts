import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useTelegramAnalytics } from './useTelegramAnalytics';

const { apiRequest } = vi.hoisted(() => ({ apiRequest: vi.fn() }));

vi.mock('@/lib/api', () => ({
    apiRequest,
    resolveClientErrorMessage: () => 'Request failed',
}));

describe('useTelegramAnalytics', () => {
    beforeEach(() => apiRequest.mockReset());

    it('uses the server comparison without a second collection request', async () => {
        const previous = { range: { chatUsername: 'example' } };
        apiRequest.mockResolvedValue({
            ok: true,
            data: {
                range: { chatUsername: 'example' },
                previousReport: previous,
            },
        });
        const analytics = useTelegramAnalytics((key) => key);
        analytics.form.chatUsername = '@example';

        await analytics.loadAnalytics();

        expect(apiRequest).toHaveBeenCalledOnce();
        expect(apiRequest.mock.calls[0][0]).not.toContain('snapshotRole');
        expect(analytics.previousPayload.value).toEqual(previous);
        expect(analytics.loading.value).toBe(false);
    });

    it('keeps the current result if a comparison is unavailable', async () => {
        apiRequest.mockResolvedValue({
            ok: true,
            data: { range: { chatUsername: 'example' }, previousReport: null },
        });
        const analytics = useTelegramAnalytics((key) => key);
        analytics.form.chatUsername = 'example';

        await analytics.loadAnalytics();

        expect(analytics.payload.value?.range.chatUsername).toBe('example');
        expect(analytics.previousPayload.value).toBeNull();
        expect(analytics.error.value).toBeNull();
        expect(apiRequest).toHaveBeenCalledOnce();
    });
});
