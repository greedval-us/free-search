<script setup lang="ts">
import { ChevronDown, ChevronUp, Search, Settings } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useTelegramSearch } from '../composables/useTelegramSearch';

const { t } = useI18n();

const {
    LIMIT_MAX,
    form,
    loading,
    loadingMore,
    error,
    items,
    total,
    hasMore,
    showAdvanced,
    searchPanelCollapsed,
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
} = useTelegramSearch(t);

const canSearch = computed(() => form.chatUsername.trim().length > 0);
const loadedCount = computed(() => items.value.length);

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
</script>

<template>
    <section class="sticky top-0 z-10 shrink-0 rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
        <div class="flex items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <Search class="h-4 w-4 text-cyan-400" />
                    <span>{{ t('telegram.search.title') }}</span>
                    <span class="group relative inline-flex">
                        <span
                            class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                            :aria-label="t('telegram.help.label')"
                        >
                            ?
                        </span>
                        <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                            {{ t('telegram.search.help.overview') }}
                        </span>
                    </span>
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ searchPanelCollapsed ? t('telegram.search.collapsed') : t('telegram.search.filters') }}
                </p>
            </div>

            <button
                type="button"
                class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-input text-sm text-foreground transition hover:bg-accent"
                @click="searchPanelCollapsed = !searchPanelCollapsed"
            >
                <ChevronDown v-if="searchPanelCollapsed" class="h-4 w-4" />
                <ChevronUp v-else class="h-4 w-4" />
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
                    class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-md border border-slate-700 bg-slate-900/80 text-slate-200 transition hover:border-cyan-300/40 hover:text-cyan-100"
                    :class="{ 'border-cyan-400/50 bg-cyan-400/20 text-cyan-300': showAdvanced }"
                    @click="showAdvanced = !showAdvanced"
                >
                    <Settings class="h-4 w-4" />
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
                    :max="LIMIT_MAX"
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
                                <ChevronUp v-if="isSenderPopoverOpen(item.id, 'reaction', reaction.key)" class="h-3.5 w-3.5" />
                                <ChevronDown v-else class="h-3.5 w-3.5" />
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
                                <ChevronUp v-if="isSenderPopoverOpen(item.id, 'gift', gift.key)" class="h-3.5 w-3.5" />
                                <ChevronDown v-else class="h-3.5 w-3.5" />
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

        <div v-if="hasMore" class="mt-4 flex justify-center">
            <button
                :disabled="loadingMore"
                class="cursor-pointer rounded-md border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                @click="searchMessages(true)"
            >
                {{ loadingMore ? t('telegram.messages.loadingMore') : t('telegram.messages.loadMore') }}
            </button>
        </div>
    </section>
</template>
