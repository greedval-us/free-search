<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import { useLocaleFormatters } from '@/composables/useLocaleFormatters';
import type { YouTubeComment } from '../../types';

defineProps<{
    comment: YouTubeComment;
    authorUrl?: string;
    replyCount?: number;
}>();
const { locale } = useI18n();
const { formatNumber: fmt, formatDate } = useLocaleFormatters(locale);
</script>

<template>
    <article class="intel-list-card">
        <div
            class="mb-1 flex flex-wrap items-center gap-2 text-[11px] text-muted-foreground"
        >
            <a
                v-if="authorUrl"
                :href="authorUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="break-words text-primary hover:underline"
                >{{ comment.author }}</a
            >
            <span v-else>{{ comment.author }}</span>
            <span>{{ formatDate(comment.publishedAt) }}</span>
            <span>👍 {{ fmt(comment.likeCount) }}</span>
            <span v-if="replyCount !== undefined"
                >💬 {{ fmt(replyCount) }}</span
            >
        </div>
        <p class="text-xs leading-relaxed text-foreground">
            {{ comment.text }}
        </p>
        <slot />
    </article>
</template>
