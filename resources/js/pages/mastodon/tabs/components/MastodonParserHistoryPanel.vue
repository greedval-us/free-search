<script setup lang="ts">
import type { ParserHistoryConfig } from '@/components/ui/parser/history';
import ParserHistoryPanel from '@/components/ui/parser/ParserHistoryPanel.vue';
import type { MastodonParserHistoryItem } from '../../types';

defineProps<{
    items: MastodonParserHistoryItem[];
    loading: boolean;
    retentionDays: number;
}>();

const emit = defineEmits<{
    download: [item: MastodonParserHistoryItem];
    downloadJson: [item: MastodonParserHistoryItem];
}>();

const config = {
    moduleKey: 'mastodon.parser',
    title: { key: 'account' },
    stats: [
        { label: 'mastodon.parser.history.statuses', key: 'processedStatuses' },
        { label: 'mastodon.parser.history.comments', key: 'processedComments' },
    ],
} satisfies ParserHistoryConfig<MastodonParserHistoryItem>;
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
