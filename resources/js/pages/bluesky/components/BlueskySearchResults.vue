<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import type {
    BlueskyActor,
    BlueskyActorDetailsState,
    BlueskyInteractionState,
    BlueskyPost,
    BlueskySearchPayload,
    BlueskyThreadState,
} from '../types';

defineProps<{
    result: BlueskySearchPayload | null;
    loading: boolean;
    totalShown: number;
    formatDate: (value: string) => string;
    ensureLikesState: (postId: string) => BlueskyInteractionState;
    ensureRepostsState: (postId: string) => BlueskyInteractionState;
    ensureThreadState: (postId: string) => BlueskyThreadState;
    ensureActorDetailsState: (actorDid: string) => BlueskyActorDetailsState;
    toggleLikes: (post: BlueskyPost) => void | Promise<void>;
    toggleReposts: (post: BlueskyPost) => void | Promise<void>;
    toggleThread: (post: BlueskyPost) => void | Promise<void>;
    loadLikes: (post: BlueskyPost, append?: boolean) => void | Promise<void>;
    loadReposts: (post: BlueskyPost, append?: boolean) => void | Promise<void>;
    toggleActorPosts: (actor: BlueskyActor) => void | Promise<void>;
    toggleActorFollowers: (actor: BlueskyActor) => void | Promise<void>;
    toggleActorFollows: (actor: BlueskyActor) => void | Promise<void>;
    loadActorPosts: (actor: BlueskyActor, append?: boolean) => void | Promise<void>;
    loadActorFollowers: (actor: BlueskyActor, append?: boolean) => void | Promise<void>;
    loadActorFollows: (actor: BlueskyActor, append?: boolean) => void | Promise<void>;
}>();

const { t } = useI18n();

const mediaPreviewUrl = (post: BlueskyPost): string => {
    if (post.media.type === 'images') {
        return post.media.images[0]?.fullsize || post.media.images[0]?.thumb || '';
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
    <div class="mb-3 flex items-center justify-between gap-3">
        <h2 class="text-sm font-semibold">{{ t('bluesky.search.resultTitle') }}</h2>
        <p class="text-xs text-muted-foreground">
            {{ t('bluesky.search.shown') }}: {{ totalShown }}
        </p>
    </div>

    <div
        class="intel-scroll min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain pr-1"
    >
        <div v-if="loading" class="intel-empty">
            {{ t('bluesky.search.searching') }}
        </div>

        <div v-else-if="!result" class="intel-empty">
            {{ t('bluesky.search.empty') }}
        </div>

        <div v-else class="space-y-4">
            <section v-if="result.posts.length > 0" class="space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                        {{ t('bluesky.sections.posts') }}
                    </h3>
                    <span class="text-xs text-muted-foreground">{{ result.posts.length }}</span>
                </div>

                <article
                    v-for="post in result.posts"
                    :key="post.id"
                    class="rounded-lg border border-border/80 bg-background/70 p-4"
                >
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
                            class="rounded-full border border-input px-2 py-1 text-xs text-primary hover:bg-accent"
                        >
                            {{ t('bluesky.common.openPost') }}
                        </a>
                    </div>

                    <p class="mt-3 text-sm leading-relaxed text-foreground">
                        {{ post.text || t('bluesky.search.noText') }}
                    </p>

                    <div class="mt-3 flex flex-wrap gap-2 text-xs text-muted-foreground">
                        <button
                            type="button"
                            class="cursor-pointer rounded-full border border-input px-3 py-1 text-xs font-medium text-foreground hover:bg-accent"
                            @click="toggleLikes(post)"
                        >
                            {{
                                ensureLikesState(post.id).open
                                    ? t('bluesky.engagement.hideLikes')
                                    : `${t('bluesky.engagement.showLikes')} (${post.likeCount})`
                            }}
                        </button>
                        <button
                            type="button"
                            class="cursor-pointer rounded-full border border-input px-3 py-1 text-xs font-medium text-foreground hover:bg-accent"
                            @click="toggleReposts(post)"
                        >
                            {{
                                ensureRepostsState(post.id).open
                                    ? t('bluesky.engagement.hideReposts')
                                    : `${t('bluesky.engagement.showReposts')} (${post.repostCount})`
                            }}
                        </button>
                        <button
                            type="button"
                            class="cursor-pointer rounded-full border border-input px-3 py-1 text-xs font-medium text-foreground hover:bg-accent"
                            @click="toggleThread(post)"
                        >
                            {{
                                ensureThreadState(post.id).open
                                    ? t('bluesky.engagement.hideThread')
                                    : `${t('bluesky.engagement.showThread')} (${post.replyCount})`
                            }}
                        </button>
                        <span class="rounded-full border border-input px-2 py-1">
                            {{ t('bluesky.metrics.quotes') }}: {{ post.quoteCount }}
                        </span>
                        <span class="rounded-full border border-input px-2 py-1">
                            {{ t('bluesky.metrics.postType') }}: {{ post.postType }}
                        </span>
                        <span
                            v-if="post.hasMedia"
                            class="rounded-full border border-cyan-400/30 bg-cyan-400/10 px-2 py-1 text-cyan-300"
                        >
                            {{ t('bluesky.metrics.media') }}
                        </span>
                    </div>

                    <div
                        v-if="post.hasMedia || post.media.type === 'external'"
                        class="mt-3 overflow-hidden rounded-lg border border-border/70 bg-card/60 p-3"
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
                                class="overflow-hidden rounded-md border border-border/70"
                            >
                                <img
                                    :src="image.thumb || image.fullsize"
                                    :alt="image.alt || `media-${index}`"
                                    class="h-56 w-full object-cover"
                                    loading="lazy"
                                />
                            </a>
                        </div>

                        <div
                            v-else-if="post.media.video.thumbnail"
                            class="space-y-2"
                        >
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

                        <div
                            v-else-if="post.media.external.uri"
                            class="flex gap-3"
                        >
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
                                    {{ post.media.external.title || post.media.external.uri }}
                                </a>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ post.media.external.description }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="post.languages.length > 0 || post.hashtags.length > 0 || post.domains.length > 0"
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

                    <div class="mt-3 rounded-lg border border-border/70 bg-card/50 p-3 text-xs">
                        <p class="mb-2 font-medium text-muted-foreground">
                            {{ t('bluesky.osint.title') }}
                        </p>
                        <div class="grid gap-2 md:grid-cols-2">
                            <p><span class="text-muted-foreground">DID:</span> {{ post.author.did || '-' }}</p>
                            <p><span class="text-muted-foreground">CID:</span> {{ post.cid || '-' }}</p>
                            <p><span class="text-muted-foreground">AT URI:</span> {{ post.uri || '-' }}</p>
                            <p><span class="text-muted-foreground">{{ t('bluesky.osint.indexedAt') }}:</span> {{ formatDate(post.indexedAt) }}</p>
                            <p><span class="text-muted-foreground">{{ t('bluesky.osint.replyRoot') }}:</span> {{ post.replyRootUri || '-' }}</p>
                            <p><span class="text-muted-foreground">{{ t('bluesky.osint.replyParent') }}:</span> {{ post.replyParentUri || '-' }}</p>
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

                        <div v-if="post.mentions.length > 0 || post.labels.length > 0" class="mt-3 grid gap-2 md:grid-cols-2">
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

                    <div
                        v-if="ensureLikesState(post.id).open"
                        class="mt-3 rounded-lg border border-border/70 bg-card/60 p-3"
                    >
                        <div class="mb-2">
                            <p class="text-xs font-medium">{{ t('bluesky.engagement.likes') }}</p>
                        </div>
                        <p v-if="ensureLikesState(post.id).loading" class="text-xs text-muted-foreground">
                            {{ t('bluesky.engagement.loading') }}
                        </p>
                        <p v-else-if="ensureLikesState(post.id).error" class="text-xs text-destructive">
                            {{ ensureLikesState(post.id).error }}
                        </p>
                        <div v-else-if="ensureLikesState(post.id).items.length === 0" class="text-xs text-muted-foreground">
                            {{ t('bluesky.engagement.emptyLikes') }}
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="item in ensureLikesState(post.id).items"
                                :key="`${post.id}-like-${item.actor.did}-${item.indexedAt}`"
                                class="rounded-md border border-border/70 bg-background/70 p-2"
                            >
                                <a
                                    :href="item.actor.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-xs font-medium text-primary hover:underline"
                                >
                                    {{ item.actor.displayName || item.actor.handle }}
                                </a>
                                <p class="text-xs text-muted-foreground">@{{ item.actor.handle }}</p>
                                <p v-if="item.createdAt || item.indexedAt" class="text-[11px] text-muted-foreground">
                                    {{ formatDate(item.createdAt || item.indexedAt) }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="ensureLikesState(post.id).hasMore"
                            class="pt-1"
                        >
                            <button
                                type="button"
                                :disabled="ensureLikesState(post.id).loadingMore"
                                class="cursor-pointer rounded-md border border-input bg-background px-3 py-2 text-xs font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                                @click="loadLikes(post, true)"
                            >
                                {{
                                    ensureLikesState(post.id).loadingMore
                                        ? t('bluesky.search.loadingMore')
                                        : t('bluesky.search.loadMore')
                                }}
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="ensureRepostsState(post.id).open"
                        class="mt-3 rounded-lg border border-border/70 bg-card/60 p-3"
                    >
                        <div class="mb-2">
                            <p class="text-xs font-medium">{{ t('bluesky.engagement.reposts') }}</p>
                        </div>
                        <p v-if="ensureRepostsState(post.id).loading" class="text-xs text-muted-foreground">
                            {{ t('bluesky.engagement.loading') }}
                        </p>
                        <p v-else-if="ensureRepostsState(post.id).error" class="text-xs text-destructive">
                            {{ ensureRepostsState(post.id).error }}
                        </p>
                        <div v-else-if="ensureRepostsState(post.id).items.length === 0" class="text-xs text-muted-foreground">
                            {{ t('bluesky.engagement.emptyReposts') }}
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="item in ensureRepostsState(post.id).items"
                                :key="`${post.id}-repost-${item.actor.did}-${item.indexedAt}`"
                                class="rounded-md border border-border/70 bg-background/70 p-2"
                            >
                                <a
                                    :href="item.actor.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-xs font-medium text-primary hover:underline"
                                >
                                    {{ item.actor.displayName || item.actor.handle }}
                                </a>
                                <p class="text-xs text-muted-foreground">@{{ item.actor.handle }}</p>
                                <p v-if="item.createdAt || item.indexedAt" class="text-[11px] text-muted-foreground">
                                    {{ formatDate(item.createdAt || item.indexedAt) }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="ensureRepostsState(post.id).hasMore"
                            class="pt-1"
                        >
                            <button
                                type="button"
                                :disabled="ensureRepostsState(post.id).loadingMore"
                                class="cursor-pointer rounded-md border border-input bg-background px-3 py-2 text-xs font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                                @click="loadReposts(post, true)"
                            >
                                {{
                                    ensureRepostsState(post.id).loadingMore
                                        ? t('bluesky.search.loadingMore')
                                        : t('bluesky.search.loadMore')
                                }}
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="ensureThreadState(post.id).open"
                        class="mt-3 rounded-lg border border-border/70 bg-card/60 p-3"
                    >
                        <p class="mb-2 text-xs font-medium">{{ t('bluesky.engagement.thread') }}</p>
                        <p v-if="ensureThreadState(post.id).loading" class="text-xs text-muted-foreground">
                            {{ t('bluesky.engagement.loading') }}
                        </p>
                        <p v-else-if="ensureThreadState(post.id).error" class="text-xs text-destructive">
                            {{ ensureThreadState(post.id).error }}
                        </p>
                        <div v-else class="space-y-2">
                            <div v-if="ensureThreadState(post.id).ancestors.length > 0" class="space-y-2">
                                <p class="text-xs font-medium text-muted-foreground">
                                    {{ t('bluesky.engagement.ancestors') }}
                                </p>
                                <div
                                    v-for="ancestor in ensureThreadState(post.id).ancestors"
                                    :key="`${post.id}-ancestor-${ancestor.id}`"
                                    class="rounded-md border border-border/70 bg-background/70 p-2 text-xs"
                                >
                                    <div class="mb-1 flex flex-wrap items-center gap-2">
                                        <span class="font-medium text-foreground">
                                            {{ ancestor.author.displayName || ancestor.author.handle }}
                                        </span>
                                        <span class="text-muted-foreground">
                                            @{{ ancestor.author.handle }}
                                        </span>
                                        <span class="text-muted-foreground">
                                            {{ formatDate(ancestor.createdAt) }}
                                        </span>
                                    </div>
                                    <p>{{ ancestor.text || t('bluesky.search.noText') }}</p>
                                </div>
                            </div>

                            <div v-if="ensureThreadState(post.id).replies.length > 0" class="space-y-2">
                                <p class="text-xs font-medium text-muted-foreground">
                                    {{ t('bluesky.engagement.replies') }}
                                </p>
                                <div
                                    v-for="reply in ensureThreadState(post.id).replies"
                                    :key="`${post.id}-reply-${reply.id}`"
                                    class="rounded-md border border-border/70 bg-background/70 p-2 text-xs"
                                >
                                    <div class="mb-1 flex flex-wrap items-center gap-2">
                                        <span class="font-medium text-foreground">
                                            {{ reply.author.displayName || reply.author.handle }}
                                        </span>
                                        <span class="text-muted-foreground">
                                            @{{ reply.author.handle }}
                                        </span>
                                        <span class="text-muted-foreground">
                                            {{ formatDate(reply.createdAt) }}
                                        </span>
                                    </div>
                                    <p>{{ reply.text || t('bluesky.search.noText') }}</p>
                                    <div
                                        v-if="reply.replies.length > 0"
                                        class="mt-2 border-l border-border pl-3"
                                    >
                                        <div
                                            v-for="child in reply.replies"
                                            :key="`${reply.id}-${child.id}`"
                                            class="mt-2 rounded-md border border-border/50 bg-background/60 p-2"
                                        >
                                            <div class="mb-1 flex flex-wrap items-center gap-2 text-[11px]">
                                                <span class="font-medium text-foreground">
                                                    {{ child.author.displayName || child.author.handle }}
                                                </span>
                                                <span class="text-muted-foreground">
                                                    @{{ child.author.handle }}
                                                </span>
                                                <span class="text-muted-foreground">
                                                    {{ formatDate(child.createdAt) }}
                                                </span>
                                            </div>
                                            <p class="text-muted-foreground">
                                                {{ child.text || t('bluesky.search.noText') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="
                                    ensureThreadState(post.id).ancestors.length === 0 &&
                                    ensureThreadState(post.id).replies.length === 0
                                "
                                class="text-xs text-muted-foreground"
                            >
                                {{ t('bluesky.engagement.emptyThread') }}
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <section v-if="result.actors.length > 0" class="space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                        {{ t('bluesky.sections.actors') }}
                    </h3>
                    <span class="text-xs text-muted-foreground">{{ result.actors.length }}</span>
                </div>

                <article
                    v-for="actor in result.actors"
                    :key="actor.did"
                    class="rounded-lg border border-border/80 bg-background/70 p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <a
                                :href="actor.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-sm font-semibold text-primary hover:underline"
                            >
                                {{ actor.displayName || actor.handle }}
                            </a>
                            <p class="text-xs text-muted-foreground">@{{ actor.handle }}</p>
                        </div>

                        <a
                            :href="actor.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="rounded-full border border-input px-2 py-1 text-xs text-primary hover:bg-accent"
                        >
                            {{ t('bluesky.common.openProfile') }}
                        </a>
                    </div>

                    <p class="mt-3 text-sm leading-relaxed text-foreground">
                        {{ actor.description || t('bluesky.search.noBio') }}
                    </p>

                    <div class="mt-3 flex flex-wrap gap-2 text-xs text-muted-foreground">
                        <span class="rounded-full border border-input px-2 py-1">
                            {{ t('bluesky.metrics.followers') }}: {{ actor.followersCount }}
                        </span>
                        <span class="rounded-full border border-input px-2 py-1">
                            {{ t('bluesky.metrics.following') }}: {{ actor.followsCount }}
                        </span>
                        <span class="rounded-full border border-input px-2 py-1">
                            {{ t('bluesky.metrics.posts') }}: {{ actor.postsCount }}
                        </span>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="cursor-pointer rounded-full border border-input px-3 py-1 text-xs font-medium text-foreground hover:bg-accent"
                            @click="toggleActorPosts(actor)"
                        >
                            {{
                                ensureActorDetailsState(actor.did).postsOpen
                                    ? t('bluesky.accounts.hidePosts')
                                    : t('bluesky.accounts.showPosts')
                            }}
                        </button>
                        <button
                            type="button"
                            class="cursor-pointer rounded-full border border-input px-3 py-1 text-xs font-medium text-foreground hover:bg-accent"
                            @click="toggleActorFollowers(actor)"
                        >
                            {{
                                ensureActorDetailsState(actor.did).followersOpen
                                    ? t('bluesky.accounts.hideFollowers')
                                    : t('bluesky.accounts.showFollowers')
                            }}
                        </button>
                        <button
                            type="button"
                            class="cursor-pointer rounded-full border border-input px-3 py-1 text-xs font-medium text-foreground hover:bg-accent"
                            @click="toggleActorFollows(actor)"
                        >
                            {{
                                ensureActorDetailsState(actor.did).followsOpen
                                    ? t('bluesky.accounts.hideFollows')
                                    : t('bluesky.accounts.showFollows')
                            }}
                        </button>
                    </div>

                    <div v-if="ensureActorDetailsState(actor.did).postsOpen" class="mt-3 rounded-lg border border-border/70 bg-card/60 p-3">
                        <p v-if="ensureActorDetailsState(actor.did).postsLoading" class="text-xs text-muted-foreground">
                            {{ t('bluesky.engagement.loading') }}
                        </p>
                        <p v-else-if="ensureActorDetailsState(actor.did).postsError" class="text-xs text-destructive">
                            {{ ensureActorDetailsState(actor.did).postsError }}
                        </p>
                        <div v-else class="space-y-2">
                            <div class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                                {{ t('bluesky.accounts.postsSection') }}
                            </div>
                            <p v-if="ensureActorDetailsState(actor.did).posts.length === 0" class="text-xs text-muted-foreground">
                                {{ t('bluesky.accounts.emptyPosts') }}
                            </p>
                            <article
                                v-for="post in ensureActorDetailsState(actor.did).posts"
                                :key="`${actor.did}-post-${post.id}`"
                                class="rounded-md border border-border/70 bg-background/70 p-3"
                            >
                                <div class="mb-1 text-[11px] text-muted-foreground">
                                    {{ formatDate(post.createdAt) }}
                                </div>
                                <p class="text-xs leading-relaxed text-foreground">
                                    {{ post.text || t('bluesky.search.noText') }}
                                </p>
                            </article>
                            <div v-if="ensureActorDetailsState(actor.did).postsHasMore" class="pt-1">
                                <button
                                    type="button"
                                    :disabled="ensureActorDetailsState(actor.did).postsLoadingMore"
                                    class="cursor-pointer rounded-md border border-input bg-background px-3 py-2 text-xs font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                                    @click="loadActorPosts(actor, true)"
                                >
                                    {{
                                        ensureActorDetailsState(actor.did).postsLoadingMore
                                            ? t('bluesky.search.loadingMore')
                                            : t('bluesky.search.loadMore')
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="ensureActorDetailsState(actor.did).followersOpen" class="mt-3 rounded-lg border border-border/70 bg-card/60 p-3">
                        <p v-if="ensureActorDetailsState(actor.did).followersLoading" class="text-xs text-muted-foreground">
                            {{ t('bluesky.engagement.loading') }}
                        </p>
                        <p v-else-if="ensureActorDetailsState(actor.did).followersError" class="text-xs text-destructive">
                            {{ ensureActorDetailsState(actor.did).followersError }}
                        </p>
                        <div v-else class="space-y-2">
                            <div class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                                {{ t('bluesky.accounts.followersSection') }}
                            </div>
                            <p v-if="ensureActorDetailsState(actor.did).followers.length === 0" class="text-xs text-muted-foreground">
                                {{ t('bluesky.accounts.emptyFollowers') }}
                            </p>
                            <article
                                v-for="follower in ensureActorDetailsState(actor.did).followers"
                                :key="`${actor.did}-follower-${follower.did}`"
                                class="rounded-md border border-border/70 bg-background/70 p-3"
                            >
                                <div class="text-xs font-medium text-foreground">
                                    {{ follower.displayName || follower.handle }}
                                </div>
                                <div class="text-[11px] text-muted-foreground">
                                    @{{ follower.handle }}
                                </div>
                            </article>
                            <div v-if="ensureActorDetailsState(actor.did).followersHasMore" class="pt-1">
                                <button
                                    type="button"
                                    :disabled="ensureActorDetailsState(actor.did).followersLoadingMore"
                                    class="cursor-pointer rounded-md border border-input bg-background px-3 py-2 text-xs font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                                    @click="loadActorFollowers(actor, true)"
                                >
                                    {{
                                        ensureActorDetailsState(actor.did).followersLoadingMore
                                            ? t('bluesky.search.loadingMore')
                                            : t('bluesky.search.loadMore')
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="ensureActorDetailsState(actor.did).followsOpen" class="mt-3 rounded-lg border border-border/70 bg-card/60 p-3">
                        <p v-if="ensureActorDetailsState(actor.did).followsLoading" class="text-xs text-muted-foreground">
                            {{ t('bluesky.engagement.loading') }}
                        </p>
                        <p v-else-if="ensureActorDetailsState(actor.did).followsError" class="text-xs text-destructive">
                            {{ ensureActorDetailsState(actor.did).followsError }}
                        </p>
                        <div v-else class="space-y-2">
                            <div class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                                {{ t('bluesky.accounts.followsSection') }}
                            </div>
                            <p v-if="ensureActorDetailsState(actor.did).follows.length === 0" class="text-xs text-muted-foreground">
                                {{ t('bluesky.accounts.emptyFollows') }}
                            </p>
                            <article
                                v-for="follow in ensureActorDetailsState(actor.did).follows"
                                :key="`${actor.did}-follow-${follow.did}`"
                                class="rounded-md border border-border/70 bg-background/70 p-3"
                            >
                                <div class="text-xs font-medium text-foreground">
                                    {{ follow.displayName || follow.handle }}
                                </div>
                                <div class="text-[11px] text-muted-foreground">
                                    @{{ follow.handle }}
                                </div>
                            </article>
                            <div v-if="ensureActorDetailsState(actor.did).followsHasMore" class="pt-1">
                                <button
                                    type="button"
                                    :disabled="ensureActorDetailsState(actor.did).followsLoadingMore"
                                    class="cursor-pointer rounded-md border border-input bg-background px-3 py-2 text-xs font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                                    @click="loadActorFollows(actor, true)"
                                >
                                    {{
                                        ensureActorDetailsState(actor.did).followsLoadingMore
                                            ? t('bluesky.search.loadingMore')
                                            : t('bluesky.search.loadMore')
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <div
                v-if="result.posts.length === 0 && result.actors.length === 0"
                class="intel-empty"
            >
                {{ t('bluesky.search.noMatches') }}
            </div>
        </div>
    </div>
</template>
