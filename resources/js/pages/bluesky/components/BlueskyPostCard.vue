<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import type {
    BlueskyInteractionState,
    BlueskyPost,
    BlueskyThreadState,
} from '../types';
import BlueskyInteractionPanel from './BlueskyInteractionPanel.vue';

defineProps<{
    post: BlueskyPost;
    formatDate: (value: string) => string;
    likesState: BlueskyInteractionState;
    repostsState: BlueskyInteractionState;
    threadState: BlueskyThreadState;
    toggleLikes: (post: BlueskyPost) => void | Promise<void>;
    toggleReposts: (post: BlueskyPost) => void | Promise<void>;
    toggleThread: (post: BlueskyPost) => void | Promise<void>;
    loadLikes: (post: BlueskyPost, append?: boolean) => void | Promise<void>;
    loadReposts: (post: BlueskyPost, append?: boolean) => void | Promise<void>;
}>();

const { t } = useI18n();

const mediaPreviewUrl = (post: BlueskyPost): string => {
    if (post.media.type === 'images') {
        return (
            post.media.images[0]?.fullsize || post.media.images[0]?.thumb || ''
        );
    }

    if (post.media.type === 'video' || post.media.type === 'recordWithMedia') {
        return post.media.video.thumbnail || '';
    }

    if (post.media.type === 'external') {
        return post.media.external.thumb || '';
    }

    return '';
};
</script>

<template>
    <article class="intel-result-card-strong">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        :href="post.author.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-sm font-semibold text-primary hover:underline"
                    >
                        {{ post.author.displayName || post.author.handle }}
                    </a>
                    <span class="text-xs text-muted-foreground">
                        @{{ post.author.handle }}
                    </span>
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ formatDate(post.createdAt) }}
                </p>
            </div>

            <a
                v-if="post.url"
                :href="post.url"
                target="_blank"
                rel="noopener noreferrer"
                class="intel-link-action"
            >
                {{ t('bluesky.common.openPost') }}
            </a>
        </div>

        <p class="mt-3 text-sm leading-relaxed text-foreground">
            {{ post.text || t('bluesky.search.noText') }}
        </p>

        <div
            v-if="post.hasMedia || post.media.type === 'external'"
            class="intel-subsection mt-3 overflow-hidden"
        >
            <div
                v-if="post.media.images.length > 0"
                class="grid gap-3 md:grid-cols-2"
            >
                <a
                    v-for="(image, index) in post.media.images"
                    :key="`${post.id}-image-${index}`"
                    :href="image.fullsize || image.thumb"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="overflow-hidden rounded-lg border border-border/70"
                >
                    <img
                        :src="image.thumb || image.fullsize"
                        :alt="image.alt || `media-${index}`"
                        class="h-56 w-full object-cover"
                        loading="lazy"
                    />
                </a>
            </div>

            <div v-else-if="post.media.video.thumbnail" class="space-y-2">
                <img
                    :src="post.media.video.thumbnail"
                    :alt="post.media.video.alt || post.text"
                    class="h-56 w-full rounded-md object-cover"
                    loading="lazy"
                />
                <a
                    v-if="post.media.video.playlist"
                    :href="post.media.video.playlist"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-xs text-primary hover:underline"
                >
                    {{ t('bluesky.common.openMedia') }}
                </a>
            </div>

            <div v-else-if="post.media.external.uri" class="flex gap-3">
                <img
                    v-if="mediaPreviewUrl(post)"
                    :src="mediaPreviewUrl(post)"
                    :alt="post.media.external.title || post.text"
                    class="h-20 w-20 rounded-md object-cover"
                    loading="lazy"
                />
                <div class="min-w-0">
                    <a
                        :href="post.media.external.uri"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-sm font-medium text-primary hover:underline"
                    >
                        {{
                            post.media.external.title || post.media.external.uri
                        }}
                    </a>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ post.media.external.description }}
                    </p>
                </div>
            </div>
        </div>

        <div
            v-if="
                post.languages.length > 0 ||
                post.hashtags.length > 0 ||
                post.domains.length > 0
            "
            class="mt-3 grid gap-2 text-xs md:grid-cols-3"
        >
            <div v-if="post.languages.length > 0">
                <p class="mb-1 font-medium text-muted-foreground">
                    {{ t('bluesky.metrics.languages') }}
                </p>
                <p>{{ post.languages.join(', ') }}</p>
            </div>
            <div v-if="post.hashtags.length > 0">
                <p class="mb-1 font-medium text-muted-foreground">
                    {{ t('bluesky.metrics.hashtags') }}
                </p>
                <p>{{ post.hashtags.join(', ') }}</p>
            </div>
            <div v-if="post.domains.length > 0">
                <p class="mb-1 font-medium text-muted-foreground">
                    {{ t('bluesky.metrics.domains') }}
                </p>
                <p>{{ post.domains.join(', ') }}</p>
            </div>
        </div>

        <div class="intel-subsection mt-3 text-xs">
            <p class="mb-2 font-medium text-muted-foreground">
                {{ t('bluesky.osint.title') }}
            </p>
            <div class="grid gap-2 md:grid-cols-2">
                <p>
                    <span class="text-muted-foreground">DID:</span>
                    {{ post.author.did || '-' }}
                </p>
                <p>
                    <span class="text-muted-foreground">CID:</span>
                    {{ post.cid || '-' }}
                </p>
                <p>
                    <span class="text-muted-foreground">AT URI:</span>
                    {{ post.uri || '-' }}
                </p>
                <p>
                    <span class="text-muted-foreground"
                        >{{ t('bluesky.osint.indexedAt') }}:</span
                    >
                    {{ formatDate(post.indexedAt) }}
                </p>
                <p>
                    <span class="text-muted-foreground"
                        >{{ t('bluesky.osint.replyRoot') }}:</span
                    >
                    {{ post.replyRootUri || '-' }}
                </p>
                <p>
                    <span class="text-muted-foreground"
                        >{{ t('bluesky.osint.replyParent') }}:</span
                    >
                    {{ post.replyParentUri || '-' }}
                </p>
            </div>

            <div v-if="post.links.length > 0" class="mt-3">
                <p class="mb-1 font-medium text-muted-foreground">
                    {{ t('bluesky.osint.links') }}
                </p>
                <div class="space-y-1">
                    <a
                        v-for="link in post.links"
                        :key="`${post.id}-${link}`"
                        :href="link"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="block truncate text-primary hover:underline"
                    >
                        {{ link }}
                    </a>
                </div>
            </div>

            <div
                v-if="post.mentions.length > 0 || post.labels.length > 0"
                class="mt-3 grid gap-2 md:grid-cols-2"
            >
                <div v-if="post.mentions.length > 0">
                    <p class="mb-1 font-medium text-muted-foreground">
                        {{ t('bluesky.osint.mentions') }}
                    </p>
                    <p>{{ post.mentions.join(', ') }}</p>
                </div>
                <div v-if="post.labels.length > 0">
                    <p class="mb-1 font-medium text-muted-foreground">
                        {{ t('bluesky.osint.labels') }}
                    </p>
                    <p>{{ post.labels.join(', ') }}</p>
                </div>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap gap-2 text-xs text-muted-foreground">
            <button
                type="button"
                class="intel-action"
                @click="toggleLikes(post)"
            >
                {{
                    likesState.open
                        ? t('bluesky.engagement.hideLikes')
                        : `${t('bluesky.engagement.showLikes')} (${post.likeCount})`
                }}
            </button>
            <button
                type="button"
                class="intel-action"
                @click="toggleReposts(post)"
            >
                {{
                    repostsState.open
                        ? t('bluesky.engagement.hideReposts')
                        : `${t('bluesky.engagement.showReposts')} (${post.repostCount})`
                }}
            </button>
            <button
                type="button"
                class="intel-action"
                @click="toggleThread(post)"
            >
                {{
                    threadState.open
                        ? t('bluesky.engagement.hideThread')
                        : `${t('bluesky.engagement.showThread')} (${post.replyCount})`
                }}
            </button>
            <span class="intel-pill">
                {{ t('bluesky.metrics.quotes') }}: {{ post.quoteCount }}
            </span>
            <span class="intel-pill">
                {{ t('bluesky.metrics.postType') }}: {{ post.postType }}
            </span>
            <span v-if="post.hasMedia" class="intel-pill-accent">
                {{ t('bluesky.metrics.media') }}
            </span>
        </div>

        <BlueskyInteractionPanel
            v-if="likesState.open"
            :title="t('bluesky.engagement.likes')"
            :empty-text="t('bluesky.engagement.emptyLikes')"
            :post="post"
            :state="likesState"
            :format-date="formatDate"
            :load-more="loadLikes"
        />

        <BlueskyInteractionPanel
            v-if="repostsState.open"
            :title="t('bluesky.engagement.reposts')"
            :empty-text="t('bluesky.engagement.emptyReposts')"
            :post="post"
            :state="repostsState"
            :format-date="formatDate"
            :load-more="loadReposts"
        />

        <div v-if="threadState.open" class="intel-subsection mt-3">
            <p class="mb-2 text-xs font-medium">
                {{ t('bluesky.engagement.thread') }}
            </p>
            <p v-if="threadState.loading" class="text-xs text-muted-foreground">
                {{ t('bluesky.engagement.loading') }}
            </p>
            <p v-else-if="threadState.error" class="text-xs text-destructive">
                {{ threadState.error }}
            </p>
            <div v-else class="space-y-3 text-xs">
                <div v-if="threadState.ancestors.length > 0">
                    <p class="mb-2 font-medium text-muted-foreground">
                        {{ t('bluesky.engagement.ancestors') }}
                    </p>
                    <div class="space-y-2">
                        <div
                            v-for="ancestor in threadState.ancestors"
                            :key="ancestor.id"
                            class="intel-list-card"
                        >
                            <div class="mb-1 flex flex-wrap items-center gap-2">
                                <a
                                    :href="ancestor.author.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="font-medium text-primary hover:underline"
                                >
                                    {{
                                        ancestor.author.displayName ||
                                        ancestor.author.handle
                                    }}
                                </a>
                                <span class="text-muted-foreground">
                                    @{{ ancestor.author.handle }}
                                </span>
                            </div>
                            <p>
                                {{
                                    ancestor.text || t('bluesky.search.noText')
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="threadState.replies.length > 0">
                    <p class="mb-2 font-medium text-muted-foreground">
                        {{ t('bluesky.engagement.replies') }}
                    </p>
                    <div class="space-y-2">
                        <div
                            v-for="reply in threadState.replies"
                            :key="reply.id"
                            class="intel-list-card"
                        >
                            <div class="mb-1 flex flex-wrap items-center gap-2">
                                <a
                                    :href="reply.author.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="font-medium text-primary hover:underline"
                                >
                                    {{
                                        reply.author.displayName ||
                                        reply.author.handle
                                    }}
                                </a>
                                <span class="text-muted-foreground">
                                    @{{ reply.author.handle }}
                                </span>
                            </div>
                            <p>
                                {{ reply.text || t('bluesky.search.noText') }}
                            </p>

                            <div
                                v-if="reply.replies.length > 0"
                                class="mt-2 space-y-2 border-l border-border/70 pl-3"
                            >
                                <div
                                    v-for="child in reply.replies"
                                    :key="child.id"
                                    class="intel-list-card"
                                >
                                    <div
                                        class="mb-1 flex flex-wrap items-center gap-2"
                                    >
                                        <a
                                            :href="child.author.url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="font-medium text-primary hover:underline"
                                        >
                                            {{
                                                child.author.displayName ||
                                                child.author.handle
                                            }}
                                        </a>
                                        <span class="text-muted-foreground">
                                            @{{ child.author.handle }}
                                        </span>
                                    </div>
                                    <p>
                                        {{
                                            child.text ||
                                            t('bluesky.search.noText')
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <p
                    v-if="
                        threadState.ancestors.length === 0 &&
                        threadState.replies.length === 0
                    "
                    class="text-muted-foreground"
                >
                    {{ t('bluesky.engagement.emptyThread') }}
                </p>
            </div>
        </div>
    </article>
</template>
