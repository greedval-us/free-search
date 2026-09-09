import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type * as Vue from 'vue';
import { useTelegramBotSettings } from '@/composables/useTelegramBotSettings';
import type { TelegramBotState } from '@/types/telegramBot';

const { request, dispose, reload } = vi.hoisted(() => ({
    request: vi.fn(),
    dispose: vi.fn(),
    reload: vi.fn(),
}));
vi.mock('@inertiajs/vue3', () => ({ router: { reload } }));
vi.mock('@/lib/api', () => ({
    apiRequestOrThrow: request,
    ApiError: class extends Error {},
}));
vi.mock('@/composables/useI18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
}));
vi.mock('vue', async (importOriginal) => ({
    ...(await importOriginal<typeof Vue>()),
    onMounted: (callback: () => void) => callback(),
    onScopeDispose: dispose,
}));

const initial = (): TelegramBotState => ({
    available: true,
    username: 'test_bot',
    link: null,
    pending: null,
    routes: {
        status: '/status',
        issue: '/issue',
        confirm: '/confirm',
        disconnect: '/disconnect',
        preferences: '/preferences',
    },
});
const pending = (): TelegramBotState => ({
    ...initial(),
    pending: {
        id: 'request-id',
        telegram_id: null,
        expires_at: '2026-09-09T13:00:00Z',
    },
});

describe('useTelegramBotSettings', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        request.mockReset();
        dispose.mockReset();
        reload.mockReset();
        vi.stubGlobal('document', {
            querySelector: () => ({ content: 'csrf' }),
        });
    });
    afterEach(() => {
        dispose.mock.calls.forEach(([callback]) => callback());
        vi.clearAllTimers();
        vi.useRealTimers();
        vi.unstubAllGlobals();
    });

    it('does not poll disabled or idle settings', async () => {
        useTelegramBotSettings({ ...initial(), available: false });
        await vi.advanceTimersByTimeAsync(15000);
        expect(request).not.toHaveBeenCalled();
    });

    it.each([
        ['refresh', '/status', 'GET'],
        ['issue', '/issue', 'POST'],
        ['confirm', '/confirm', 'POST'],
        ['disconnect', '/disconnect', 'DELETE'],
    ] as const)(
        'uses the existing HTTP method for %s',
        async (action, url, method) => {
            request.mockResolvedValue(initial());
            const settings = useTelegramBotSettings(pending());

            await settings[action]();

            expect(request).toHaveBeenCalledWith(
                url,
                expect.objectContaining({ method, retry: { attempts: 0 } })
            );
        }
    );

    it('updates preferences using PATCH without refreshing notifications', async () => {
        request.mockResolvedValue(initial());
        const settings = useTelegramBotSettings(initial());
        const preferences = {
            locale: 'ru' as const,
            notifications_enabled: true,
            exports_enabled: false,
            broadcasts_enabled: false,
        };

        await settings.save(preferences);

        expect(request).toHaveBeenCalledWith(
            '/preferences',
            expect.objectContaining({ method: 'PATCH', body: preferences })
        );
        expect(reload).not.toHaveBeenCalled();
    });

    it('polls pending linking until Telegram claims it and never confirms automatically', async () => {
        request.mockResolvedValue({
            ...pending(),
            pending: { ...pending().pending, telegram_id: '12345' },
        });
        const settings = useTelegramBotSettings(pending());
        await vi.advanceTimersByTimeAsync(15000);
        expect(request).toHaveBeenCalledTimes(1);
        expect(request).toHaveBeenCalledWith(
            '/status',
            expect.objectContaining({ method: 'GET', retry: { attempts: 0 } })
        );
        expect(settings.state.value.pending?.telegram_id).toBe('12345');
        expect(settings.state.value.link).toBeNull();
        expect(reload).not.toHaveBeenCalled();
    });

    it.each(['confirm', 'disconnect'] as const)(
        'refreshes shared notifications after a successful %s',
        async (action) => {
            request.mockResolvedValue(initial());
            const settings = useTelegramBotSettings(pending());

            await settings[action]();

            expect(reload).toHaveBeenCalledExactlyOnceWith({ only: ['auth'] });
        }
    );

    it.each(['confirm', 'disconnect'] as const)(
        'does not refresh notifications when %s fails',
        async (action) => {
            request.mockRejectedValue(new Error('Request failed'));
            const settings = useTelegramBotSettings(pending());

            await settings[action]();

            expect(reload).not.toHaveBeenCalled();
            expect(settings.error.value).toBe('telegramBot.error');
        }
    );

    it('aborts stale requests and ignores their responses after a newer action', async () => {
        let resolve!: (state: TelegramBotState & { url: string }) => void;
        request
            .mockImplementationOnce(
                () =>
                    new Promise((done) => {
                        resolve = done;
                    })
            )
            .mockResolvedValueOnce(initial());
        const settings = useTelegramBotSettings(initial());
        const first = settings.issue();
        const signal = request.mock.calls[0][1].signal as AbortSignal;
        await settings.disconnect();
        resolve({ ...pending(), url: 'https://t.me/test_bot?start=secret' });
        await first;
        expect(signal.aborted).toBe(true);
        expect(settings.state.value.pending).toBeNull();
        expect(settings.linkUrl.value).toBe('');
    });

    it('clears ephemeral link and cancels polling when the page scope ends', async () => {
        request.mockResolvedValue({
            ...pending(),
            url: 'https://t.me/test_bot?start=secret',
        });
        const settings = useTelegramBotSettings(initial());
        await settings.issue();
        expect(settings.linkUrl.value).toContain('start=');
        expect(request).toHaveBeenCalledWith(
            '/issue',
            expect.objectContaining({
                headers: { 'X-CSRF-TOKEN': 'csrf' },
                retry: { attempts: 0 },
            })
        );
        dispose.mock.calls[0][0]();
        await vi.advanceTimersByTimeAsync(15000);
        expect(settings.linkUrl.value).toBe('');
        expect(request).toHaveBeenCalledTimes(1);
    });
});
