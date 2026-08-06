import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { apiRequest, resolveClientErrorMessage } from '@/lib/api';
import { withDownloadLocale } from '@/lib/downloadLocale';
import type {
    TelegramParserHistoryItem as ParserHistoryItem,
    TelegramParserHistoryResponse as ParserHistoryResponse,
    TelegramParserPeriod,
    TelegramParserStage,
    TelegramParserStatusResponse,
} from '../types';

type TranslateFn = (key: string) => string;

const DAY_IN_MS = 24 * 60 * 60 * 1000;
const POLL_INTERVAL_MS = 3000;
const parserEndpoint = (suffix: string) => `/telegram/parser/${suffix}`;
const csrfToken = () =>
    document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
        ?.content ?? '';

const parseDate = (value: string): Date | null => {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);

    if (!match) {
        return null;
    }

    return new Date(
        Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3]))
    );
};

const diffDays = (from: string, to: string): number | null => {
    const fromDate = parseDate(from);
    const toDate = parseDate(to);

    if (!fromDate || !toDate) {
        return null;
    }

    return Math.floor((toDate.getTime() - fromDate.getTime()) / DAY_IN_MS);
};

export const useTelegramParser = (t: TranslateFn) => {
    const form = reactive({
        chatUsername: '',
        keyword: '',
        period: 'week' as TelegramParserPeriod,
        dateFrom: '',
        dateTo: '',
    });

    const settingsCollapsed = ref(false);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const runId = ref<string | null>(null);
    const progress = ref(0);
    const stage = ref<TelegramParserStage>('idle');
    const processedMessages = ref(0);
    const processedComments = ref(0);
    const downloadUrl = ref<string | null>(null);
    const downloadJsonUrl = ref<string | null>(null);
    const historyItems = ref<ParserHistoryItem[]>([]);
    const historyLoading = ref(false);
    const historyRetentionDays = ref(7);
    const pollTimer = ref<number | null>(null);
    const pollRequestInFlight = ref(false);

    const keywordActive = computed(() => form.keyword.trim().length > 0);
    const customPeriod = computed(() => form.period === 'custom');
    const canStart = computed(
        () => form.chatUsername.trim().length > 0 && !loading.value
    );

    const resetState = () => {
        runId.value = null;
        progress.value = 0;
        stage.value = 'idle';
        processedMessages.value = 0;
        processedComments.value = 0;
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

    const applyStatusPayload = (payload: TelegramParserStatusResponse) => {
        stage.value = payload.stage;
        progress.value = payload.progress;
        processedMessages.value = payload.processedMessages;
        processedComments.value = payload.processedComments;
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
                    response.message ?? t('telegram.parser.history.errors.load')
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
    ): Promise<TelegramParserStatusResponse | null> => {
        const response = await apiRequest<TelegramParserStatusResponse>(
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

    const finalizeRun = async (payload: TelegramParserStatusResponse) => {
        applyStatusPayload(payload);
        loading.value = false;
        clearPolling();
        await refreshHistory();
    };

    const stop = () => {
        clearPolling();

        loading.value = false;

        if (stage.value !== 'completed' && stage.value !== 'failed') {
            stage.value = 'stopped';
        }

        const activeRunId = runId.value;

        if (activeRunId) {
            requestStop(activeRunId)
                .then((payload) => {
                    if (!payload || payload.runId !== runId.value) {
                        return;
                    }

                    applyStatusPayload(payload);
                    void refreshHistory();
                })
                .catch(() => undefined);
        }
    };

    const stopSilently = () => {
        clearPolling();

        const activeRunId = runId.value;

        if (activeRunId) {
            requestStop(activeRunId).catch(() => undefined);
        }
    };

    const start = async () => {
        if (!canStart.value) {
            return;
        }

        if (customPeriod.value && !keywordActive.value) {
            if (!form.dateFrom || !form.dateTo) {
                error.value = t('telegram.parser.errors.customBothDates');

                return;
            }

            const days = diffDays(form.dateFrom, form.dateTo);

            if (days === null || days < 0) {
                error.value = t('telegram.parser.errors.customInvalid');

                return;
            }

            if (days > 30) {
                error.value = t('telegram.parser.errors.customTooLong');

                return;
            }
        }

        stopSilently();
        resetState();
        error.value = null;
        loading.value = true;
        stage.value = 'messages';
        progress.value = 1;

        try {
            const response = await apiRequest<TelegramParserStatusResponse>(
                parserEndpoint('start'),
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: {
                        chatUsername: form.chatUsername.trim(),
                        keyword: form.keyword.trim(),
                        period: form.period,
                        dateFrom: form.dateFrom,
                        dateTo: form.dateTo,
                    },
                }
            );

            if (!response.ok || !response.data.runId) {
                throw new Error(
                    response.message ?? t('telegram.parser.errors.failed')
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
                        await apiRequest<TelegramParserStatusResponse>(
                            parserEndpoint(`status/${runId.value}`),
                            { method: 'GET' }
                        );

                    if (!statusResponse.ok) {
                        throw new Error(
                            statusResponse.message ??
                                t('telegram.parser.errors.failed')
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
                        t('telegram.parser.errors.failed')
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
                t('telegram.parser.errors.failed')
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
        runId,
        progress,
        stage,
        processedMessages,
        processedComments,
        downloadUrl,
        downloadJsonUrl,
        historyItems,
        historyLoading,
        historyRetentionDays,
        keywordActive,
        customPeriod,
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
