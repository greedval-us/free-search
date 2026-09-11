import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import type * as Vue from 'vue';
import { ApiError } from '@/lib/api';
import type * as Api from '@/lib/api';
import type { TrackingTask } from './types';
import { useTelegramTracking } from './useTelegramTracking';

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

const task: TrackingTask = {
    id: 1,
    name: 'Tracking',
    mode: 'keyword',
    query: 'test',
    status: 'active',
    pause_reason: null,
    notify_bot: false,
    started_at: '2026-09-10T00:00:00Z',
    expires_at: '2026-10-10T00:00:00Z',
    purge_at: null,
    messages_count: 0,
    can_renew: false,
    sources: [],
};
const listing = { items: [task], has_more: false, limit: 1, active_count: 1 };

describe('Telegram tracking state', () => {
    beforeEach(() => {
        hooks.request.mockReset();
        hooks.mounted.length = 0;
        hooks.unmounted.length = 0;
        vi.useFakeTimers();
        vi.stubGlobal('document', {
            querySelector: () => ({ content: 'csrf' }),
            hidden: false,
        });
    });
    afterEach(() => {
        hooks.unmounted.forEach((callback) => callback());
        vi.useRealTimers();
        vi.unstubAllGlobals();
    });

    it('restores tasks and disables automatic retries for mutation safety', async () => {
        hooks.request.mockResolvedValue(listing);
        const state = useTelegramTracking();
        await state.refresh();
        expect(state.list.value?.items[0].id).toBe(1);
        expect(hooks.request).toHaveBeenCalledWith(
            '/telegram/tracking',
            expect.objectContaining({
                query: { page: 1, locale: 'ru' },
                retry: { attempts: 0 },
            })
        );
    });

    it('keeps form data and shows field validation errors on failed create', async () => {
        hooks.request.mockRejectedValue(
            new ApiError({
                ok: false,
                message: 'Validation',
                errors: { query: ['Too short'] },
            })
        );
        const state = useTelegramTracking();
        state.form.value.query = 'ab';
        await state.create();
        expect(state.form.value.query).toBe('ab');
        expect(state.error.value).toBe('Too short');
        expect(state.busy.value).toBe(false);
    });

    it('invalidates group validation after editing the input', async () => {
        hooks.request.mockResolvedValue({
            groups: [{ title: 'Public group' }],
        });
        const state = useTelegramTracking();
        await state.validateGroups();
        expect(state.validatedGroups.value).toEqual(['Public group']);
        state.form.value.groups = '@different_group';
        await nextTick();
        expect(state.validatedGroups.value).toEqual([]);
    });

    it('submits the bound form, resets text fields and selects the created task', async () => {
        hooks.request
            .mockResolvedValueOnce({ id: task.id })
            .mockResolvedValueOnce(listing)
            .mockResolvedValueOnce({ items: [], has_more: false });
        const state = useTelegramTracking();
        state.form.value = {
            name: 'New tracking',
            groups: ' @first_group \r\n\n@second_group ',
            mode: 'user',
            query: '42',
            notify_bot: true,
        };

        await state.create();

        expect(hooks.request).toHaveBeenNthCalledWith(
            1,
            '/telegram/tracking',
            expect.objectContaining({
                method: 'POST',
                body: {
                    name: 'New tracking',
                    groups: ['@first_group', '@second_group'],
                    mode: 'user',
                    query: '42',
                    notify_bot: true,
                },
            })
        );
        expect(state.form.value).toEqual({
            name: '',
            groups: '',
            mode: 'user',
            query: '',
            notify_bot: true,
        });
        expect(state.selected.value?.id).toBe(task.id);
        expect(state.messagePage.value).toBe(1);
    });

    it('keeps the selected message page during a task list refresh', async () => {
        hooks.request
            .mockResolvedValueOnce({ items: [], has_more: true })
            .mockResolvedValueOnce(listing)
            .mockResolvedValueOnce({ items: [], has_more: false });
        const state = useTelegramTracking();

        await state.show(task, 2);
        await state.refresh();

        expect(state.selected.value?.id).toBe(task.id);
        expect(state.messagePage.value).toBe(2);
        expect(state.hasMoreMessages.value).toBe(false);
        expect(hooks.request).toHaveBeenLastCalledWith(
            '/telegram/tracking/1/messages',
            expect.objectContaining({ query: { page: 2, locale: 'ru' } })
        );
    });

    it.each([' ', '\t', '\u00a0'])(
        'rejects groups separated by %j before validating or creating',
        async (separator) => {
            const state = useTelegramTracking();
            const input = `@first_group${separator}@second_group`;
            state.form.value.groups = input;

            expect(state.groupsError.value).toBe(
                'telegramTracking.groupsOnePerLine'
            );
            await state.validateGroups();
            await state.create();

            expect(hooks.request).not.toHaveBeenCalled();
            expect(state.form.value.groups).toBe(input);
            expect(state.error.value).toBe('telegramTracking.groupsOnePerLine');
            expect(state.busy.value).toBe(false);
            state.form.value.groups = '@first_group\n@second_group';
            expect(state.groupsError.value).toBe('');
        }
    );

    it.each(['\n', '\r\n', '\r'])(
        'accepts one group per line with %j line endings',
        async (newline) => {
            hooks.request.mockResolvedValue({ groups: [] });
            const state = useTelegramTracking();
            state.form.value.groups = ` @first_group ${newline}${newline} https://t.me/second_group `;

            await state.validateGroups();

            expect(state.groupsError.value).toBe('');
            expect(hooks.request).toHaveBeenCalledWith(
                '/telegram/tracking/validate',
                expect.objectContaining({
                    method: 'POST',
                    body: {
                        groups: ['@first_group', 'https://t.me/second_group'],
                    },
                })
            );
        }
    );

    it('ignores validation results for groups edited while the request was pending', async () => {
        let finish!: (value: unknown) => void;
        hooks.request.mockImplementationOnce(
            () =>
                new Promise((resolve) => {
                    finish = resolve;
                })
        );
        const state = useTelegramTracking();
        state.form.value.groups = '@first_group';
        const pending = state.validateGroups();
        state.form.value.groups = '@second_group';
        await nextTick();
        finish({ groups: [{ title: 'First group' }] });
        await pending;

        expect(state.validatedGroups.value).toEqual([]);
    });

    it('does not submit the same action twice while a request is pending', async () => {
        let finish!: (value: unknown) => void;
        hooks.request.mockImplementationOnce(
            () =>
                new Promise((resolve) => {
                    finish = resolve;
                })
        );
        hooks.request.mockResolvedValue(listing);
        const state = useTelegramTracking();
        const pending = state.change(task, 'pause');
        await state.change(task, 'pause');
        expect(hooks.request).toHaveBeenCalledTimes(1);
        finish({});
        await pending;
        expect(hooks.request).toHaveBeenCalledTimes(2);
    });
});
