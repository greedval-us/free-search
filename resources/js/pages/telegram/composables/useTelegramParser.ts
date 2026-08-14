import { computed, reactive, ref } from 'vue';
import { useParserRun } from '@/composables/useParserRun';
import type {
    TelegramParserHistoryItem as ParserHistoryItem,
    TelegramParserPeriod,
    TelegramParserStage as ParserStage,
    TelegramParserStatusResponse as ParserStatusResponse,
} from '../types';

type TranslateFn = (key: string) => string;

const DAY_IN_MS = 24 * 60 * 60 * 1000;

const parseDate = (value: string): Date | null => {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);

    if (!match) {
        return null;
    }

    return new Date(
        Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3]))
    );
};

const diffDays = (from: string, to: string): number | null => {
    const fromDate = parseDate(from);
    const toDate = parseDate(to);

    if (!fromDate || !toDate) {
        return null;
    }

    return Math.floor((toDate.getTime() - fromDate.getTime()) / DAY_IN_MS);
};

export const useTelegramParser = (t: TranslateFn) => {
    const form = reactive({
        chatUsername: '',
        keyword: '',
        period: 'week' as TelegramParserPeriod,
        dateFrom: '',
        dateTo: '',
    });
    const settingsCollapsed = ref(false);
    const processedMessages = ref(0);
    const processedComments = ref(0);

    const parser = useParserRun<
        ParserStage,
        ParserStatusResponse,
        ParserHistoryItem
    >({
        endpointBase: '/telegram/parser',
        idleStage: 'idle',
        initialStage: 'messages',
        stoppedStage: 'stopped',
        failedStage: 'failed',
        requestErrorMessage: () => t('telegram.parser.errors.failed'),
        historyErrorMessage: () => t('telegram.parser.history.errors.load'),
        applyModulePayload: (payload) => {
            processedMessages.value = payload.processedMessages;
            processedComments.value = payload.processedComments;
        },
        resetModuleState: () => {
            processedMessages.value = 0;
            processedComments.value = 0;
        },
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

        const days = diffDays(form.dateFrom, form.dateTo);

        if (days === null || days < 0) {
            parser.error.value = t('telegram.parser.errors.customInvalid');

            return false;
        }

        if (days > 30) {
            parser.error.value = t('telegram.parser.errors.customTooLong');

            return false;
        }

        return true;
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
        processedMessages,
        processedComments,
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
