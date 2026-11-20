import { ref } from 'vue';
import type { Translate } from '@/composables/useI18n';
import { apiRequest, resolveApiErrorMessage } from '@/lib/api';
import { commentsPreview } from '@/routes/youtube/search';
import type { YouTubeCommentItem, YouTubeCommentsPayload } from '../types';

export type VideoComments = {
    open: boolean;
    loading: boolean;
    loadingMore: boolean;
    error: string | null;
    items: YouTubeCommentItem[];
    nextPageToken: string | null;
    openRepliesByCommentId: Record<string, boolean>;
};

const COMMENTS_PAGE_SIZE = 20;

export function useYouTubeComments(t: Translate) {
    const commentsByVideoId = ref<Record<string, VideoComments>>(
        Object.create(null)
    );
    const resetComments = () => {
        commentsByVideoId.value = Object.create(null);
    };

    async function loadComments(videoId: string, append: boolean) {
        const state = commentsByVideoId.value[videoId];

        if (
            !state ||
            state.loading ||
            state.loadingMore ||
            (append && !state.nextPageToken)
        ) {
            return;
        }

        state.error = null;
        const loadingField = append ? 'loadingMore' : 'loading';
        state[loadingField] = true;
        const response = await apiRequest<YouTubeCommentsPayload>(
            commentsPreview.url(),
            {
                query: {
                    videoId,
                    limit: COMMENTS_PAGE_SIZE,
                    order: 'relevance',
                    pageToken: append ? state.nextPageToken : '',
                },
            }
        );
        state[loadingField] = false;

        if (!response.ok) {
            state.error = resolveApiErrorMessage(
                response.message,
                t('youtube.common.requestFailed')
            );

            return;
        }

        state.items = append
            ? [...state.items, ...response.data.items]
            : response.data.items;
        state.nextPageToken = response.data.pagination.nextPageToken;
    }

    async function toggleComments(videoId: string) {
        commentsByVideoId.value[videoId] ??= {
            open: false,
            loading: false,
            loadingMore: false,
            error: null,
            items: [],
            nextPageToken: null,
            openRepliesByCommentId: Object.create(null),
        };
        const state = commentsByVideoId.value[videoId];
        state.open = !state.open;

        if (state.open && !state.items.length) {
            await loadComments(videoId, false);
        }
    }

    function toggleReplies(videoId: string, commentId: string) {
        const replies = commentsByVideoId.value[videoId].openRepliesByCommentId;
        replies[commentId] = !replies[commentId];
    }

    return {
        commentsByVideoId,
        toggleComments,
        toggleReplies,
        loadMoreComments: (videoId: string) => loadComments(videoId, true),
        resetComments,
    };
}
