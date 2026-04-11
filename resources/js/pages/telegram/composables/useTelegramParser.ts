import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';

type ParserPeriod = 'day' | 'week' | 'month' | 'custom';
type ParserStage = 'idle' | 'messages' | 'comments' | 'finishing' | 'completed' | 'failed' | 'stopped';
type ParserStatus = 'running' | 'completed' | 'failed' | 'stopped';
type TranslateFn = (key: string) => string;
type ParserStatusResponse = {
    ok: boolean;
    runId: string;
    status: ParserStatus;
    stage: ParserStage;
    progress: number;
    processedMessages: number;
    processedComments: number;
    error: string | null;
    downloadUrl: string | null;
};

const DAY_IN_MS = 24 * 60 * 60 * 1000;

const parseDate = (value: string): Date | null => {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
    if (!match) {
        return null;
    }

    return new Date(Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3])));
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
        chatUsername: 'durov',
        keyword: '',
        period: 'week' as ParserPeriod,
        dateFrom: '',
        dateTo: '',
    });

    const settingsCollapsed = ref(false);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const runId = ref<string | null>(null);
    const progress = ref(0);
    const stage = ref<ParserStage>('idle');
    const processedMessages = ref(0);
    const processedComments = ref(0);
    const downloadUrl = ref<string | null>(null);
    const pollTimer = ref<number | null>(null);

    const keywordActive = computed(() => form.keyword.trim().length > 0);
    const customPeriod = computed(() => form.period === 'custom');
    const canStart = computed(() => form.chatUsername.trim().length > 0 && !loading.value);

    const resetState = () => {
        runId.value = null;
        progress.value = 0;
        stage.value = 'idle';
        processedMessages.value = 0;
        processedComments.value = 0;
        downloadUrl.value = null;
    };

    const stop = () => {
        if (pollTimer.value !== null) {
            window.clearInterval(pollTimer.value);
            pollTimer.value = null;
        }

        loading.value = false;
        if (stage.value !== 'completed' && stage.value !== 'failed') {
            stage.value = 'stopped';
        }

        const activeRunId = runId.value;
        if (activeRunId) {
            fetch(`/telegram/parser/stop/${activeRunId}`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
                },
            })
                .then(async (response) => {
                    const payload = (await response.json()) as ParserStatusResponse;

                    if (!response.ok || !payload.ok || payload.runId !== runId.value) {
                        return;
                    }

                    stage.value = payload.stage;
                    progress.value = payload.progress;
                    processedMessages.value = payload.processedMessages;
                    processedComments.value = payload.processedComments;
                    error.value = payload.error;
                    downloadUrl.value = payload.downloadUrl;
                })
                .catch(() => undefined);
        }
    };

    const stopSilently = () => {
        if (pollTimer.value !== null) {
            window.clearInterval(pollTimer.value);
            pollTimer.value = null;
        }

        const activeRunId = runId.value;
        if (activeRunId) {
            fetch(`/telegram/parser/stop/${activeRunId}`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
                },
            }).catch(() => undefined);
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
            const response = await fetch('/telegram/parser/start', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({
                    chatUsername: form.chatUsername.trim(),
                    keyword: form.keyword.trim(),
                    period: form.period,
                    dateFrom: form.dateFrom,
                    dateTo: form.dateTo,
                }),
            });
            const payload = (await response.json()) as ParserStatusResponse;

            if (!response.ok || !payload.ok || !payload.runId) {
                throw new Error(payload.error ?? t('telegram.parser.errors.failed'));
            }

            runId.value = payload.runId;
            stage.value = payload.stage;
            progress.value = payload.progress;
            processedMessages.value = payload.processedMessages;
            processedComments.value = payload.processedComments;

            pollTimer.value = window.setInterval(async () => {
                if (!runId.value) {
                    return;
                }

                try {
                    const statusResponse = await fetch(`/telegram/parser/status/${runId.value}`, {
                        method: 'GET',
                        headers: {
                            Accept: 'application/json',
                        },
                    });
                    const statusPayload = (await statusResponse.json()) as ParserStatusResponse;

                    if (!statusResponse.ok || !statusPayload.ok) {
                        throw new Error(statusPayload.error ?? t('telegram.parser.errors.failed'));
                    }

                    stage.value = statusPayload.stage;
                    progress.value = statusPayload.progress;
                    processedMessages.value = statusPayload.processedMessages;
                    processedComments.value = statusPayload.processedComments;
                    error.value = statusPayload.error;

                    if (statusPayload.status === 'completed') {
                        downloadUrl.value = statusPayload.downloadUrl;
                        loading.value = false;
                        if (pollTimer.value !== null) {
                            window.clearInterval(pollTimer.value);
                            pollTimer.value = null;
                        }
                        return;
                    }

                    if (statusPayload.status === 'failed' || statusPayload.status === 'stopped') {
                        downloadUrl.value = statusPayload.downloadUrl;
                        loading.value = false;
                        if (pollTimer.value !== null) {
                            window.clearInterval(pollTimer.value);
                            pollTimer.value = null;
                        }
                    }
                } catch (pollError) {
                    loading.value = false;
                    error.value = pollError instanceof Error ? pollError.message : t('telegram.parser.errors.failed');
                    if (pollTimer.value !== null) {
                        window.clearInterval(pollTimer.value);
                        pollTimer.value = null;
                    }
                }
            }, 1500);
        } catch (exception) {
            stage.value = 'failed';
            error.value = exception instanceof Error ? exception.message : t('telegram.parser.errors.failed');
        } finally {
            if (stage.value === 'failed') {
                loading.value = false;
            }
        }
    };

    const download = () => {
        if (!downloadUrl.value) return;
        window.location.href = downloadUrl.value;
    };

    const handleBeforeUnload = () => {
        const activeRunId = runId.value;
        if (!activeRunId) {
            return;
        }

        fetch(`/telegram/parser/stop/${activeRunId}`, {
            method: 'POST',
            keepalive: true,
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
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
        runId,
        progress,
        stage,
        processedMessages,
        processedComments,
        downloadUrl,
        keywordActive,
        customPeriod,
        canStart,
        start,
        stop,
        download,
    };
};
