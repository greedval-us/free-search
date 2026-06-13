import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import {
    getRepeatQueryParams,
    isRepeatAutorunEnabled,
    readRepeatQueryParam,
} from '@/composables/useRepeatQuery';
import { apiRequest, resolveClientErrorMessage } from '@/lib/api';

type ParserStage =
    | 'idle'
    | 'comments'
    | 'replies'
    | 'finishing'
    | 'completed'
    | 'failed'
    | 'stopped';
type ParserStatus = 'running' | 'completed' | 'failed' | 'stopped';
type TranslateFn = (key: string) => string;

type ParserStatusResponse = {
    ok: boolean;
    runId: string;
    status: ParserStatus;
    stage: ParserStage;
    progress: number;
    processedComments: number;
    processedReplies: number;
    error: string | null;
    downloadUrl: string | null;
    downloadJsonUrl: string | null;
};

const POLL_INTERVAL_MS = 3000;

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

    const stopSilently = () => {
        if (pollTimer.value !== null) {
            window.clearTimeout(pollTimer.value);
            pollTimer.value = null;
        }

        pollRequestInFlight.value = false;
        const activeRunId = runId.value;

        if (activeRunId) {
            apiRequest<ParserStatusResponse>(
                `/youtube/parser/stop/${activeRunId}`,
                {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN':
                            document.querySelector<HTMLMetaElement>(
                                'meta[name="csrf-token"]'
                            )?.content ?? '',
                    },
                }
            ).catch(() => undefined);
        }
    };

    const stop = () => {
        if (pollTimer.value !== null) {
            window.clearTimeout(pollTimer.value);
            pollTimer.value = null;
        }

        pollRequestInFlight.value = false;
        loading.value = false;

        if (stage.value !== 'completed' && stage.value !== 'failed') {
            stage.value = 'stopped';
        }

        const activeRunId = runId.value;

        if (!activeRunId) {
            return;
        }

        apiRequest<ParserStatusResponse>(
            `/youtube/parser/stop/${activeRunId}`,
            {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':
                        document.querySelector<HTMLMetaElement>(
                            'meta[name="csrf-token"]'
                        )?.content ?? '',
                },
            }
        )
            .then((response) => {
                if (!response.ok || response.data.runId !== runId.value) {
                    return;
                }

                const payload = response.data;
                stage.value = payload.stage;
                progress.value = payload.progress;
                processedComments.value = payload.processedComments;
                processedReplies.value = payload.processedReplies;
                error.value = payload.error;
                downloadUrl.value = payload.downloadUrl;
                downloadJsonUrl.value = payload.downloadJsonUrl;
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
                '/youtube/parser/start',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN':
                            document.querySelector<HTMLMetaElement>(
                                'meta[name="csrf-token"]'
                            )?.content ?? '',
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
            stage.value = payload.stage;
            progress.value = payload.progress;
            processedComments.value = payload.processedComments;
            processedReplies.value = payload.processedReplies;
            downloadUrl.value = payload.downloadUrl;
            downloadJsonUrl.value = payload.downloadJsonUrl;

            const pollStatus = async () => {
                if (!runId.value || pollRequestInFlight.value) {
                    return;
                }

                pollRequestInFlight.value = true;

                try {
                    const statusResponse =
                        await apiRequest<ParserStatusResponse>(
                            `/youtube/parser/status/${runId.value}`,
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
                    stage.value = statusPayload.stage;
                    progress.value = statusPayload.progress;
                    processedComments.value = statusPayload.processedComments;
                    processedReplies.value = statusPayload.processedReplies;
                    error.value = statusPayload.error;

                    if (
                        statusPayload.status === 'completed' ||
                        statusPayload.status === 'failed' ||
                        statusPayload.status === 'stopped'
                    ) {
                        downloadUrl.value = statusPayload.downloadUrl;
                        downloadJsonUrl.value = statusPayload.downloadJsonUrl;
                        loading.value = false;
                        stopSilently();

                        return;
                    }
                } catch (pollError) {
                    loading.value = false;
                    error.value = resolveClientErrorMessage(
                        pollError,
                        t('youtube.parser.errors.failed')
                    );
                    stopSilently();

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
        if (!downloadUrl.value) {
            return;
        }

        window.location.href = downloadUrl.value;
    };

    const downloadJson = () => {
        if (!downloadJsonUrl.value) {
            return;
        }

        window.location.href = downloadJsonUrl.value;
    };

    const handleBeforeUnload = () => {
        const activeRunId = runId.value;

        if (!activeRunId) {
            return;
        }

        fetch(`/youtube/parser/stop/${activeRunId}`, {
            method: 'POST',
            keepalive: true,
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN':
                    document.querySelector<HTMLMetaElement>(
                        'meta[name="csrf-token"]'
                    )?.content ?? '',
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
        canStart,
        start,
        stop,
        download,
        downloadJson,
    };
};
