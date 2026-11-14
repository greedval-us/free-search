import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useTelegramSearch } from './useTelegramSearch';

const fetchRequest =
    vi.fn<(url: string, options: RequestInit) => Promise<Response>>();

const searchResponse = (
    offsetId: number,
    nextOffsetId: number | null,
    total: number | null = 50
) =>
    Response.json({
        ok: true,
        data: {
            items: [],
            pagination: {
                limit: 15,
                offsetId,
                nextOffsetId,
                hasMore: nextOffsetId !== null,
                total,
            },
        },
    });

const requestedUrl = (index: number) =>
    new URL(fetchRequest.mock.calls[index][0], 'https://example.org');

describe('useTelegramSearch', () => {
    beforeEach(() => {
        fetchRequest.mockReset();
        vi.stubGlobal('fetch', fetchRequest);
    });

    afterEach(() => vi.unstubAllGlobals());

    it('sends the original filters and API cursor when loading the next page', async () => {
        fetchRequest.mockResolvedValueOnce(searchResponse(0, 42));
        fetchRequest.mockResolvedValueOnce(searchResponse(42, null));
        fetchRequest.mockResolvedValueOnce(searchResponse(0, null));
        const search = useTelegramSearch((key) => key);
        Object.assign(search.form, {
            chatUsername: ' @public_channel ',
            q: ' A & B ',
            fromUsername: ' @public_author ',
            dateFrom: '2026-10-01',
            dateTo: '2026-10-31',
            limit: 15,
        });

        await search.searchMessages();

        const initial = requestedUrl(0);
        expect(initial.pathname).toBe('/telegram/search/messages');
        expect(Object.fromEntries(initial.searchParams)).toEqual({
            chatUsername: '@public_channel',
            q: 'A & B',
            fromUsername: '@public_author',
            dateFrom: '2026-10-01',
            dateTo: '2026-10-31',
            limit: '15',
            offsetId: '0',
        });
        expect(fetchRequest.mock.calls[0][1].method).toBe('GET');

        Object.assign(search.form, {
            chatUsername: '@another_channel',
            q: 'edited',
            fromUsername: '@another_author',
            dateFrom: '2026-11-01',
            dateTo: '2026-11-05',
            limit: 10,
        });

        await search.searchMessages(true);

        const next = requestedUrl(1);
        expect(next.pathname).toBe(initial.pathname);
        expect(Object.fromEntries(next.searchParams)).toEqual({
            ...Object.fromEntries(initial.searchParams),
            offsetId: '42',
        });

        await search.searchMessages();

        expect(Object.fromEntries(requestedUrl(2).searchParams)).toEqual({
            chatUsername: '@another_channel',
            q: 'edited',
            fromUsername: '@another_author',
            dateFrom: '2026-11-01',
            dateTo: '2026-11-05',
            limit: '10',
            offsetId: '0',
        });
    });

    it('uses the displayed results channel for comments and media after the form is edited', async () => {
        fetchRequest.mockResolvedValueOnce(searchResponse(0, null));
        fetchRequest.mockResolvedValueOnce(searchResponse(0, 5));
        fetchRequest.mockResolvedValueOnce(searchResponse(5, null));
        const search = useTelegramSearch((key) => key);
        search.form.chatUsername = ' @public_channel ';
        await search.searchMessages();

        search.form.chatUsername = '@another_channel';
        await search.toggleComments(42);
        await search.loadComments(42, true);

        for (const [index, offsetId] of [
            [1, '0'],
            [2, '5'],
        ] as const) {
            const comments = requestedUrl(index);
            expect(comments.pathname).toBe('/telegram/search/comments');
            expect(Object.fromEntries(comments.searchParams)).toEqual({
                chatUsername: '@public_channel',
                postId: '42',
                offsetId,
                limit: '20',
            });
            expect(fetchRequest.mock.calls[index][1].method).toBe('GET');
        }

        expect(search.getMediaUrl(42)).toBe(
            '/telegram/media/public_channel/42'
        );
    });

    it('does not issue another page request while one is loading or after the cursor ends', async () => {
        fetchRequest.mockResolvedValueOnce(searchResponse(0, 42));
        fetchRequest.mockImplementation(async () => searchResponse(42, null));
        const search = useTelegramSearch((key) => key);
        search.form.chatUsername = '@public_channel';
        await search.searchMessages();

        await Promise.all([
            search.searchMessages(true),
            search.searchMessages(true),
        ]);
        await search.searchMessages(true);

        expect(fetchRequest).toHaveBeenCalledTimes(2);
    });

    it('shows the API validation for a saved search with a numeric author ID', async () => {
        const validationMessage =
            'Enter a public author username such as @username.';
        fetchRequest.mockResolvedValueOnce(
            Response.json(
                {
                    ok: false,
                    message: 'Check the submitted values.',
                    errors: { fromUsername: [validationMessage] },
                },
                { status: 422 }
            )
        );
        const search = useTelegramSearch((key) => key);
        search.form.chatUsername = '@public_channel';
        search.form.fromUsername = '123456789';

        await search.searchMessages();

        expect(requestedUrl(0).searchParams.get('fromUsername')).toBe(
            '123456789'
        );
        expect(search.error.value).toBe(validationMessage);
        expect(fetchRequest).toHaveBeenCalledOnce();
    });

    it('keeps an unavailable message total distinct from a known total of zero', async () => {
        fetchRequest.mockResolvedValueOnce(searchResponse(0, null, null));
        fetchRequest.mockResolvedValueOnce(searchResponse(0, null, 0));
        const search = useTelegramSearch((key) => key);
        search.form.chatUsername = '@public_channel';
        search.form.dateFrom = '2026-11-01';

        await search.searchMessages();

        expect(search.total.value).toBeNull();

        search.form.dateFrom = '';
        await search.searchMessages();

        expect(search.total.value).toBe(0);
    });
});
