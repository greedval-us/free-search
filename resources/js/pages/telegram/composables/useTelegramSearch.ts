import { nextTick, reactive, ref } from 'vue';
import TelegramSearchController from '@/actions/App/Http/Controllers/TelegramSearchController';
import type { CommentState, SearchItem, SearchResponse } from '../types';

type TranslateFn = (key: string) => string;

const LIMIT_MIN = 1;
const LIMIT_MAX = 50;

export const useTelegramSearch = (t: TranslateFn) => {
    const form = reactive({
        chatUsername: 'durov',
        q: '',
        fromUsername: '',
        dateFrom: '',
        dateTo: '',
        limit: 20,
    });

    const loading = ref(false);
    const loadingMore = ref(false);
    const error = ref<string | null>(null);
    const items = ref<SearchItem[]>([]);
    const total = ref(0);
    const nextOffsetId = ref<number | null>(null);
    const hasMore = ref(false);
    const showAdvanced = ref(false);
    const searchPanelCollapsed = ref(false);
    const commentsByPost = ref<Record<number, CommentState>>({});
    const messagesContainerRef = ref<HTMLElement | null>(null);
    const senderPopoverOpenByKey = ref<Record<string, boolean>>({});
    const mediaOpenByPost = ref<Record<number, boolean>>({});

    const clampLimit = () => {
        const value = Number(form.limit);

        if (!Number.isFinite(value)) {
            form.limit = 20;

            return;
        }

        form.limit = Math.min(LIMIT_MAX, Math.max(LIMIT_MIN, Math.trunc(value)));
    };

    const normalizedChatUsername = () => form.chatUsername.trim().replace(/^@+/, '');

    const getMediaUrl = (postId: number) =>
        TelegramSearchController.media([normalizedChatUsername(), postId]).url;

    const isMediaOpen = (postId: number) => Boolean(mediaOpenByPost.value[postId]);

    const toggleMedia = (postId: number) => {
        mediaOpenByPost.value[postId] = !isMediaOpen(postId);
    };

    const searchMessages = async (append = false) => {
        const canSearch = form.chatUsername.trim().length > 0;

        if (!canSearch) {
            error.value = t('telegram.errors.channelRequired');

            return;
        }

        error.value = null;

        if (append) {
            loadingMore.value = true;
        } else {
            loading.value = true;
            items.value = [];
            commentsByPost.value = {};
            senderPopoverOpenByKey.value = {};
            mediaOpenByPost.value = {};
            nextOffsetId.value = null;
            hasMore.value = false;
            total.value = 0;
        }

        try {
            const response = await fetch(
                TelegramSearchController.messages({
                    query: {
                        chatUsername: form.chatUsername.trim(),
                        q: form.q.trim(),
                        fromUsername: form.fromUsername.trim(),
                        dateFrom: form.dateFrom,
                        dateTo: form.dateTo,
                        limit: Math.min(LIMIT_MAX, Math.max(LIMIT_MIN, form.limit)),
                        offsetId: append ? (nextOffsetId.value ?? 0) : 0,
                    },
                }).url,
                {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                    },
                }
            );

            const payload: SearchResponse = await response.json();

            if (!response.ok || !payload.ok) {
                error.value = payload.message ?? t('telegram.errors.searchFailed');

                return;
            }

            const incoming = payload.items ?? [];

            if (append) {
                const known = new Set(items.value.map((item) => item.id));

                for (const item of incoming) {
                    if (!known.has(item.id)) {
                        items.value.push(item);
                    }
                }
            } else {
                items.value = incoming;
            }

            total.value = payload.pagination.total ?? items.value.length;
            nextOffsetId.value = payload.pagination.nextOffsetId;
            hasMore.value = payload.pagination.hasMore;
        } catch (exception) {
            error.value = exception instanceof Error ? exception.message : t('telegram.errors.unknownSearchError');
        } finally {
            loading.value = false;
            loadingMore.value = false;
        }
    };

    const getCommentState = (postId: number): CommentState => {
        if (!commentsByPost.value[postId]) {
            commentsByPost.value[postId] = {
                open: false,
                loading: false,
                loaded: false,
                error: null,
                items: [],
                total: 0,
                hasMore: false,
                nextOffsetId: null,
            };
        }

        return commentsByPost.value[postId];
    };

    const getSenderPopoverKey = (postId: number, kind: 'reaction' | 'gift', itemKey: string) =>
        `${postId}:${kind}:${itemKey}`;

    const isSenderPopoverOpen = (postId: number, kind: 'reaction' | 'gift', itemKey: string) =>
        Boolean(senderPopoverOpenByKey.value[getSenderPopoverKey(postId, kind, itemKey)]);

    const toggleSenderPopover = (postId: number, kind: 'reaction' | 'gift', itemKey: string) => {
        const targetKey = getSenderPopoverKey(postId, kind, itemKey);

        senderPopoverOpenByKey.value = isSenderPopoverOpen(postId, kind, itemKey)
            ? {}
            : { [targetKey]: true };
    };

    const preservePostPosition = async (
        postId: number,
        action: () => Promise<void> | void
    ) => {
        const container = messagesContainerRef.value;
        const selector = `[data-post-id="${postId}"]`;
        const postElement = container?.querySelector<HTMLElement>(selector) ?? null;
        const beforeTop = postElement?.getBoundingClientRect().top ?? null;

        await action();
        await nextTick();

        if (!container || !postElement || beforeTop === null) {
            return;
        }

        const afterTop = postElement.getBoundingClientRect().top;
        container.scrollTop += afterTop - beforeTop;
    };

    const loadComments = async (postId: number, append = false) => {
        const state = getCommentState(postId);

        if (append && !state.hasMore) {
            return;
        }

        await preservePostPosition(postId, async () => {
            state.loading = true;
            state.error = null;

            try {
                const response = await fetch(
                    TelegramSearchController.comments({
                        query: {
                            chatUsername: form.chatUsername.trim(),
                            postId,
                            offsetId: append ? (state.nextOffsetId ?? 0) : 0,
                            limit: 20,
                        },
                    }).url,
                    {
                        method: 'GET',
                        headers: {
                            Accept: 'application/json',
                        },
                    }
                );

                const payload = await response.json();

                if (!response.ok || !payload?.ok) {
                    state.error = payload?.message ?? t('telegram.errors.commentsFailed');

                    if (!append) {
                        state.items = [];
                        state.total = 0;
                        state.hasMore = false;
                        state.nextOffsetId = null;
                        state.loaded = false;
                    }

                    return;
                }

                const incoming = Array.isArray(payload.items) ? payload.items : [];

                if (append) {
                    const known = new Set(state.items.map((item) => item.id));

                    for (const comment of incoming) {
                        if (!known.has(comment.id)) {
                            state.items.push(comment);
                        }
                    }
                } else {
                    state.items = incoming;
                }

                state.total = Number(payload?.pagination?.total ?? state.items.length);
                state.hasMore = Boolean(payload?.pagination?.hasMore);
                state.nextOffsetId = payload?.pagination?.nextOffsetId ?? null;
                state.loaded = true;
            } catch (exception) {
                state.error = exception instanceof Error ? exception.message : t('telegram.errors.commentsFailed');

                if (!append) {
                    state.items = [];
                    state.total = 0;
                    state.hasMore = false;
                    state.nextOffsetId = null;
                    state.loaded = false;
                }
            } finally {
                state.loading = false;
            }
        });
    };

    const toggleComments = async (postId: number) => {
        const state = getCommentState(postId);

        await preservePostPosition(postId, async () => {
            state.open = !state.open;
        });

        if (state.open && !state.loaded && !state.loading) {
            await loadComments(postId, false);
        }
    };

    const commentsToggleLabel = (postId: number, repliesCount: number | null) => {
        if (getCommentState(postId).open) {
            return t('telegram.comments.toggleHide');
        }

        return `${t('telegram.comments.toggleShow')} (${repliesCount ?? 0})`;
    };

    return {
        LIMIT_MAX,
        form,
        loading,
        loadingMore,
        error,
        items,
        total,
        nextOffsetId,
        hasMore,
        showAdvanced,
        searchPanelCollapsed,
        commentsByPost,
        messagesContainerRef,
        clampLimit,
        getMediaUrl,
        isMediaOpen,
        toggleMedia,
        searchMessages,
        getCommentState,
        isSenderPopoverOpen,
        toggleSenderPopover,
        loadComments,
        toggleComments,
        commentsToggleLabel,
    };
};

