<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, nextTick, reactive, ref } from 'vue';
import { useI18n } from '@/composables/useI18n';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Telegram',
                href: '/telegram',
            },
        ],
    },
});

type SearchItem = {
    id: number;
    date: number;
    message: string;
    fromId: number;
    authorId: number | null;
    peerId: number;
    views: number | null;
    forwards: number | null;
    postAuthor: string | null;
    authorSignature: string | null;
    repliesCount: number | null;
    telegramUrl: string | null;
    media: {
        hasMedia: boolean;
        type: string;
        label: string;
        rawType: string | null;
    };
    reactions: Array<{
        key: string;
        emoji: string;
        count: number;
        senderIds: number[];
    }>;
    reactionSenderIds: number[];
    gifts: {
        hasGift: boolean;
        types: string[];
        senderIds: number[];
        entries: Array<{
            key: string;
            label: string;
            senderIds: number[];
        }>;
    };
};

type SearchResponse = {
    ok: boolean;
    message?: string;
    items: SearchItem[];
    pagination: {
        limit: number;
        offsetId: number;
        nextOffsetId: number | null;
        hasMore: boolean;
        total: number;
    };
};

type CommentItem = {
    id: number;
    date: number;
    message: string;
    authorId: number | null;
};

type CommentState = {
    open: boolean;
    loading: boolean;
    loaded: boolean;
    error: string | null;
    items: CommentItem[];
    total: number;
    hasMore: boolean;
    nextOffsetId: number | null;
};

const form = reactive({
    chatUsername: 'durov',
    q: '',
    fromUsername: '',
    dateFrom: '',
    dateTo: '',
    limit: 20,
});

const { t } = useI18n();

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

const canSearch = computed(() => form.chatUsername.trim().length > 0);
const loadedCount = computed(() => items.value.length);
const pageTitle = computed(() => t('telegram.headTitle'));
const LIMIT_MIN = 1;
const LIMIT_MAX = 50;

const clampLimit = () => {
    const value = Number(form.limit);

    if (!Number.isFinite(value)) {
        form.limit = 20;

        return;
    }

    form.limit = Math.min(LIMIT_MAX, Math.max(LIMIT_MIN, Math.trunc(value)));
};

const buildQuery = (params: Record<string, string | number>) => {
    const query = new URLSearchParams();

    Object.entries(params).forEach(([key, value]) => {
        if (String(value).length > 0) {
            query.set(key, String(value));
        }
    });

    return query.toString();
};

const formatDate = (unix: number) => {
    if (!unix) {
        return '-';
    }

    return new Date(unix * 1000).toLocaleString();
};

const mediaLabel = (type: string) => {
    const map: Record<string, string> = {
        photo: t('telegram.mediaTypes.photo'),
        video: t('telegram.mediaTypes.video'),
        document: t('telegram.mediaTypes.document'),
        audio: t('telegram.mediaTypes.audio'),
        geo: t('telegram.mediaTypes.geo'),
        poll: t('telegram.mediaTypes.poll'),
        contact: t('telegram.mediaTypes.contact'),
        link_preview: t('telegram.mediaTypes.link_preview'),
        other: t('telegram.mediaTypes.other'),
        none: t('telegram.mediaTypes.none'),
    };

    return map[type] ?? t('telegram.mediaTypes.other');
};

const getMediaUrl = (postId: number) =>
    `/telegram/media/${encodeURIComponent(form.chatUsername.trim().replace(/^@+/, ''))}/${postId}`;

const canPreviewInlineMedia = (type: string) => ['photo', 'video', 'audio'].includes(type);

const isMediaOpen = (postId: number) => Boolean(mediaOpenByPost.value[postId]);

const toggleMedia = (postId: number) => {
    mediaOpenByPost.value[postId] = !isMediaOpen(postId);
};

const searchMessages = async (append = false) => {
    if (!canSearch.value) {
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
        const query = buildQuery({
            chatUsername: form.chatUsername.trim(),
            q: form.q.trim(),
            fromUsername: form.fromUsername.trim(),
            dateFrom: form.dateFrom,
            dateTo: form.dateTo,
            limit: Math.min(LIMIT_MAX, Math.max(LIMIT_MIN, form.limit)),
            offsetId: append ? (nextOffsetId.value ?? 0) : 0,
        });

        const response = await fetch(`/telegram/search/messages?${query}`, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
            },
        });

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
    } catch (e) {
        error.value = e instanceof Error ? e.message : t('telegram.errors.unknownSearchError');
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
    const nextState: Record<string, boolean> = {};
    const targetKey = getSenderPopoverKey(postId, kind, itemKey);

    if (!isSenderPopoverOpen(postId, kind, itemKey)) {
        nextState[targetKey] = true;
    }

    senderPopoverOpenByKey.value = nextState;
};

const preservePostPosition = async (
    postId: number,
    action: () => Promise<void> | void,
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
            const query = buildQuery({
                chatUsername: form.chatUsername.trim(),
                postId,
                offsetId: append ? (state.nextOffsetId ?? 0) : 0,
                limit: 20,
            });

            const response = await fetch(`/telegram/search/comments?${query}`, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
            });

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
        } catch (e) {
            state.error = e instanceof Error ? e.message : t('telegram.errors.commentsFailed');

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
</script>

<template>
    <Head :title="pageTitle" />

    <div class="flex h-full min-h-0 flex-1 flex-col gap-4 overflow-hidden rounded-xl p-4">
        <section class="sticky top-0 z-10 shrink-0 rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold">{{ t('telegram.search.title') }}</h2>
                    <p class="text-xs text-muted-foreground">
                        {{ searchPanelCollapsed ? t('telegram.search.collapsed') : t('telegram.search.filters') }}
                    </p>
                </div>

                <button
                    type="button"
                    class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-input text-sm text-foreground transition hover:bg-accent"
                    @click="searchPanelCollapsed = !searchPanelCollapsed"
                >
                    {{ searchPanelCollapsed ? '▼' : '▲' }}
                </button>
            </div>

            <div v-if="!searchPanelCollapsed" class="mt-3 flex flex-wrap items-end gap-3">
                <div class="grid min-w-0 flex-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <label class="block min-w-0">
                        <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('telegram.search.channelUsername') }}</span>
                        <input
                            v-model="form.chatUsername"
                            type="text"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            placeholder="durov"
                        />
                    </label>

                    <label class="block min-w-0">
                        <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('telegram.search.keyword') }}</span>
                        <input
                            v-model="form.q"
                            type="text"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            :placeholder="t('telegram.search.placeholderKeyword')"
                        />
                    </label>

                    <label class="block min-w-0">
                        <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('telegram.search.authorId') }}</span>
                        <input
                            v-model="form.fromUsername"
                            type="text"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            :placeholder="t('telegram.search.placeholderAuthor')"
                        />
                    </label>
                </div>

                <div class="flex w-full flex-wrap gap-2 lg:w-auto">
                    <button
                        type="button"
                        :aria-label="showAdvanced ? t('telegram.search.advancedAriaHide') : t('telegram.search.advancedAriaShow')"
                        class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-md border border-input text-lg font-medium text-foreground hover:bg-accent"
                        @click="showAdvanced = !showAdvanced"
                    >
                        &#9881;
                    </button>

                    <button
                        :disabled="loading || !canSearch"
                        class="h-10 cursor-pointer rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                        @click="searchMessages(false)"
                    >
                        {{ loading ? t('telegram.search.searching') : t('telegram.search.find') }}
                    </button>
                </div>
            </div>

            <div
                v-if="!searchPanelCollapsed && showAdvanced"
                class="mt-3 grid gap-3 border-t border-border/60 pt-3 md:grid-cols-3"
            >
                <label class="block min-w-0">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('telegram.search.limit') }}</span>
                    <input
                        v-model.number="form.limit"
                        type="number"
                        min="1"
                        max="50"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        @input="clampLimit"
                        @blur="clampLimit"
                    />
                </label>

                <label class="block min-w-0">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('telegram.search.dateFrom') }}</span>
                    <input
                        v-model="form.dateFrom"
                        type="date"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    />
                </label>

                <label class="block min-w-0">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('telegram.search.dateTo') }}</span>
                    <input
                        v-model="form.dateTo"
                        type="date"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    />
                </label>
            </div>

            <p v-if="error" class="mt-3 text-sm text-destructive">{{ error }}</p>
        </section>

        <section class="flex min-h-0 flex-1 flex-col rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold">{{ t('telegram.messages.title') }}</h2>
                <p class="text-xs text-muted-foreground">{{ t('telegram.messages.shown') }}: {{ loadedCount }} / {{ total }}</p>
            </div>

            <div ref="messagesContainerRef" class="telegram-scroll min-h-0 flex-1 overflow-y-auto overscroll-contain pr-1">
                <div v-if="!loading && items.length === 0" class="rounded-md border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                    {{ t('telegram.messages.empty') }}
                </div>

                <div v-else class="space-y-3">
                    <article
                        v-for="item in items"
                        :key="item.id"
                        :data-post-id="item.id"
                        class="relative rounded-lg border border-border/80 bg-background/70 p-3"
                    >
                        <div class="mb-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                            <span>{{ t('telegram.messages.authorId') }}: {{ item.authorId ?? '-' }}</span>
                            <span>{{ t('telegram.messages.date') }}: {{ formatDate(item.date) }}</span>
                            <span>{{ t('telegram.messages.views') }}: {{ item.views ?? '-' }}</span>
                            <span>{{ t('telegram.messages.forwards') }}: {{ item.forwards ?? '-' }}</span>
                            <span>{{ t('telegram.messages.replies') }}: {{ item.repliesCount ?? '-' }}</span>
                            <span>{{ t('telegram.messages.media') }}: {{ mediaLabel(item.media.type) }}</span>
                            <span v-if="item.gifts.hasGift" class="text-amber-500">{{ t('telegram.messages.giftYes') }}</span>
                        </div>

                        <p class="text-sm leading-relaxed text-foreground">
                            {{ item.message || t('telegram.messages.noText') }}
                        </p>

                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <a
                                v-if="item.telegramUrl"
                                :href="item.telegramUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="cursor-pointer rounded-full border border-input px-2 py-1 text-xs text-primary hover:bg-accent"
                            >
                                {{ t('telegram.messages.openInTelegram') }}
                            </a>

                            <span
                                v-if="item.media.hasMedia"
                                class="rounded-full border border-input px-2 py-1 text-xs text-muted-foreground"
                            >
                                {{ item.media.label || mediaLabel(item.media.type) }}
                            </span>

                            <button
                                v-if="item.media.hasMedia"
                                type="button"
                                class="cursor-pointer rounded-full border border-input px-3 py-1 text-xs font-medium text-foreground hover:bg-accent"
                                @click="toggleMedia(item.id)"
                            >
                                {{ isMediaOpen(item.id) ? t('telegram.messages.hideMedia') : t('telegram.messages.showMedia') }}
                            </button>

                            <div
                                v-for="reaction in item.reactions"
                                :key="`${item.id}-${reaction.key}`"
                                class="relative inline-flex items-center gap-1"
                            >
                                <span class="rounded-full border border-input px-2 py-1 text-xs">
                                    {{ reaction.emoji }} {{ reaction.count }}
                                </span>

                                <button
                                    v-if="reaction.senderIds.length > 0"
                                    type="button"
                                    class="inline-flex h-6 w-6 cursor-pointer items-center justify-center rounded-full border border-input text-[11px] text-foreground hover:bg-accent"
                                    @click="toggleSenderPopover(item.id, 'reaction', reaction.key)"
                                >
                                    {{ isSenderPopoverOpen(item.id, 'reaction', reaction.key) ? '▲' : '▼' }}
                                </button>

                                <div
                                    v-if="isSenderPopoverOpen(item.id, 'reaction', reaction.key)"
                                    class="absolute right-0 top-full z-20 mt-2 w-64 rounded-lg border border-border bg-card/95 p-3 shadow-2xl backdrop-blur"
                                >
                                    <p class="mb-2 text-xs font-semibold">{{ t('telegram.popover.reactionIds') }} {{ reaction.emoji }}</p>

                                    <div class="telegram-scroll max-h-52 space-y-1 overflow-y-auto pr-1">
                                        <p
                                            v-for="senderId in reaction.senderIds"
                                            :key="`${item.id}-${reaction.key}-sender-${senderId}`"
                                            class="rounded-md border border-input/70 bg-background/70 px-2 py-1 text-xs"
                                        >
                                            {{ t('telegram.popover.id') }}: {{ senderId }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-for="gift in item.gifts.entries"
                                :key="`${item.id}-${gift.key}`"
                                class="relative inline-flex items-center gap-1"
                            >
                                <span class="rounded-full border border-amber-400/40 bg-amber-400/10 px-2 py-1 text-xs text-amber-600 dark:text-amber-300">
                                    {{ gift.label }}
                                </span>

                                <button
                                    v-if="gift.senderIds.length > 0"
                                    type="button"
                                    class="inline-flex h-6 w-6 cursor-pointer items-center justify-center rounded-full border border-amber-400/40 bg-amber-400/10 text-[11px] text-amber-700 hover:bg-amber-400/20 dark:text-amber-300"
                                    @click="toggleSenderPopover(item.id, 'gift', gift.key)"
                                >
                                    {{ isSenderPopoverOpen(item.id, 'gift', gift.key) ? '▲' : '▼' }}
                                </button>

                                <div
                                    v-if="isSenderPopoverOpen(item.id, 'gift', gift.key)"
                                    class="absolute right-0 top-full z-20 mt-2 w-64 rounded-lg border border-amber-400/40 bg-card/95 p-3 shadow-2xl backdrop-blur"
                                >
                                    <p class="mb-2 text-xs font-semibold">{{ t('telegram.popover.giftIds') }} {{ gift.label }}</p>

                                    <div class="telegram-scroll max-h-52 space-y-1 overflow-y-auto pr-1">
                                        <p
                                            v-for="senderId in gift.senderIds"
                                            :key="`${item.id}-${gift.key}-sender-${senderId}`"
                                            class="rounded-md border border-amber-400/30 bg-background/70 px-2 py-1 text-xs"
                                        >
                                            {{ t('telegram.popover.id') }}: {{ senderId }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <button
                                v-if="(item.repliesCount ?? 0) > 0"
                                type="button"
                                class="cursor-pointer rounded-full border border-input px-3 py-1 text-xs font-medium text-foreground hover:bg-accent"
                                @click="toggleComments(item.id)"
                            >
                                {{ commentsToggleLabel(item.id, item.repliesCount) }}
                            </button>
                        </div>

                        <div
                            v-if="item.media.hasMedia && isMediaOpen(item.id)"
                            class="mt-3 overflow-hidden rounded-lg border border-border/70 bg-card/60 p-3"
                        >
                            <img
                                v-if="item.media.type === 'photo'"
                                :src="getMediaUrl(item.id)"
                                :alt="`media-${item.id}`"
                                class="max-h-[32rem] w-full rounded-md object-contain"
                                loading="lazy"
                            />

                            <video
                                v-else-if="item.media.type === 'video'"
                                :src="getMediaUrl(item.id)"
                                controls
                                preload="metadata"
                                class="max-h-[32rem] w-full rounded-md bg-black"
                            />

                            <audio
                                v-else-if="item.media.type === 'audio'"
                                :src="getMediaUrl(item.id)"
                                controls
                                preload="metadata"
                                class="w-full"
                            />

                            <div v-else class="flex flex-wrap items-center gap-2">
                                <span class="text-xs text-muted-foreground">
                                    {{ t('telegram.messages.unsupportedMedia') }}
                                </span>
                                <a
                                    :href="getMediaUrl(item.id)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="cursor-pointer rounded-md border border-input px-3 py-1 text-xs font-medium text-primary hover:bg-accent"
                                >
                                    {{ t('telegram.messages.openFile') }}
                                </a>
                            </div>
                        </div>

                        <div
                            v-if="getCommentState(item.id).open"
                            class="mt-3 rounded-lg border border-border/70 bg-card/60 p-3"
                        >
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-[11px] text-muted-foreground">
                                    {{ t('telegram.comments.title') }}: {{ getCommentState(item.id).items.length }} / {{ getCommentState(item.id).total || (item.repliesCount ?? 0) }}
                                </p>
                            </div>

                            <p
                                v-if="getCommentState(item.id).loading && getCommentState(item.id).items.length === 0"
                                class="text-xs text-muted-foreground"
                            >
                                {{ t('telegram.comments.loading') }}
                            </p>

                            <p
                                v-else-if="getCommentState(item.id).error && getCommentState(item.id).items.length === 0"
                                class="text-xs text-destructive"
                            >
                                {{ getCommentState(item.id).error }}
                            </p>

                            <p
                                v-else-if="getCommentState(item.id).items.length === 0"
                                class="text-xs text-muted-foreground"
                            >
                                {{ t('telegram.comments.notFound') }}
                            </p>

                            <div v-else class="space-y-2">
                                <article
                                    v-for="comment in getCommentState(item.id).items"
                                    :key="`${item.id}-comment-${comment.id}`"
                                    class="rounded-md border border-border/70 bg-background/70 p-2"
                                >
                                    <div class="mb-1 flex flex-wrap items-center gap-2 text-[11px] text-muted-foreground">
                                        <span>{{ t('telegram.comments.author') }}: {{ comment.authorId ?? '-' }}</span>
                                        <span>{{ t('telegram.comments.date') }}: {{ formatDate(comment.date) }}</span>
                                    </div>
                                    <p class="text-xs leading-relaxed text-foreground">
                                        {{ comment.message || t('telegram.messages.noText') }}
                                    </p>
                                </article>
                            </div>

                            <div
                                v-if="!getCommentState(item.id).loading && getCommentState(item.id).hasMore"
                                class="mt-2"
                            >
                                <button
                                    type="button"
                                    :disabled="getCommentState(item.id).loading"
                                    class="cursor-pointer rounded-md border border-input px-3 py-1 text-xs font-medium text-foreground hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                                    @click="loadComments(item.id, true)"
                                >
                                    {{ t('telegram.comments.loadMore') }}
                                </button>
                            </div>

                            <p
                                v-if="getCommentState(item.id).loading && getCommentState(item.id).items.length > 0"
                                class="mt-2 text-xs text-muted-foreground"
                            >
                                {{ t('telegram.comments.loadingMore') }}
                            </p>

                            <p
                                v-if="getCommentState(item.id).error && getCommentState(item.id).items.length > 0"
                                class="mt-2 text-xs text-destructive"
                            >
                                {{ getCommentState(item.id).error }}
                            </p>
                        </div>

                    </article>
                </div>
            </div>

            <div class="mt-4 flex justify-center" v-if="hasMore">
                <button
                    :disabled="loadingMore"
                    class="cursor-pointer rounded-md border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                    @click="searchMessages(true)"
                >
                    {{ loadingMore ? t('telegram.messages.loadingMore') : t('telegram.messages.loadMore') }}
                </button>
            </div>
        </section>
    </div>
</template>
