<script setup lang="ts">
import { computed } from 'vue';
import type {
    ParserHistoryField,
    ParserHistoryStat,
} from '@/components/ui/parser/history';
import ParserHistoryPanel from '@/components/ui/parser/ParserHistoryPanel.vue';
import { useI18n } from '@/composables/useI18n';
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

const { t } = useI18n();

const historyPeriodLabel = (value: string | null) => {
    if (!value) {
        return '-';
    }

    const key = `telegram.parser.periods.${value}`;
    const translated = t(key);

    return translated === key ? value : translated;
};

const detailFields = computed<ParserHistoryField<TelegramParserHistoryItem>[]>(
    () => [
        {
            label: t('telegram.parser.history.period'),
            value: (item) => historyPeriodLabel(item.period),
        },
        {
            label: t('telegram.parser.history.keyword'),
            value: (item) => item.keyword,
        },
    ]
);

const statFields = computed<ParserHistoryStat<TelegramParserHistoryItem>[]>(
    () => [
        {
            label: 'telegram.parser.history.messages',
            value: (item) => item.processedMessages,
        },
        {
            label: 'telegram.parser.history.comments',
            value: (item) => item.processedComments,
        },
    ]
);
</script>

<template>
    <ParserHistoryPanel
        module-key="telegram.parser"
        :items="items"
        :loading="loading"
        :retention-days="retentionDays"
        :title="(item) => (item.chatUsername ? `@${item.chatUsername}` : null)"
        :detail-fields="detailFields"
        :stat-fields="statFields"
        @download="emit('download', $event)"
        @download-json="emit('downloadJson', $event)"
    />
</template>
