import { computed, reactive, ref } from 'vue';
import { useParserRun } from '@/composables/useParserRun';
import type {
    MastodonParserHistoryItem as ParserHistoryItem,
    MastodonParserStage as ParserStage,
    MastodonParserStatusResponse as ParserStatusResponse,
} from '../types';

type TranslateFn = (key: string) => string;

export const useMastodonParser = (t: TranslateFn) => {
    const form = reactive({
        account: '',
    });
    const settingsCollapsed = ref(false);
    const processedStatuses = ref(0);
    const processedComments = ref(0);

    const parser = useParserRun<
        ParserStage,
        ParserStatusResponse,
        ParserHistoryItem
    >({
        endpointBase: '/mastodon/parser',
        idleStage: 'idle',
        initialStage: 'statuses',
        stoppedStage: 'stopped',
        failedStage: 'failed',
        requestErrorMessage: () => t('mastodon.parser.errors.failed'),
        historyErrorMessage: () => t('mastodon.parser.history.errors.load'),
        applyModulePayload: (payload) => {
            processedStatuses.value = payload.processedStatuses;
            processedComments.value = payload.processedComments;
        },
        resetModuleState: () => {
            processedStatuses.value = 0;
            processedComments.value = 0;
        },
    });

    const canStart = computed(
        () => form.account.trim().length > 0 && !parser.loading.value
    );

    const start = async () => {
        if (!canStart.value) {
            parser.error.value = t('mastodon.parser.errors.accountRequired');

            return;
        }

        await parser.startRun({
            account: form.account.trim(),
        });
    };

    return {
        form,
        settingsCollapsed,
        loading: parser.loading,
        error: parser.error,
        progress: parser.progress,
        stage: parser.stage,
        processedStatuses,
        processedComments,
        downloadUrl: parser.downloadUrl,
        downloadJsonUrl: parser.downloadJsonUrl,
        historyItems: parser.historyItems,
        historyLoading: parser.historyLoading,
        historyRetentionDays: parser.historyRetentionDays,
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
