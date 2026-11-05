import { computed, reactive, ref } from 'vue';
import ParserRoutes from '@/actions/App/Http/Controllers/Mastodon/MastodonParserController';
import { useParserRun } from '@/composables/useParserRun';
import type {
    MastodonParserHistoryItem as ParserHistoryItem,
    MastodonParserStatusResponse as ParserStatusResponse,
} from '../types';

type TranslateFn = (key: string) => string;

export const useMastodonParser = (t: TranslateFn) => {
    const form = reactive({
        account: '',
    });
    const settingsCollapsed = ref(false);
    const parser = useParserRun<ParserStatusResponse, ParserHistoryItem>({
        routes: ParserRoutes,
        initialStage: 'statuses',
        requestErrorMessage: () => t('mastodon.parser.errors.failed'),
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
        processedStatuses: computed(
            () => parser.payload.value?.processedStatuses ?? 0
        ),
        processedComments: computed(
            () => parser.payload.value?.processedComments ?? 0
        ),
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
