import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { apiRequest } from '@/lib/api';

type ParserStage =
    | 'idle'
    | 'profile'
    | 'feed'
    | 'followers'
    | 'follows'
    | 'interactions'
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
    processedPosts: number;
    processedAuthoredReplies: number;
    processedReceivedReplies: number;
    processedFollowers: number;
    processedFollows: number;
    processedReactions: number;
    error: string | null;
    downloadUrl: string | null;
    downloadJsonUrl: string | null;
};

const POLL_INTERVAL_MS = 3000;
const TERMINAL_STATUSES: ParserStatus[] = ['completed', 'failed', 'stopped'];

export const useBlueskyParser = (t: TranslateFn) => {
    const form = reactive({
        actor: '',
    });

    const settingsCollapsed = ref(false);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const runId = ref<string | null>(null);
    const progress = ref(0);
    const stage = ref<ParserStage>('idle');
    const processedPosts = ref(0);
    const processedAuthoredReplies = ref(0);
    const processedReceivedReplies = ref(0);
    const processedFollowers = ref(0);
    const processedFollows = ref(0);
    const processedReactions = ref(0);
    const downloadUrl = ref<string | null>(null);
    const downloadJsonUrl = ref<string | null>(null);
    const pollTimer = ref<number | null>(null);
    const pollRequestInFlight = ref(false);

    const canStart = computed(() => form.actor.trim().length > 0 && !loading.value);

    const csrfToken = () =>
        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

    const requestHeaders = (contentType?: 'application/json') => ({
        ...(contentType ? { 'Content-Type': contentType } : {}),
        'X-CSRF-TOKEN': csrfToken(),
    });

    const resetState = () => {
        runId.value = null;
        progress.value = 0;
        stage.value = 'idle';
        processedPosts.value = 0;
        processedAuthoredReplies.value = 0;
        processedReceivedReplies.value = 0;
        processedFollowers.value = 0;
        processedFollows.value = 0;
        processedReactions.value = 0;
        downloadUrl.value = null;
        downloadJsonUrl.value = null;
    };

    const applyPayload = (payload: ParserStatusResponse) => {
        stage.value = payload.stage;
        progress.value = payload.progress;
        processedPosts.value = payload.processedPosts;
        processedAuthoredReplies.value = payload.processedAuthoredReplies;
        processedReceivedReplies.value = payload.processedReceivedReplies;
        processedFollowers.value = payload.processedFollowers;
        processedFollows.value = payload.processedFollows;
        processedReactions.value = payload.processedReactions;
        error.value = payload.error;
        downloadUrl.value = payload.downloadUrl;
        downloadJsonUrl.value = payload.downloadJsonUrl;
    };

    const stopRunRequest = (activeRunId: string) =>
        apiRequest<ParserStatusResponse>(`/bluesky/parser/stop/${activeRunId}`, {
            method: 'POST',
            headers: requestHeaders(),
        });

    const stopSilently = () => {
        if (pollTimer.value !== null) {
            window.clearTimeout(pollTimer.value);
            pollTimer.value = null;
        }

        pollRequestInFlight.value = false;
        const activeRunId = runId.value;

        if (activeRunId) {
            stopRunRequest(activeRunId).catch(() => undefined);
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

        stopRunRequest(activeRunId)
            .then((response) => {
                if (!response.ok || response.data.runId !== runId.value) {
                    return;
                }

                applyPayload(response.data);
            })
            .catch(() => undefined);
    };

    const start = async () => {
        if (!canStart.value) {
            error.value = t('bluesky.parser.errors.actorRequired');

            return;
        }

        stopSilently();
        resetState();
        error.value = null;
        loading.value = true;
        stage.value = 'profile';
        progress.value = 1;

        try {
            const response = await apiRequest<ParserStatusResponse>('/bluesky/parser/start', {
                method: 'POST',
                headers: requestHeaders('application/json'),
                body: {
                    actor: form.actor.trim(),
                },
            });

            if (!response.ok || !response.data.runId) {
                throw new Error(response.message ?? t('bluesky.parser.errors.failed'));
            }

            runId.value = response.data.runId;
            applyPayload(response.data);

            const pollStatus = async () => {
                if (!runId.value || pollRequestInFlight.value) {
                    return;
                }

                pollRequestInFlight.value = true;

                try {
                    const statusResponse = await apiRequest<ParserStatusResponse>(
                        `/bluesky/parser/status/${runId.value}`,
                        {
                            method: 'GET',
                        }
                    );

                    if (!statusResponse.ok) {
                        throw new Error(statusResponse.message ?? t('bluesky.parser.errors.failed'));
                    }

                    const statusPayload = statusResponse.data;
                    applyPayload(statusPayload);

                    if (TERMINAL_STATUSES.includes(statusPayload.status)) {
                        loading.value = false;
                        stopSilently();

                        return;
                    }
                } catch (pollError) {
                    loading.value = false;
                    error.value =
                        pollError instanceof Error
                            ? pollError.message
                            : t('bluesky.parser.errors.failed');
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
            error.value =
                exception instanceof Error ? exception.message : t('bluesky.parser.errors.failed');
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

        fetch(`/bluesky/parser/stop/${activeRunId}`, {
            method: 'POST',
            keepalive: true,
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
        }).catch(() => undefined);
    };

    onMounted(() => {
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
        processedPosts,
        processedAuthoredReplies,
        processedReceivedReplies,
        processedFollowers,
        processedFollows,
        processedReactions,
        downloadUrl,
        downloadJsonUrl,
        canStart,
        start,
        stop,
        download,
        downloadJson,
    };
};
