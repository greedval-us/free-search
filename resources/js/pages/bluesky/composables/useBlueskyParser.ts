import { computed, reactive, ref } from 'vue';
import ParserRoutes from '@/actions/App/Http/Controllers/Bluesky/BlueskyParserController';
import { useParserRun } from '@/composables/useParserRun';
import type {
    BlueskyParserHistoryItem as ParserHistoryItem,
    BlueskyParserStatusResponse as ParserStatusResponse,
} from '../types';

type TranslateFn = (key: string) => string;

export const useBlueskyParser = (t: TranslateFn) => {
    const form = reactive({
        actor: '',
    });
    const settingsCollapsed = ref(false);
    const parser = useParserRun<ParserStatusResponse, ParserHistoryItem>({
        routes: ParserRoutes,
        initialStage: 'profile',
        requestErrorMessage: () => t('bluesky.parser.errors.failed'),
    });

    const canStart = computed(
        () => form.actor.trim().length > 0 && !parser.loading.value
    );

    const start = async () => {
        if (!canStart.value) {
            parser.error.value = t('bluesky.parser.errors.actorRequired');

            return;
        }

        await parser.startRun({
            actor: form.actor.trim(),
        });
    };

    return {
        form,
        settingsCollapsed,
        loading: parser.loading,
        error: parser.error,
        progress: parser.progress,
        stage: parser.stage,
        processedPosts: computed(
            () => parser.payload.value?.processedPosts ?? 0
        ),
        processedAuthoredReplies: computed(
            () => parser.payload.value?.processedAuthoredReplies ?? 0
        ),
        processedReceivedReplies: computed(
            () => parser.payload.value?.processedReceivedReplies ?? 0
        ),
        processedFollowers: computed(
            () => parser.payload.value?.processedFollowers ?? 0
        ),
        processedFollows: computed(
            () => parser.payload.value?.processedFollows ?? 0
        ),
        processedReactions: computed(
            () => parser.payload.value?.processedReactions ?? 0
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
