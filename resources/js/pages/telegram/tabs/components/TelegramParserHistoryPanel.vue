<script setup lang="ts">
import type { ParserHistoryConfig } from '@/components/ui/parser/history';
import ParserHistoryPanel from '@/components/ui/parser/ParserHistoryPanel.vue';
import type { TelegramParserHistoryItem } from '../../types';

defineProps<{
    items: TelegramParserHistoryItem[];
    loading: boolean;
    retentionDays: number;
}>();

const emit = defineEmits<{
    download: [item: TelegramParserHistoryItem];
    downloadJson: [item: TelegramParserHistoryItem];
}>();

const config = {
    moduleKey: 'telegram.parser',
    title: { key: 'chatUsername', prefix: '@' },
    details: [
        {
            label: 'telegram.parser.history.period',
            key: 'period',
            translationPrefix: 'telegram.parser.periods',
        },
        { label: 'telegram.parser.history.keyword', key: 'keyword' },
    ],
    stats: [
        { label: 'telegram.parser.history.messages', key: 'processedMessages' },
        { label: 'telegram.parser.history.comments', key: 'processedComments' },
    ],
} satisfies ParserHistoryConfig<TelegramParserHistoryItem>;
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
