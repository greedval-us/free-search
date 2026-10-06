import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { effectScope, nextTick, ref } from 'vue';
import type { EffectScope } from 'vue';
import type * as Api from '@/lib/api';
import { useMonitoringState } from './useMonitoringState';

const request = vi.hoisted(() => vi.fn());
vi.mock('@/composables/useI18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
}));
vi.mock('@/lib/api', async (original) => ({
    ...(await original<typeof Api>()),
    apiRequestOrThrow: request,
}));
let scopes: EffectScope[] = [];
const create = (poll = false) => {
    const initial = ref({ id: 1, status: 'queued' });
    const endpoint = ref('/monitoring/projects/1/status');
    const scope = effectScope();
    scopes.push(scope);
    const state = scope.run(() =>
        useMonitoringState(initial, endpoint, () => poll)
    )!;

    return { ...state, initial, endpoint, scope };
};
describe('monitoring request lifecycle', () => {
    beforeEach(() => {
        request.mockReset();
        vi.useFakeTimers();
    });
    afterEach(() => {
        scopes.forEach((scope) => scope.stop());
        scopes = [];
        vi.useRealTimers();
    });
    it('ignores a response for a project left while the request was pending', async () => {
        let finish!: (value: unknown) => void;
        request.mockImplementation(
            () =>
                new Promise((resolve) => {
                    finish = resolve;
                })
        );
        const state = create();
        const pending = state.refresh();
        state.initial.value = { id: 2, status: 'completed' };
        state.endpoint.value = '/monitoring/projects/2/status';
        await nextTick();
        finish({ id: 1, status: 'completed' });
        await pending;
        expect(state.state.value).toEqual({ id: 2, status: 'completed' });
    });
    it('does not accept an older response over a newer refresh', async () => {
        let finish!: (value: unknown) => void;
        request
            .mockImplementationOnce(
                () =>
                    new Promise((resolve) => {
                        finish = resolve;
                    })
            )
            .mockResolvedValueOnce({ id: 1, status: 'completed' });
        const state = create();
        const first = state.refresh();
        await state.refresh();
        finish({ id: 1, status: 'queued' });
        await first;
        expect(state.state.value.status).toBe('completed');
    });
    it('prevents repeated mutation clicks and disables automatic retries', async () => {
        let finish!: (value: unknown) => void;
        request.mockImplementationOnce(
            () =>
                new Promise((resolve) => {
                    finish = resolve;
                })
        );
        const state = create();
        const first = state.mutate('/reports', 'POST', {
            request_key: 'stable',
        });
        await state.mutate('/reports', 'POST', { request_key: 'stable' });
        expect(request).toHaveBeenCalledTimes(1);
        expect(state.busy.value).toBe(true);
        expect(request).toHaveBeenCalledWith(
            '/reports',
            expect.objectContaining({ retry: { attempts: 0 } })
        );
        finish({ id: 7 });
        await first;
        expect(state.busy.value).toBe(false);
    });
    it('polls saved state and clears polling and aborts a request on disposal', async () => {
        request.mockImplementation(() => new Promise(() => {}));
        const state = create(true);
        await vi.advanceTimersByTimeAsync(5000);
        const signal = request.mock.calls[0][1].signal as AbortSignal;
        expect(request).toHaveBeenCalledTimes(1);
        state.scope.stop();
        expect(signal.aborted).toBe(true);
        await vi.advanceTimersByTimeAsync(30000);
        expect(request).toHaveBeenCalledTimes(1);
    });
    it('does not discard a completed mutation because a background refresh ran', async () => {
        let finish!: (value: unknown) => void;
        request
            .mockImplementationOnce(
                () =>
                    new Promise((resolve) => {
                        finish = resolve;
                    })
            )
            .mockResolvedValueOnce({ id: 1, status: 'completed' });
        const state = create();
        const command = state.mutate('/reports', 'POST');
        await state.refresh();
        finish({ id: 7 });
        expect(await command).toEqual({ id: 7 });
    });
    it('keeps actions locked while refreshing the saved state after a mutation', async () => {
        let finish!: () => void;
        request.mockResolvedValue({ id: 7 });
        const state = create();
        const pending = state.mutate(
            '/schedules',
            'POST',
            {},
            () =>
                new Promise((resolve) => {
                    finish = resolve;
                })
        );
        await nextTick();
        await state.mutate('/schedules', 'POST', {});
        expect(request).toHaveBeenCalledTimes(1);
        expect(state.busy.value).toBe(true);
        finish();
        await pending;
        expect(state.busy.value).toBe(false);
    });
});
