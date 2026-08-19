import { computed, onMounted, reactive, ref } from 'vue';
import { useParserRun } from '@/composables/useParserRun';
import {
    getRepeatQueryParams,
    isRepeatAutorunEnabled,
    readRepeatQueryParam,
} from '@/composables/useRepeatQuery';
import type {
    YouTubeParserHistoryItem as ParserHistoryItem,
    YouTubeParserStage as ParserStage,
    YouTubeParserStatusResponse as ParserStatusResponse,
} from '../types';

type TranslateFn = (key: string) => string;

export const useYouTubeParser = (t: TranslateFn) => {
    const form = reactive({
        videoId: '',
    });
    const settingsCollapsed = ref(false);
    const processedComments = ref(0);
    const processedReplies = ref(0);

    const parser = useParserRun<
        ParserStage,
        ParserStatusResponse,
        ParserHistoryItem
    >({
        endpointBase: '/youtube/parser',
        idleStage: 'idle',
        initialStage: 'comments',
        stoppedStage: 'stopped',
        failedStage: 'failed',
        requestErrorMessage: () => t('youtube.parser.errors.failed'),
        historyErrorMessage: () => t('youtube.parser.history.errors.load'),
        applyModulePayload: (payload) => {
            processedComments.value = payload.processedComments;
            processedReplies.value = payload.processedReplies;
        },
        resetModuleState: () => {
            processedComments.value = 0;
            processedReplies.value = 0;
        },
    });

    const canStart = computed(
        () => form.videoId.trim().length > 0 && !parser.loading.value
    );

    const start = async () => {
        if (!canStart.value) {
            parser.error.value = t('youtube.parser.errors.videoRequired');

            return;
        }

        await parser.startRun({
            videoId: form.videoId.trim(),
        });
    };

    onMounted(() => {
        const params = getRepeatQueryParams();

        if (!params || readRepeatQueryParam(params, ['tab']) !== 'parser') {
            return;
        }

        const videoId = readRepeatQueryParam(params, ['videoId']);

        if (videoId !== '') {
            form.videoId = videoId;
        }

        if (isRepeatAutorunEnabled(params) && form.videoId.trim() !== '') {
            void start();
        }
    });

    return {
        form,
        settingsCollapsed,
        loading: parser.loading,
        error: parser.error,
        progress: parser.progress,
        stage: parser.stage,
        processedComments,
        processedReplies,
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
