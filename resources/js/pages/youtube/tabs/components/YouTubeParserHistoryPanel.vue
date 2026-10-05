<script setup lang="ts">
import type { ParserHistoryConfig } from '@/components/ui/parser/history';
import ParserHistoryPanel from '@/components/ui/parser/ParserHistoryPanel.vue';
import type { YouTubeParserHistoryItem } from '../../types';

defineProps<{
    items: YouTubeParserHistoryItem[];
    loading: boolean;
    retentionDays: number;
}>();

const emit = defineEmits<{
    download: [item: YouTubeParserHistoryItem];
    downloadJson: [item: YouTubeParserHistoryItem];
}>();

const config = {
    moduleKey: 'youtube.parser',
    title: { key: 'videoId' },
    stats: [
        { label: 'youtube.parser.history.comments', key: 'processedComments' },
        { label: 'youtube.parser.history.replies', key: 'processedReplies' },
    ],
} satisfies ParserHistoryConfig<YouTubeParserHistoryItem>;
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
