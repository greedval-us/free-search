import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import {
    getRepeatQueryParams,
    isRepeatAutorunEnabled,
    readRepeatQueryParam,
} from '@/composables/useRepeatQuery';
import { apiRequest, resolveClientErrorMessage } from '@/lib/api';
import { withDownloadLocale } from '@/lib/downloadLocale';
import type {
    YouTubeParserHistoryItem as ParserHistoryItem,
    YouTubeParserHistoryResponse as ParserHistoryResponse,
    YouTubeParserStage as ParserStage,
    YouTubeParserStatusResponse as ParserStatusResponse,
} from '../types';

type TranslateFn = (key: string) => string;

const POLL_INTERVAL_MS = 3000;
const parserEndpoint = (suffix: string) => `/youtube/parser/${suffix}`;
const csrfToken = () =>
    document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
        ?.content ?? '';

export const useYouTubeParser = (t: TranslateFn) => {
    const form = reactive({
        videoId: '',
    });

    const settingsCollapsed = ref(false);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const runId = ref<string | null>(null);
    const progress = ref(0);
    const stage = ref<ParserStage>('idle');
    const processedComments = ref(0);
    const processedReplies = ref(0);
    const downloadUrl = ref<string | null>(null);
    const downloadJsonUrl = ref<string | null>(null);
    const historyItems = ref<ParserHistoryItem[]>([]);
    const historyLoading = ref(false);
    const historyRetentionDays = ref(7);
    const pollTimer = ref<number | null>(null);
    const pollRequestInFlight = ref(false);

    const canStart = computed(
        () => form.videoId.trim().length > 0 && !loading.value
    );

    const resetState = () => {
        runId.value = null;
        progress.value = 0;
        stage.value = 'idle';
        processedComments.value = 0;
        processedReplies.value = 0;
        downloadUrl.value = null;
        downloadJsonUrl.value = null;
    };

    const clearPolling = () => {
        if (pollTimer.value !== null) {
            window.clearTimeout(pollTimer.value);
            pollTimer.value = null;
        }

        pollRequestInFlight.value = false;
    };

    const applyStatusPayload = (payload: ParserStatusResponse) => {
        stage.value = payload.stage;
        progress.value = payload.progress;
        processedComments.value = payload.processedComments;
        processedReplies.value = payload.processedReplies;
        error.value = payload.error;
        downloadUrl.value = payload.downloadUrl;
        downloadJsonUrl.value = payload.downloadJsonUrl;
    };

    const refreshHistory = async () => {
        historyLoading.value = true;

        try {
            const response = await apiRequest<ParserHistoryResponse>(
                parserEndpoint('history'),
                { method: 'GET' }
            );

            if (!response.ok) {
                throw new Error(
                    response.message ?? t('youtube.parser.history.errors.load')
                );
            }

            historyItems.value = response.data.items;
            historyRetentionDays.value = response.data.retentionDays;
        } catch {
            historyItems.value = [];
        } finally {
            historyLoading.value = false;
        }
    };

    const requestStop = async (
        activeRunId: string
    ): Promise<ParserStatusResponse | null> => {
        const response = await apiRequest<ParserStatusResponse>(
            parserEndpoint(`stop/${activeRunId}`),
            {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                },
            }
        );

        return response.ok ? response.data : null;
    };

    const downloadByUrl = (url: string | null) => {
        if (!url) {
            return;
        }

        window.location.href = withDownloadLocale(url);
    };

    const finalizeRun = async (payload: ParserStatusResponse) => {
        applyStatusPayload(payload);
        loading.value = false;
        clearPolling();
        await refreshHistory();
    };

    const stopSilently = () => {
        clearPolling();
        const activeRunId = runId.value;

        if (activeRunId) {
            requestStop(activeRunId).catch(() => undefined);
        }
    };

    const stop = () => {
        clearPolling();
        loading.value = false;

        if (stage.value !== 'completed' && stage.value !== 'failed') {
            stage.value = 'stopped';
        }

        const activeRunId = runId.value;

        if (!activeRunId) {
            return;
        }

        requestStop(activeRunId)
            .then((payload) => {
                if (!payload || payload.runId !== runId.value) {
                    return;
                }

                applyStatusPayload(payload);
                void refreshHistory();
            })
            .catch(() => undefined);
    };

    const start = async () => {
        if (!canStart.value) {
            error.value = t('youtube.parser.errors.videoRequired');

            return;
        }

        stopSilently();
        resetState();
        error.value = null;
        loading.value = true;
        stage.value = 'comments';
        progress.value = 1;

        try {
            const response = await apiRequest<ParserStatusResponse>(
                parserEndpoint('start'),
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: {
                        videoId: form.videoId.trim(),
                    },
                }
            );

            if (!response.ok || !response.data.runId) {
                throw new Error(
                    response.message ?? t('youtube.parser.errors.failed')
                );
            }

            const payload = response.data;
            runId.value = payload.runId;
            applyStatusPayload(payload);
            void refreshHistory();

            const pollStatus = async () => {
                if (!runId.value || pollRequestInFlight.value) {
                    return;
                }

                pollRequestInFlight.value = true;

                try {
                    const statusResponse =
                        await apiRequest<ParserStatusResponse>(
                            parserEndpoint(`status/${runId.value}`),
                            {
                                method: 'GET',
                            }
                        );

                    if (!statusResponse.ok) {
                        throw new Error(
                            statusResponse.message ??
                                t('youtube.parser.errors.failed')
                        );
                    }

                    const statusPayload = statusResponse.data;
                    applyStatusPayload(statusPayload);

                    if (
                        statusPayload.status === 'completed' ||
                        statusPayload.status === 'failed' ||
                        statusPayload.status === 'stopped'
                    ) {
                        await finalizeRun(statusPayload);

                        return;
                    }
                } catch (pollError) {
                    loading.value = false;
                    error.value = resolveClientErrorMessage(
                        pollError,
                        t('youtube.parser.errors.failed')
                    );
                    clearPolling();

                    return;
                } finally {
                    pollRequestInFlight.value = false;
                }

                pollTimer.value = window.setTimeout(() => {
                    void pollStatus();
                }, POLL_INTERVAL_MS);
            };

            pollTimer.value = window.setTimeout(() => {
                void pollStatus();
            }, POLL_INTERVAL_MS);
        } catch (exception) {
            stage.value = 'failed';
            error.value = resolveClientErrorMessage(
                exception,
                t('youtube.parser.errors.failed')
            );
        } finally {
            if (stage.value === 'failed') {
                loading.value = false;
            }
        }
    };

    const download = () => {
        downloadByUrl(downloadUrl.value);
    };

    const downloadJson = () => {
        downloadByUrl(downloadJsonUrl.value);
    };

    const downloadHistoryRun = (item: ParserHistoryItem) => {
        downloadByUrl(item.downloadUrl);
    };

    const downloadHistoryRunJson = (item: ParserHistoryItem) => {
        downloadByUrl(item.downloadJsonUrl);
    };

    const handleBeforeUnload = () => {
        const activeRunId = runId.value;

        if (!activeRunId) {
            return;
        }

        fetch(parserEndpoint(`stop/${activeRunId}`), {
            method: 'POST',
            keepalive: true,
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
        }).catch(() => undefined);
    };

    onMounted(() => {
        const params = getRepeatQueryParams();

        if (params) {
            const tab = readRepeatQueryParam(params, ['tab']);

            if (tab === 'parser') {
                const videoId = readRepeatQueryParam(params, ['videoId']);

                if (videoId !== '') {
                    form.videoId = videoId;
                }

                if (
                    isRepeatAutorunEnabled(params) &&
                    form.videoId.trim() !== ''
                ) {
                    void start();
                }
            }
        }

        window.addEventListener('beforeunload', handleBeforeUnload);
        void refreshHistory();
    });

    onBeforeUnmount(() => {
        window.removeEventListener('beforeunload', handleBeforeUnload);
        stopSilently();
    });

    return {
        form,
        settingsCollapsed,
        loading,
        error,
        progress,
        stage,
        processedComments,
        processedReplies,
        downloadUrl,
        downloadJsonUrl,
        historyItems,
        historyLoading,
        historyRetentionDays,
        canStart,
        start,
        stop,
        download,
        downloadJson,
        refreshHistory,
        downloadHistoryRun,
        downloadHistoryRunJson,
    };
};
