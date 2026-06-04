<script setup lang="ts">
import type { MastodonStatusThreadNode } from '../types';

defineOptions({
    name: 'MastodonReplyThread',
});

const props = defineProps<{
    items: MastodonStatusThreadNode[];
    noTextLabel: string;
}>();

const formatDate = (value: string) =>
    value ? new Date(value).toLocaleString() : '-';
</script>

<template>
    <div class="space-y-2">
        <article v-for="item in props.items" :key="item.id" class="intel-stat">
            <div class="mb-1 text-[11px] text-muted-foreground">
                @{{ item.account.acct }} |
                {{ item.instanceDomain || item.account.instanceDomain }} |
                {{ formatDate(item.createdAt) }}
            </div>
            <p class="text-xs leading-relaxed text-foreground">
                {{ item.content || props.noTextLabel }}
            </p>

            <div
                v-if="item.replies.length > 0"
                class="mt-2 border-l border-border/80 pl-3"
            >
                <MastodonReplyThread
                    :items="item.replies"
                    :no-text-label="props.noTextLabel"
                />
            </div>
        </article>
    </div>
</template>
