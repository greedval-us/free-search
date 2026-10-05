<script setup lang="ts">
import type { ParserHistoryConfig } from '@/components/ui/parser/history';
import ParserHistoryPanel from '@/components/ui/parser/ParserHistoryPanel.vue';
import type { BlueskyParserHistoryItem } from '../../types';

defineProps<{
    items: BlueskyParserHistoryItem[];
    loading: boolean;
    retentionDays: number;
}>();

const emit = defineEmits<{
    download: [item: BlueskyParserHistoryItem];
    downloadJson: [item: BlueskyParserHistoryItem];
}>();

const config = {
    moduleKey: 'bluesky.parser',
    title: { key: 'actor' },
    stats: [
        { label: 'bluesky.parser.history.posts', key: 'processedPosts' },
        {
            label: 'bluesky.parser.history.authoredReplies',
            key: 'processedAuthoredReplies',
        },
        {
            label: 'bluesky.parser.history.receivedReplies',
            key: 'processedReceivedReplies',
        },
        {
            label: 'bluesky.parser.history.followers',
            key: 'processedFollowers',
        },
        { label: 'bluesky.parser.history.follows', key: 'processedFollows' },
        {
            label: 'bluesky.parser.history.reactions',
            key: 'processedReactions',
        },
    ],
} satisfies ParserHistoryConfig<BlueskyParserHistoryItem>;
</script>

<template>
    <ParserHistoryPanel
        :config="config"
        :items="items"
        :loading="loading"
        :retention-days="retentionDays"
        @download="emit('download', $event)"
        @download-json="emit('downloadJson', $event)"
    />
</template>
