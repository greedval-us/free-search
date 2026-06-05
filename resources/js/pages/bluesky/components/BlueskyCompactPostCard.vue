<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import type { BlueskyPost } from '../types';

defineProps<{
    post: BlueskyPost;
    formatDate: (value: string) => string;
}>();

const { t } = useI18n();
</script>

<template>
    <article class="rounded-lg border border-border/70 bg-background/70 p-4">
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

        <div class="mt-3 flex flex-wrap gap-2 text-xs text-muted-foreground">
            <span class="intel-pill">
                {{ t('bluesky.metrics.likes') }}: {{ post.likeCount }}
            </span>
            <span class="intel-pill">
                {{ t('bluesky.metrics.reposts') }}: {{ post.repostCount }}
            </span>
            <span class="intel-pill">
                {{ t('bluesky.metrics.replies') }}: {{ post.replyCount }}
            </span>
            <span class="intel-pill">
                {{ t('bluesky.metrics.quotes') }}: {{ post.quoteCount }}
            </span>
        </div>
    </article>
</template>
