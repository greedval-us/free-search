<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import type { BlueskySearchPayload } from '../types';

defineProps<{
    result: BlueskySearchPayload | null;
    loading: boolean;
    totalShown: number;
    formatDate: (value: string) => string;
}>();

const { t } = useI18n();
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
                        <span class="rounded-full border border-input px-2 py-1">
                            {{ t('bluesky.metrics.likes') }}: {{ post.likeCount }}
                        </span>
                        <span class="rounded-full border border-input px-2 py-1">
                            {{ t('bluesky.metrics.reposts') }}: {{ post.repostCount }}
                        </span>
                        <span class="rounded-full border border-input px-2 py-1">
                            {{ t('bluesky.metrics.replies') }}: {{ post.replyCount }}
                        </span>
                        <span class="rounded-full border border-input px-2 py-1">
                            {{ t('bluesky.metrics.quotes') }}: {{ post.quoteCount }}
                        </span>
                        <span
                            v-if="post.hasMedia"
                            class="rounded-full border border-cyan-400/30 bg-cyan-400/10 px-2 py-1 text-cyan-300"
                        >
                            {{ t('bluesky.metrics.media') }}
                        </span>
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
