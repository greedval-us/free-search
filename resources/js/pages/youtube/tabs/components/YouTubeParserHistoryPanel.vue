<script setup lang="ts">
import { computed } from 'vue';
import type {
    ParserHistoryField,
    ParserHistoryStat,
} from '@/components/ui/parser/history';
import ParserHistoryPanel from '@/components/ui/parser/ParserHistoryPanel.vue';
import { useI18n } from '@/composables/useI18n';
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

const { t } = useI18n();

const detailFields = computed<ParserHistoryField<YouTubeParserHistoryItem>[]>(
    () => [
        {
            label: t('youtube.parser.videoId'),
            value: (item) => item.videoId,
        },
    ]
);

const statFields = computed<ParserHistoryStat<YouTubeParserHistoryItem>[]>(
    () => [
        {
            label: 'youtube.parser.history.comments',
            value: (item) => item.processedComments,
        },
        {
            label: 'youtube.parser.history.replies',
            value: (item) => item.processedReplies,
        },
    ]
);
</script>

<template>
    <ParserHistoryPanel
        module-key="youtube.parser"
        :items="items"
        :loading="loading"
        :retention-days="retentionDays"
        :title="(item) => item.videoId"
        :detail-fields="detailFields"
        :stat-fields="statFields"
        @download="emit('download', $event)"
        @download-json="emit('downloadJson', $event)"
    />
</template>
