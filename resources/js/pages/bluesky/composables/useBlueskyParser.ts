import { computed, reactive, ref } from 'vue';
import { useParserRun } from '@/composables/useParserRun';
import type {
    BlueskyParserHistoryItem as ParserHistoryItem,
    BlueskyParserStage as ParserStage,
    BlueskyParserStatusResponse as ParserStatusResponse,
} from '../types';

type TranslateFn = (key: string) => string;

export const useBlueskyParser = (t: TranslateFn) => {
    const form = reactive({
        actor: '',
    });
    const settingsCollapsed = ref(false);
    const processedPosts = ref(0);
    const processedAuthoredReplies = ref(0);
    const processedReceivedReplies = ref(0);
    const processedFollowers = ref(0);
    const processedFollows = ref(0);
    const processedReactions = ref(0);

    const parser = useParserRun<
        ParserStage,
        ParserStatusResponse,
        ParserHistoryItem
    >({
        endpointBase: '/bluesky/parser',
        idleStage: 'idle',
        initialStage: 'profile',
        stoppedStage: 'stopped',
        failedStage: 'failed',
        requestErrorMessage: () => t('bluesky.parser.errors.failed'),
        historyErrorMessage: () => t('bluesky.parser.history.errors.load'),
        applyModulePayload: (payload) => {
            processedPosts.value = payload.processedPosts;
            processedAuthoredReplies.value = payload.processedAuthoredReplies;
            processedReceivedReplies.value = payload.processedReceivedReplies;
            processedFollowers.value = payload.processedFollowers;
            processedFollows.value = payload.processedFollows;
            processedReactions.value = payload.processedReactions;
        },
        resetModuleState: () => {
            processedPosts.value = 0;
            processedAuthoredReplies.value = 0;
            processedReceivedReplies.value = 0;
            processedFollowers.value = 0;
            processedFollows.value = 0;
            processedReactions.value = 0;
        },
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
        processedPosts,
        processedAuthoredReplies,
        processedReceivedReplies,
        processedFollowers,
        processedFollows,
        processedReactions,
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
