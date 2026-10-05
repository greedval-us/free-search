import { onBeforeUnmount, onMounted, ref } from 'vue';
import type { Ref } from 'vue';
import {
    apiRequest,
    resolveApiErrorMessage,
    resolveClientErrorMessage,
} from '@/lib/api';
import type { ApiResult, RequestOptions } from '@/lib/api';
import { withDownloadLocale } from '@/lib/downloadLocale';
import { resolveSameOriginUrl } from '@/lib/sameOriginUrl';

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
    let disposed = false;
    let pollingVersion = 0;
    const requests = new Set<AbortController>();
    const isCurrent = (version: number) =>
        !disposed && version === pollingVersion;
    const pollIntervalMs = options.pollIntervalMs ?? 3000;
    const endpoint = (suffix: string) =>
        `${options.endpointBase.replace(/\/$/, '')}/${suffix}`;

    const request = async <T>(
        url: string,
        requestOptions: RequestOptions
    ): Promise<ApiResult<T>> => {
        const controller = new AbortController();
        requests.add(controller);

        try {
            return await apiRequest<T>(url, {
                ...requestOptions,
                signal: controller.signal,
            });
        } finally {
            requests.delete(controller);
        }
    };

    const clearPolling = () => {
        // Ignore responses belonging to a stopped, replaced or unmounted run.
        pollingVersion += 1;

        for (const controller of requests) {
            controller.abort();
        }

        requests.clear();

        if (pollTimer.value !== null) {
            window.clearTimeout(pollTimer.value);
            pollTimer.value = null;
        }

        pollRequestInFlight.value = false;
        historyLoading.value = false;
    };

    const applyPayload = (payload: StatusPayload) => {
        stage.value = payload.stage;
        progress.value = payload.progress;
        error.value = payload.error;
        downloadUrl.value = resolveSameOriginUrl(payload.downloadUrl);
        downloadJsonUrl.value = resolveSameOriginUrl(payload.downloadJsonUrl);
        options.applyModulePayload({
            ...payload,
            downloadUrl: downloadUrl.value,
            downloadJsonUrl: downloadJsonUrl.value,
        });
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
        if (disposed || historyLoading.value) {
            return;
        }

        const version = pollingVersion;
        historyLoading.value = true;

        try {
            const response = await request<
                ParserRunHistoryResponse<HistoryItem>
            >(endpoint('history'), { method: 'GET' });

            if (!isCurrent(version)) {
                return;
            }

            if (!response.ok) {
                throw new Error(
                    resolveApiErrorMessage(
                        response.message,
                        options.historyErrorMessage()
                    )
                );
            }

            historyItems.value = response.data.items.map((item) => ({
                ...item,
                downloadUrl: resolveSameOriginUrl(item.downloadUrl),
                downloadJsonUrl: resolveSameOriginUrl(item.downloadJsonUrl),
            }));
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
            if (isCurrent(version)) {
                historyItems.value = [];
            }
        } finally {
            if (isCurrent(version)) {
                historyLoading.value = false;
            }
        }
    };

    const requestStop = async (
        activeRunId: string
    ): Promise<StatusPayload | null> => {
        const response = await request<StatusPayload>(
            endpoint(`stop/${encodeURIComponent(activeRunId)}`),
            {
                method: 'POST',
            }
        );

        return response.ok ? response.data : null;
    };

    const schedulePoll = () => {
        if (disposed || !loading.value || !runId.value) {
            return;
        }

        if (pollTimer.value !== null) {
            window.clearTimeout(pollTimer.value);
        }

        pollTimer.value = window.setTimeout(() => {
            pollTimer.value = null;
            void pollStatus();
        }, pollIntervalMs);
    };

    const pollStatus = async () => {
        if (disposed || !runId.value || pollRequestInFlight.value) {
            return;
        }

        const version = pollingVersion;
        const activeRunId = runId.value;
        pollRequestInFlight.value = true;

        try {
            const response = await request<StatusPayload>(
                endpoint(`status/${encodeURIComponent(activeRunId)}`),
                { method: 'GET' }
            );

            if (!isCurrent(version)) {
                return;
            }

            if (!response.ok || response.data.runId !== activeRunId) {
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
            if (!isCurrent(version)) {
                return;
            }

            loading.value = false;
            error.value = resolveClientErrorMessage(
                pollError,
                options.requestErrorMessage()
            );
            clearPolling();

            return;
        } finally {
            if (isCurrent(version)) {
                pollRequestInFlight.value = false;
            }
        }

        schedulePoll();
    };

    const startRun = async (body: unknown): Promise<boolean> => {
        if (disposed || loading.value) {
            return false;
        }

        clearPolling();
        const version = pollingVersion;
        resetState();
        error.value = null;
        loading.value = true;
        stage.value = options.initialStage;
        progress.value = 1;

        try {
            const response = await request<StatusPayload>(endpoint('start'), {
                method: 'POST',
                body,
            });

            if (!isCurrent(version)) {
                return false;
            }

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

            if (TERMINAL_STATUSES.includes(response.data.status)) {
                loading.value = false;
                clearPolling();
            }

            void refreshHistory();
            schedulePoll();

            return true;
        } catch (startError) {
            if (!isCurrent(version)) {
                return false;
            }

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
        if (disposed) {
            return;
        }

        clearPolling();
        const version = pollingVersion;
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
                if (
                    !isCurrent(version) ||
                    !payload ||
                    payload.runId !== runId.value
                ) {
                    return;
                }

                applyPayload(payload);
                void refreshHistory();
            })
            .catch(() => undefined);
    };

    const downloadByUrl = (url: string | null) => {
        const safeUrl = url ? withDownloadLocale(url) : null;

        if (safeUrl) {
            window.location.href = safeUrl;
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
        disposed = true;
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
