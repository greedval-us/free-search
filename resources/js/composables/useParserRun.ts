import { computed, onBeforeUnmount, onMounted, ref, shallowRef } from 'vue';
import type { Ref } from 'vue';
import {
    apiRequest,
    resolveApiErrorMessage,
    resolveClientErrorMessage,
} from '@/lib/api';
import type { ApiResult, RequestOptions } from '@/lib/api';
import { withDownloadLocale } from '@/lib/downloadLocale';
import { resolveSameOriginUrl } from '@/lib/sameOriginUrl';
import type {
    ParserHistoryItem,
    ParserHistoryResponse,
    ParserRunPayload,
    ParserRunStatus,
    ParserStage,
} from '@/types/parser';

type ParserRunOptions<Stage extends string> = {
    routes: {
        start: { url: () => string };
        status: { url: (runId: string) => string };
        stop: { url: (runId: string) => string };
        history: { url: () => string };
    };
    initialStage: Stage;
    requestErrorMessage: () => string;
    pollIntervalMs?: number;
};

const TERMINAL_STATUSES: ParserRunStatus[] = ['completed', 'failed', 'stopped'];
const DEFAULT_POLL_INTERVAL_MS = 3_000;
const DEFAULT_HISTORY_RETENTION_DAYS = 7;

export const useParserRun = <
    StatusPayload extends ParserRunPayload<string>,
    HistoryItem extends ParserHistoryItem,
>(
    options: ParserRunOptions<StatusPayload['stage']>
) => {
    const loading = ref(false);
    const error = ref<string | null>(null);
    const runId = ref<string | null>(null);
    const progress = ref(0);
    const stage = shallowRef<ParserStage<StatusPayload['stage']>>('idle');
    const payload = shallowRef<StatusPayload | null>(null);
    const downloadUrl = computed(() => payload.value?.downloadUrl ?? null);
    const downloadJsonUrl = computed(
        () => payload.value?.downloadJsonUrl ?? null
    );
    const historyItems = ref<HistoryItem[]>([]) as Ref<HistoryItem[]>;
    const historyLoading = ref(false);
    const historyRetentionDays = ref(DEFAULT_HISTORY_RETENTION_DAYS);
    const pollTimer = ref<number | null>(null);
    const pollRequestInFlight = ref(false);
    let disposed = false;
    let pollingVersion = 0;
    const requests = new Set<AbortController>();
    const isCurrent = (version: number) =>
        !disposed && version === pollingVersion;
    const pollIntervalMs = options.pollIntervalMs ?? DEFAULT_POLL_INTERVAL_MS;

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

    const applyPayload = (result: StatusPayload) => {
        stage.value = result.stage;
        progress.value = result.progress;
        error.value = result.error;
        payload.value = {
            ...result,
            downloadUrl: resolveSameOriginUrl(result.downloadUrl),
            downloadJsonUrl: resolveSameOriginUrl(result.downloadJsonUrl),
        };
    };

    const resetState = () => {
        runId.value = null;
        progress.value = 0;
        stage.value = 'idle';
        payload.value = null;
    };

    const refreshHistory = async () => {
        if (disposed || historyLoading.value) {
            return;
        }

        const version = pollingVersion;
        historyLoading.value = true;

        try {
            const response = await request<ParserHistoryResponse<HistoryItem>>(
                options.routes.history.url(),
                { method: 'GET' }
            );

            if (!isCurrent(version)) {
                return;
            }

            if (!response.ok) {
                historyItems.value = [];

                return;
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
            options.routes.stop.url(encodeURIComponent(activeRunId)),
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
                options.routes.status.url(encodeURIComponent(activeRunId)),
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
            const response = await request<StatusPayload>(
                options.routes.start.url(),
                {
                    method: 'POST',
                    body,
                }
            );

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

            stage.value = 'failed';
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
            stage.value = 'stopped';
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
        payload,
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
