import { computed, reactive, ref } from 'vue';
import ParserRoutes from '@/actions/App/Http/Controllers/Telegram/TelegramParserController';
import type { Translate } from '@/composables/useI18n';
import { useParserRun } from '@/composables/useParserRun';
import { customRangeError } from '../date-range';
import type {
    TelegramParserHistoryItem as ParserHistoryItem,
    TelegramParserPeriod,
    TelegramParserStatusResponse as ParserStatusResponse,
} from '../types';

export const useTelegramParser = (
    t: Translate,
    customRangeMaxDays: () => number
) => {
    const form = reactive({
        chatUsername: '',
        keyword: '',
        period: 'week' as TelegramParserPeriod,
        dateFrom: '',
        dateTo: '',
    });
    const settingsCollapsed = ref(false);
    const parser = useParserRun<ParserStatusResponse, ParserHistoryItem>({
        routes: ParserRoutes,
        initialStage: 'messages',
        requestErrorMessage: () => t('telegram.parser.errors.failed'),
    });

    const keywordActive = computed(() => form.keyword.trim().length > 0);
    const customPeriod = computed(() => form.period === 'custom');
    const canStart = computed(
        () => form.chatUsername.trim().length > 0 && !parser.loading.value
    );

    const validateCustomPeriod = (): boolean => {
        if (!customPeriod.value || keywordActive.value) {
            return true;
        }

        if (!form.dateFrom || !form.dateTo) {
            parser.error.value = t('telegram.parser.errors.customBothDates');

            return false;
        }

        parser.error.value = customRangeError(
            form.dateFrom,
            form.dateTo,
            customRangeMaxDays(),
            t
        );

        return parser.error.value === null;
    };

    const start = async () => {
        if (!canStart.value || !validateCustomPeriod()) {
            return;
        }

        await parser.startRun({
            chatUsername: form.chatUsername.trim(),
            keyword: form.keyword.trim(),
            period: form.period,
            dateFrom: form.dateFrom,
            dateTo: form.dateTo,
        });
    };

    return {
        form,
        settingsCollapsed,
        loading: parser.loading,
        error: parser.error,
        runId: parser.runId,
        progress: parser.progress,
        stage: parser.stage,
        processedMessages: computed(
            () => parser.payload.value?.processedMessages ?? 0
        ),
        processedComments: computed(
            () => parser.payload.value?.processedComments ?? 0
        ),
        downloadUrl: parser.downloadUrl,
        downloadJsonUrl: parser.downloadJsonUrl,
        historyItems: parser.historyItems,
        historyLoading: parser.historyLoading,
        historyRetentionDays: parser.historyRetentionDays,
        keywordActive,
        customPeriod,
        canStart,
        start,
        stop: parser.stop,
        download: parser.download,
        downloadJson: parser.downloadJson,
        refreshHistory: parser.refreshHistory,
        downloadHistoryRun: parser.downloadHistoryRun,
        downloadHistoryRunJson: parser.downloadHistoryRunJson,
    };
};
