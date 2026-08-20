import { onBeforeUnmount, onMounted, ref } from 'vue';
import type { Ref } from 'vue';
import {
    apiRequest,
    resolveApiErrorMessage,
    resolveClientErrorMessage,
} from '@/lib/api';
import { withDownloadLocale } from '@/lib/downloadLocale';

export type ParserRunStatus = 'running' | 'completed' | 'failed' | 'stopped';

export type ParserRunStatusPayload<Stage extends string> = {
    runId: string;
    status: ParserRunStatus;
    stage: Stage;
    progress: number;
    error: string | null;
    downloadUrl: string | null;
    downloadJsonUrl: string | null;
};

export type ParserRunHistoryItem = {
    runId: string;
    status: ParserRunStatus | 'unknown';
    downloadUrl: string | null;
    downloadJsonUrl: string | null;
};

type ParserRunHistoryResponse<Item extends ParserRunHistoryItem> = {
    items: Item[];
    retentionDays: number;
};

type ParserRunOptions<
    Stage extends string,
    StatusPayload extends ParserRunStatusPayload<Stage>,
> = {
    endpointBase: string;
    idleStage: Stage;
    initialStage: Stage;
    stoppedStage: Stage;
    failedStage: Stage;
    requestErrorMessage: () => string;
    historyErrorMessage: () => string;
    applyModulePayload: (payload: StatusPayload) => void;
    resetModuleState: () => void;
    pollIntervalMs?: number;
};

const TERMINAL_STATUSES: ParserRunStatus[] = ['completed', 'failed', 'stopped'];

const csrfToken = () =>
    document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
        ?.content ?? '';

export const useParserRun = <
    Stage extends string,
    StatusPayload extends ParserRunStatusPayload<Stage>,
    HistoryItem extends ParserRunHistoryItem,
>(
    options: ParserRunOptions<Stage, StatusPayload>
) => {
    const loading = ref(false);
    const error = ref<string | null>(null);
    const runId = ref<string | null>(null);
    const progress = ref(0);
    const stage = ref(options.idleStage) as Ref<Stage>;
    const downloadUrl = ref<string | null>(null);
    const downloadJsonUrl = ref<string | null>(null);
    const historyItems = ref<HistoryItem[]>([]) as Ref<HistoryItem[]>;
    const historyLoading = ref(false);
    const historyRetentionDays = ref(7);
    const pollTimer = ref<number | null>(null);
    const pollRequestInFlight = ref(false);
    const pollIntervalMs = options.pollIntervalMs ?? 3000;
    const endpoint = (suffix: string) =>
        `${options.endpointBase.replace(/\/$/, '')}/${suffix}`;

    const clearPolling = () => {
        if (pollTimer.value !== null) {
            window.clearTimeout(pollTimer.value);
            pollTimer.value = null;
        }

        pollRequestInFlight.value = false;
    };

    const applyPayload = (payload: StatusPayload) => {
        stage.value = payload.stage;
        progress.value = payload.progress;
        error.value = payload.error;
        downloadUrl.value = payload.downloadUrl;
        downloadJsonUrl.value = payload.downloadJsonUrl;
        options.applyModulePayload(payload);
    };

    const resetState = () => {
        runId.value = null;
        progress.value = 0;
        stage.value = options.idleStage;
        downloadUrl.value = null;
        downloadJsonUrl.value = null;
        options.resetModuleState();
    };

    const refreshHistory = async () => {
        historyLoading.value = true;

        try {
            const response = await apiRequest<
                ParserRunHistoryResponse<HistoryItem>
            >(endpoint('history'), { method: 'GET' });

            if (!response.ok) {
                throw new Error(
                    resolveApiErrorMessage(
                        response.message,
                        options.historyErrorMessage()
                    )
                );
            }

            historyItems.value = response.data.items;
            historyRetentionDays.value = response.data.retentionDays;

            const activeRun = response.data.items.find(
                (item) => item.status === 'running'
            );

            if (!runId.value && activeRun) {
                runId.value = activeRun.runId;
                loading.value = true;
                await pollStatus();
            }
        } catch {
            historyItems.value = [];
        } finally {
            historyLoading.value = false;
        }
    };

    const requestStop = async (
        activeRunId: string
    ): Promise<StatusPayload | null> => {
        const response = await apiRequest<StatusPayload>(
            endpoint(`stop/${activeRunId}`),
            {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                },
            }
        );

        return response.ok ? response.data : null;
    };

    const schedulePoll = () => {
        pollTimer.value = window.setTimeout(() => {
            void pollStatus();
        }, pollIntervalMs);
    };

    const pollStatus = async () => {
        if (!runId.value || pollRequestInFlight.value) {
            return;
        }

        pollRequestInFlight.value = true;

        try {
            const response = await apiRequest<StatusPayload>(
                endpoint(`status/${runId.value}`),
                { method: 'GET' }
            );

            if (!response.ok) {
                throw new Error(
                    resolveApiErrorMessage(
                        response.message,
                        options.requestErrorMessage()
                    )
                );
            }

            applyPayload(response.data);

            if (TERMINAL_STATUSES.includes(response.data.status)) {
                loading.value = false;
                clearPolling();
                await refreshHistory();

                return;
            }
        } catch (pollError) {
            loading.value = false;
            error.value = resolveClientErrorMessage(
                pollError,
                options.requestErrorMessage()
            );
            clearPolling();

            return;
        } finally {
            pollRequestInFlight.value = false;
        }

        schedulePoll();
    };

    const startRun = async (body: unknown): Promise<boolean> => {
        clearPolling();
        resetState();
        error.value = null;
        loading.value = true;
        stage.value = options.initialStage;
        progress.value = 1;

        try {
            const response = await apiRequest<StatusPayload>(
                endpoint('start'),
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body,
                }
            );

            if (!response.ok || !response.data.runId) {
                throw new Error(
                    resolveApiErrorMessage(
                        response.message,
                        options.requestErrorMessage()
                    )
                );
            }

            runId.value = response.data.runId;
            applyPayload(response.data);
            void refreshHistory();
            schedulePoll();

            return true;
        } catch (startError) {
            stage.value = options.failedStage;
            error.value = resolveClientErrorMessage(
                startError,
                options.requestErrorMessage()
            );
            loading.value = false;

            return false;
        }
    };

    const stop = () => {
        clearPolling();
        loading.value = false;

        if (stage.value !== 'completed' && stage.value !== 'failed') {
            stage.value = options.stoppedStage;
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

                applyPayload(payload);
                void refreshHistory();
            })
            .catch(() => undefined);
    };

    const downloadByUrl = (url: string | null) => {
        if (url) {
            window.location.href = withDownloadLocale(url);
        }
    };

    const download = () => downloadByUrl(downloadUrl.value);
    const downloadJson = () => downloadByUrl(downloadJsonUrl.value);
    const downloadHistoryRun = (item: HistoryItem) =>
        downloadByUrl(item.downloadUrl);
    const downloadHistoryRunJson = (item: HistoryItem) =>
        downloadByUrl(item.downloadJsonUrl);

    onMounted(() => {
        void refreshHistory();
    });

    onBeforeUnmount(() => {
        clearPolling();
    });

    return {
        loading,
        error,
        runId,
        progress,
        stage,
        downloadUrl,
        downloadJsonUrl,
        historyItems,
        historyLoading,
        historyRetentionDays,
        startRun,
        stop,
        refreshHistory,
        download,
        downloadJson,
        downloadHistoryRun,
        downloadHistoryRunJson,
    };
};
