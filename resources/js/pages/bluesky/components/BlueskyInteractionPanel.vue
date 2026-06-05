<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import type { BlueskyInteractionState, BlueskyPost } from '../types';

defineProps<{
    title: string;
    emptyText: string;
    post: BlueskyPost;
    state: BlueskyInteractionState;
    formatDate: (value: string) => string;
    loadMore: (post: BlueskyPost, append?: boolean) => void | Promise<void>;
}>();

const { t } = useI18n();
</script>

<template>
    <div class="intel-subsection mt-3">
        <div class="mb-2">
            <p class="text-xs font-medium">{{ title }}</p>
        </div>

        <p v-if="state.loading" class="text-xs text-muted-foreground">
            {{ t('bluesky.engagement.loading') }}
        </p>

        <p v-else-if="state.error" class="text-xs text-destructive">
            {{ state.error }}
        </p>

        <div
            v-else-if="state.items.length === 0"
            class="text-xs text-muted-foreground"
        >
            {{ emptyText }}
        </div>

        <div v-else class="space-y-2">
            <div
                v-for="item in state.items"
                :key="`${post.id}-${item.actor.did}-${item.indexedAt}`"
                class="intel-stat"
            >
                <a
                    :href="item.actor.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-xs font-medium text-primary hover:underline"
                >
                    {{ item.actor.displayName || item.actor.handle }}
                </a>
                <p class="text-xs text-muted-foreground">
                    @{{ item.actor.handle }}
                </p>
                <p
                    v-if="item.createdAt || item.indexedAt"
                    class="text-[11px] text-muted-foreground"
                >
                    {{ formatDate(item.createdAt || item.indexedAt) }}
                </p>
            </div>
        </div>

        <div v-if="state.hasMore" class="pt-1">
            <button
                type="button"
                :disabled="state.loadingMore"
                class="intel-expand-button"
                @click="loadMore(post, true)"
            >
                {{
                    state.loadingMore
                        ? t('bluesky.search.loadingMore')
                        : t('bluesky.search.loadMore')
                }}
            </button>
        </div>
    </div>
</template>
