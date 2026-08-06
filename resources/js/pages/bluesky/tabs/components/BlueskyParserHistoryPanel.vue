<script setup lang="ts">
import { computed } from 'vue';
import type {
    ParserHistoryField,
    ParserHistoryStat,
} from '@/components/ui/parser/history';
import ParserHistoryPanel from '@/components/ui/parser/ParserHistoryPanel.vue';
import { useI18n } from '@/composables/useI18n';
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

const { t } = useI18n();

const detailFields = computed<ParserHistoryField<BlueskyParserHistoryItem>[]>(
    () => [
        {
            label: t('bluesky.parser.actor'),
            value: (item) => item.actor,
        },
    ]
);

const statFields = computed<ParserHistoryStat<BlueskyParserHistoryItem>[]>(
    () => [
        {
            label: 'bluesky.parser.history.posts',
            value: (item) => item.processedPosts,
        },
        {
            label: 'bluesky.parser.history.authoredReplies',
            value: (item) => item.processedAuthoredReplies,
        },
        {
            label: 'bluesky.parser.history.receivedReplies',
            value: (item) => item.processedReceivedReplies,
        },
        {
            label: 'bluesky.parser.history.followers',
            value: (item) => item.processedFollowers,
        },
        {
            label: 'bluesky.parser.history.follows',
            value: (item) => item.processedFollows,
        },
        {
            label: 'bluesky.parser.history.reactions',
            value: (item) => item.processedReactions,
        },
    ]
);
</script>

<template>
    <ParserHistoryPanel
        module-key="bluesky.parser"
        :items="items"
        :loading="loading"
        :retention-days="retentionDays"
        :title="(item) => item.actor"
        :detail-fields="detailFields"
        :stat-fields="statFields"
        @download="emit('download', $event)"
        @download-json="emit('downloadJson', $event)"
    />
</template>
