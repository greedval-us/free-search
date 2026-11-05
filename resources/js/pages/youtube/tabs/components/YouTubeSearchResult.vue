<script setup lang="ts">
import { ExternalLink } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';
import { useLocaleFormatters } from '@/composables/useLocaleFormatters';
import type { VideoComments } from '../../composables/useYouTubeComments';
import type { YouTubeVideo } from '../../types';
import YouTubeCommentCard from './YouTubeCommentCard.vue';

defineProps<{ video: YouTubeVideo; comments?: VideoComments }>();
const emit = defineEmits<{
    toggleComments: [];
    loadMoreComments: [];
    toggleReplies: [commentId: string];
}>();
const { t, locale } = useI18n();
const { formatNumber: fmt, formatDate } = useLocaleFormatters(locale);
</script>

<template>
    <article class="intel-result-card relative">
        <div class="flex min-w-0 flex-col gap-3 sm:flex-row">
            <img
                v-if="video.thumbnail"
                :src="video.thumbnail"
                :alt="video.title"
                class="aspect-video h-auto w-full rounded-md object-cover sm:h-24 sm:w-40 sm:shrink-0"
                loading="lazy"
            />
            <div class="min-w-0 flex-1">
                <h2 class="line-clamp-2 text-sm font-semibold">
                    {{ video.title }}
                </h2>
                <p class="mt-1 text-xs break-words text-muted-foreground">
                    {{ t(`youtube.options.searchType.${video.type}`) }}
                    | {{ video.channelTitle }} |
                    {{ t('youtube.common.published') }}:
                    {{ formatDate(video.publishedAt) }}
                </p>
                <p class="mt-2 line-clamp-2 text-xs text-muted-foreground">
                    {{ video.description }}
                </p>
                <div
                    v-if="video.type === 'video'"
                    class="mt-2 flex flex-wrap gap-2 text-xs"
                >
                    <span
                        >{{ t('youtube.analytics.metrics.views') }}:
                        {{ fmt(video.views) }}</span
                    >
                    <span
                        >{{ t('youtube.analytics.metrics.likes') }}:
                        {{ fmt(video.likes) }}</span
                    >
                    <span
                        >{{ t('youtube.analytics.metrics.comments') }}:
                        {{ fmt(video.comments) }}</span
                    >
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a
                        :href="video.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="intel-link-pill"
                    >
                        <ExternalLink class="mr-1 inline h-3 w-3" />
                        {{ t('youtube.common.open') }}
                    </a>
                    <button
                        v-if="video.type === 'video'"
                        type="button"
                        class="intel-action"
                        @click="emit('toggleComments')"
                    >
                        {{
                            comments?.open
                                ? t('youtube.comments.toggleHide')
                                : `${t('youtube.comments.toggleShow')} (${fmt(video.comments)})`
                        }}
                    </button>
                </div>

                <div
                    v-if="video.type === 'video' && comments?.open"
                    class="intel-subsection mt-3"
                >
                    <p
                        v-if="comments.loading"
                        class="text-xs text-muted-foreground"
                    >
                        {{ t('youtube.common.loading') }}
                    </p>
                    <p
                        v-else-if="comments.error"
                        class="text-xs break-words text-destructive"
                    >
                        {{ comments.error }}
                    </p>
                    <p
                        v-else-if="comments.items.length === 0"
                        class="text-xs text-muted-foreground"
                    >
                        {{ t('youtube.comments.empty') }}
                    </p>
                    <div v-else class="space-y-2">
                        <YouTubeCommentCard
                            v-for="comment in comments.items"
                            :key="comment.id"
                            :comment="comment"
                            :author-url="comment.authorChannelUrl"
                            :reply-count="comment.replyCount"
                        >
                            <div v-if="comment.replyCount > 0" class="mt-2">
                                <button
                                    type="button"
                                    class="intel-action text-[11px]"
                                    @click="emit('toggleReplies', comment.id)"
                                >
                                    {{
                                        comments.openRepliesByCommentId[
                                            comment.id
                                        ]
                                            ? t(
                                                  'youtube.comments.toggleHideReplies'
                                              )
                                            : `${t('youtube.comments.toggleShowReplies')} (${fmt(comment.replyCount)})`
                                    }}
                                </button>
                            </div>
                            <div
                                v-if="
                                    comment.replyCount > 0 &&
                                    comments.openRepliesByCommentId[comment.id]
                                "
                                class="mt-2 space-y-1.5 border-l border-border/70 pl-2"
                            >
                                <YouTubeCommentCard
                                    v-for="reply in comment.replies"
                                    :key="reply.id"
                                    :comment="reply"
                                />
                                <p
                                    v-if="
                                        comment.replyCount >
                                        comment.replies.length
                                    "
                                    class="text-[11px] text-muted-foreground"
                                >
                                    {{
                                        t('youtube.comments.repliesPartialHint')
                                    }}
                                </p>
                            </div>
                        </YouTubeCommentCard>
                        <div v-if="comments.nextPageToken" class="pt-1">
                            <button
                                type="button"
                                :disabled="comments.loadingMore"
                                class="intel-expand-button px-3 py-1"
                                @click="emit('loadMoreComments')"
                            >
                                {{
                                    comments.loadingMore
                                        ? t('youtube.common.loading')
                                        : t('youtube.common.loadMore')
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </article>
</template>
