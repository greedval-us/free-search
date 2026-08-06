<script setup lang="ts">
import { computed } from 'vue';
import type {
    ParserHistoryField,
    ParserHistoryStat,
} from '@/components/ui/parser/history';
import ParserHistoryPanel from '@/components/ui/parser/ParserHistoryPanel.vue';
import { useI18n } from '@/composables/useI18n';
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

const { t } = useI18n();

const detailFields = computed<ParserHistoryField<MastodonParserHistoryItem>[]>(
    () => [
        {
            label: t('mastodon.parser.account'),
            value: (item) => item.account,
        },
    ]
);

const statFields = computed<ParserHistoryStat<MastodonParserHistoryItem>[]>(
    () => [
        {
            label: 'mastodon.parser.history.statuses',
            value: (item) => item.processedStatuses,
        },
        {
            label: 'mastodon.parser.history.comments',
            value: (item) => item.processedComments,
        },
    ]
);
</script>

<template>
    <ParserHistoryPanel
        module-key="mastodon.parser"
        :items="items"
        :loading="loading"
        :retention-days="retentionDays"
        :title="(item) => item.account"
        :detail-fields="detailFields"
        :stat-fields="statFields"
        @download="emit('download', $event)"
        @download-json="emit('downloadJson', $event)"
    />
</template>
