import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type * as Vue from 'vue';
import { useYouTubeComments } from './useYouTubeComments';
import { useYouTubeSearch } from './useYouTubeSearch';

const { apiRequest, onMounted } = vi.hoisted(() => ({
    apiRequest: vi.fn(),
    onMounted: vi.fn(),
}));
vi.mock('vue', async (importOriginal) => ({
    ...(await importOriginal<typeof Vue>()),
    onMounted,
}));
vi.mock('@/lib/api', () => ({
    apiRequest,
    resolveApiErrorMessage: (message: string) => message,
}));

const t = (key: string) => key;
const page = (id: string, nextPageToken: string | null = null) => ({
    ok: true,
    data: {
        items: [{ id }],
        pagination: { nextPageToken, total: 2, perPage: 1 },
    },
});

describe('YouTube search', () => {
    beforeEach(() => vi.clearAllMocks());
    afterEach(() => vi.unstubAllGlobals());

    it('restores repeat filters and uses the next token when appending results', async () => {
        vi.stubGlobal('window', {
            location: {
                search: '?tab=search&autorun=1&q=%20music%20&type=channel&order=date&regionCode=DE&limit=99',
            },
        });
        apiRequest
            .mockResolvedValueOnce(page('channel-1', 'next'))
            .mockResolvedValueOnce(page('channel-2'));
        const resetComments = vi.fn();
        const search = useYouTubeSearch(t, resetComments);
        onMounted.mock.calls[0][0]();
        await vi.waitFor(() =>
            expect(search.result.value?.items[0].id).toBe('channel-1')
        );
        expect(apiRequest).toHaveBeenNthCalledWith(
            1,
            '/youtube/search/videos',
            {
                query: expect.objectContaining({
                    q: 'music',
                    type: 'channel',
                    order: 'date',
                    regionCode: 'DE',
                    limit: 10,
                    pageToken: '',
                }),
            }
        );
        await search.runSearch(true);
        expect(apiRequest).toHaveBeenLastCalledWith('/youtube/search/videos', {
            query: expect.objectContaining({
                pageToken: 'next',
                order: 'date',
            }),
        });
        expect(search.result.value?.items.map((item) => item.id)).toEqual([
            'channel-1',
            'channel-2',
        ]);
        expect(resetComments).toHaveBeenCalledOnce();
    });

    it('keeps stale pagination out of a replacement search', async () => {
        const search = useYouTubeSearch(t, vi.fn());
        apiRequest.mockResolvedValueOnce(page('previous', 'old-next'));
        await search.runSearch();
        let finish!: (result: ReturnType<typeof page>) => void;
        apiRequest.mockReturnValueOnce(
            new Promise((resolve) => {
                finish = resolve;
            })
        );
        const oldPage = search.runSearch(true);
        search.form.q = 'new search';
        apiRequest.mockResolvedValueOnce(page('new'));
        await search.runSearch();
        finish(page('stale'));
        await oldPage;
        expect(search.result.value?.items.map((item) => item.id)).toEqual([
            'new',
        ]);
    });

    it('caches comment threads until a fresh search succeeds', async () => {
        const comments = useYouTubeComments(t);
        const search = useYouTubeSearch(t, comments.resetComments);
        apiRequest
            .mockResolvedValueOnce(page('comment-1', 'more-comments'))
            .mockResolvedValueOnce(page('comment-2'));
        await comments.toggleComments('video');
        comments.toggleReplies('video', 'comment-1');
        await comments.toggleComments('video');
        await comments.toggleComments('video');
        expect(apiRequest).toHaveBeenCalledOnce();
        await comments.loadMoreComments('video');
        expect(apiRequest).toHaveBeenLastCalledWith(
            '/youtube/search/comments-preview',
            {
                query: {
                    videoId: 'video',
                    limit: 20,
                    order: 'relevance',
                    pageToken: 'more-comments',
                },
            }
        );
        const state = comments.commentsByVideoId.value.video;
        expect(state.items.map((item) => item.id)).toEqual([
            'comment-1',
            'comment-2',
        ]);
        expect(state.openRepliesByCommentId['comment-1']).toBe(true);
        apiRequest.mockResolvedValueOnce({ ok: false, message: 'Unavailable' });
        await search.runSearch();
        expect(search.error.value).toBe('Unavailable');
        expect(comments.commentsByVideoId.value.video).toBe(state);
        apiRequest.mockResolvedValueOnce(page('video'));
        await search.runSearch();
        expect(comments.commentsByVideoId.value.video).toBeUndefined();
    });
});
