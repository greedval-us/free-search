import { computed, onMounted, reactive, ref } from 'vue';
import ParserRoutes from '@/actions/App/Http/Controllers/YouTube/YouTubeParserController';
import { useParserRun } from '@/composables/useParserRun';
import {
    getRepeatQueryParams,
    isRepeatAutorunEnabled,
    readRepeatQueryParam,
} from '@/composables/useRepeatQuery';
import type {
    YouTubeParserHistoryItem as ParserHistoryItem,
    YouTubeParserStatusResponse as ParserStatusResponse,
} from '../types';

type TranslateFn = (key: string) => string;

export const useYouTubeParser = (t: TranslateFn) => {
    const form = reactive({
        videoId: '',
    });
    const settingsCollapsed = ref(false);
    const parser = useParserRun<ParserStatusResponse, ParserHistoryItem>({
        routes: ParserRoutes,
        initialStage: 'comments',
        requestErrorMessage: () => t('youtube.parser.errors.failed'),
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
        processedComments: computed(
            () => parser.payload.value?.processedComments ?? 0
        ),
        processedReplies: computed(
            () => parser.payload.value?.processedReplies ?? 0
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
