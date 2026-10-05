import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type * as Vue from 'vue';
import { useParserRun } from '@/composables/useParserRun';

const { apiRequest, onBeforeUnmount } = vi.hoisted(() => ({
    apiRequest: vi.fn(),
    onBeforeUnmount: vi.fn(),
}));

vi.mock('@/lib/api', () => ({
    apiRequest,
    resolveClientErrorMessage: () => 'Request failed',
    resolveApiErrorMessage: () => 'Request failed',
}));

vi.mock('vue', async (importOriginal) => {
    const vue = await importOriginal<typeof Vue>();

    return {
        ...vue,
        onMounted: (callback: () => void) => callback(),
        onBeforeUnmount,
    };
});

type TestStage = 'idle' | 'working' | 'completed' | 'failed' | 'stopped';

type TestStatusPayload = {
    runId: string;
    status: 'running' | 'completed' | 'failed' | 'stopped';
    stage: TestStage;
    progress: number;
    error: string | null;
    downloadUrl: string | null;
    downloadJsonUrl: string | null;
    processedItems: number;
};

type TestHistoryItem = {
    runId: string;
    status: 'running' | 'completed' | 'failed' | 'stopped' | 'unknown';
    downloadUrl: string | null;
    downloadJsonUrl: string | null;
};

const statusResponse = (overrides: Partial<TestStatusPayload> = {}) => ({
    ok: true,
    data: {
        runId: 'active-run',
        status: 'running',
        stage: 'working',
        progress: 42,
        error: null,
        downloadUrl: null,
        downloadJsonUrl: null,
        processedItems: 12,
        ...overrides,
    } as TestStatusPayload,
});

const historyResponse = (active = true) => ({
    ok: true,
    data: {
        items: active ? [{ runId: 'active-run', status: 'running' }] : [],
        retentionDays: 7,
    },
});

const deferred = <T>() => {
    let resolve!: (value: T) => void;
    const promise = new Promise<T>((done) => {
        resolve = done;
    });

    return { promise, resolve };
};

const createParser = () =>
    useParserRun<TestStage, TestStatusPayload, TestHistoryItem>({
        endpointBase: '/test/parser',
        idleStage: 'idle',
        initialStage: 'working',
        stoppedStage: 'stopped',
        failedStage: 'failed',
        requestErrorMessage: () => 'Request failed',
        historyErrorMessage: () => 'History failed',
        applyModulePayload: () => {},
        resetModuleState: () => {},
    });

const unmount = () => {
    onBeforeUnmount.mock.calls[0][0]();
};

describe('useParserRun', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.stubGlobal('window', {
            setTimeout,
            clearTimeout,
            location: { href: '', origin: 'https://free-search.test' },
            localStorage: { getItem: () => 'ru' },
        });
        vi.stubGlobal('document', {
            querySelector: () => ({ content: 'test-csrf' }),
            documentElement: { lang: 'en' },
        });
        apiRequest.mockReset();
        onBeforeUnmount.mockReset();
    });

    afterEach(() => {
        vi.clearAllTimers();
        vi.useRealTimers();
        vi.unstubAllGlobals();
    });

    it('restores an active run from history and resumes status polling', async () => {
        apiRequest
            .mockResolvedValueOnce({
                ok: true,
                data: {
                    items: [
                        {
                            runId: 'active-run',
                            status: 'running',
                            downloadUrl: null,
                            downloadJsonUrl: null,
                        },
                    ],
                    retentionDays: 7,
                },
            })
            .mockResolvedValueOnce({
                ok: true,
                data: {
                    runId: 'active-run',
                    status: 'running',
                    stage: 'working',
                    progress: 42,
                    error: null,
                    downloadUrl: null,
                    downloadJsonUrl: null,
                    processedItems: 12,
                },
            });

        const processedItems = { value: 0 };
        const parser = useParserRun<
            TestStage,
            TestStatusPayload,
            TestHistoryItem
        >({
            endpointBase: '/test/parser',
            idleStage: 'idle',
            initialStage: 'working',
            stoppedStage: 'stopped',
            failedStage: 'failed',
            requestErrorMessage: () => 'Request failed',
            historyErrorMessage: () => 'History failed',
            applyModulePayload: (payload) => {
                processedItems.value = payload.processedItems;
            },
            resetModuleState: () => {
                processedItems.value = 0;
            },
        });

        await vi.waitFor(() => {
            expect(parser.runId.value).toBe('active-run');
            expect(parser.progress.value).toBe(42);
        });

        expect(parser.loading.value).toBe(true);
        expect(parser.stage.value).toBe('working');
        expect(processedItems.value).toBe(12);
        expect(apiRequest).toHaveBeenNthCalledWith(
            2,
            '/test/parser/status/active-run',
            expect.objectContaining({
                method: 'GET',
                signal: expect.any(AbortSignal),
            })
        );
    });

    it('does not restart polling when status arrives after unmount', async () => {
        const pending = deferred<ReturnType<typeof statusResponse>>();
        apiRequest
            .mockResolvedValueOnce(historyResponse())
            .mockReturnValueOnce(pending.promise);
        const parser = createParser();
        await vi.advanceTimersByTimeAsync(0);
        expect(apiRequest).toHaveBeenCalledTimes(2);
        const signal = apiRequest.mock.calls[1][1].signal as AbortSignal;
        unmount();
        expect(signal.aborted).toBe(true);
        pending.resolve(statusResponse());
        await vi.advanceTimersByTimeAsync(6000);
        expect(vi.getTimerCount()).toBe(0);
        expect(apiRequest).toHaveBeenCalledTimes(2);
        expect(parser.progress.value).toBe(0);
    });

    it('does not restore an active run when history arrives after unmount', async () => {
        const pending = deferred<ReturnType<typeof historyResponse>>();
        apiRequest.mockReturnValueOnce(pending.promise);
        const parser = createParser();
        unmount();
        expect(
            (apiRequest.mock.calls[0][1].signal as AbortSignal).aborted
        ).toBe(true);
        pending.resolve(historyResponse());
        await vi.advanceTimersByTimeAsync(6000);
        expect(parser.runId.value).toBeNull();
        expect(apiRequest).toHaveBeenCalledTimes(1);
        expect(vi.getTimerCount()).toBe(0);
    });

    it('ignores a start response after unmount and prevents duplicate clicks', async () => {
        const pending = deferred<ReturnType<typeof statusResponse>>();
        apiRequest
            .mockResolvedValueOnce(historyResponse(false))
            .mockReturnValueOnce(pending.promise);
        const parser = createParser();
        await vi.advanceTimersByTimeAsync(0);
        const started = parser.startRun({});
        expect(await parser.startRun({})).toBe(false);
        const signal = apiRequest.mock.calls[1][1].signal as AbortSignal;
        unmount();
        expect(signal.aborted).toBe(true);
        pending.resolve(statusResponse());
        expect(await started).toBe(false);
        expect(parser.runId.value).toBeNull();
        expect(apiRequest).toHaveBeenCalledTimes(2);
        expect(vi.getTimerCount()).toBe(0);
    });

    it('ignores an in-flight status response after stop', async () => {
        const pending = deferred<ReturnType<typeof statusResponse>>();
        apiRequest
            .mockResolvedValueOnce(historyResponse())
            .mockReturnValueOnce(pending.promise)
            .mockResolvedValueOnce(
                statusResponse({ status: 'stopped', stage: 'stopped' })
            )
            .mockResolvedValueOnce(historyResponse(false));
        const parser = createParser();
        await vi.advanceTimersByTimeAsync(0);
        const signal = apiRequest.mock.calls[1][1].signal as AbortSignal;
        parser.stop();
        expect(signal.aborted).toBe(true);
        await vi.advanceTimersByTimeAsync(0);
        pending.resolve(statusResponse());
        await vi.advanceTimersByTimeAsync(6000);
        expect(parser.stage.value).toBe('stopped');
        expect(parser.loading.value).toBe(false);
        expect(parser.historyLoading.value).toBe(false);
        expect(apiRequest).toHaveBeenCalledTimes(4);
        expect(vi.getTimerCount()).toBe(0);
    });

    it('clears the history loader if the restored run status fails', async () => {
        apiRequest
            .mockResolvedValueOnce(historyResponse())
            .mockResolvedValueOnce({ ok: false });
        const parser = createParser();
        await vi.advanceTimersByTimeAsync(0);
        expect(parser.loading.value).toBe(false);
        expect(parser.historyLoading.value).toBe(false);
        expect(parser.error.value).toBe('Request failed');
        expect(vi.getTimerCount()).toBe(0);
    });

    it('refreshes history without further polling when a run completes', async () => {
        apiRequest
            .mockResolvedValueOnce(historyResponse())
            .mockResolvedValueOnce(
                statusResponse({ status: 'completed', stage: 'completed' })
            )
            .mockResolvedValueOnce(historyResponse(false));
        const parser = createParser();
        await vi.advanceTimersByTimeAsync(6000);
        expect(parser.stage.value).toBe('completed');
        expect(parser.historyLoading.value).toBe(false);
        expect(apiRequest).toHaveBeenCalledTimes(3);
        expect(vi.getTimerCount()).toBe(0);
    });

    it('finishes immediately when start returns a terminal run', async () => {
        apiRequest
            .mockResolvedValueOnce(historyResponse(false))
            .mockResolvedValueOnce(
                statusResponse({ status: 'completed', stage: 'completed' })
            )
            .mockResolvedValueOnce(historyResponse(false));
        const parser = createParser();
        await vi.advanceTimersByTimeAsync(0);

        expect(await parser.startRun({})).toBe(true);
        await vi.advanceTimersByTimeAsync(6000);
        expect(parser.loading.value).toBe(false);
        expect(parser.stage.value).toBe('completed');
        expect(apiRequest).toHaveBeenCalledTimes(3);
        expect(vi.getTimerCount()).toBe(0);
    });

    it('does not apply status from a different run', async () => {
        apiRequest
            .mockResolvedValueOnce(historyResponse())
            .mockResolvedValueOnce(statusResponse({ runId: 'another-run' }));
        const parser = createParser();
        await vi.advanceTimersByTimeAsync(6000);

        expect(parser.loading.value).toBe(false);
        expect(parser.runId.value).toBe('active-run');
        expect(parser.progress.value).toBe(0);
        expect(parser.error.value).toBe('Request failed');
        expect(vi.getTimerCount()).toBe(0);
    });

    it('avoids duplicate history requests while one is pending', async () => {
        const pending = deferred<ReturnType<typeof historyResponse>>();
        apiRequest.mockReturnValueOnce(pending.promise);
        const parser = createParser();

        await parser.refreshHistory();
        expect(apiRequest).toHaveBeenCalledTimes(1);
        pending.resolve(historyResponse(false));
        await vi.advanceTimersByTimeAsync(0);
        expect(parser.historyLoading.value).toBe(false);
    });

    it('blocks unsafe URLs from status and history while preserving valid downloads', async () => {
        apiRequest
            .mockResolvedValueOnce({
                ok: true,
                data: {
                    items: [
                        {
                            runId: 'active-run',
                            status: 'running',
                            downloadUrl: 'javascript:alert(1)',
                            downloadJsonUrl: 'https://other.test/report',
                        },
                    ],
                    retentionDays: 7,
                },
            })
            .mockResolvedValueOnce(
                statusResponse({
                    downloadUrl: 'data:text/html,attack',
                    downloadJsonUrl: '/parser/report?format=json',
                })
            );
        const parser = createParser();
        await vi.advanceTimersByTimeAsync(0);

        expect(parser.downloadUrl.value).toBeNull();
        expect(parser.historyItems.value[0].downloadUrl).toBeNull();
        expect(parser.historyItems.value[0].downloadJsonUrl).toBeNull();
        parser.download();
        parser.downloadHistoryRun(parser.historyItems.value[0]);
        parser.downloadHistoryRunJson({
            runId: 'tampered',
            status: 'completed',
            downloadUrl: null,
            downloadJsonUrl: 'https://user:password@free-search.test/report',
        });
        expect(window.location.href).toBe('');

        parser.downloadJson();
        expect(window.location.href).toBe(
            'https://free-search.test/parser/report?format=json&locale=ru'
        );
        parser.downloadHistoryRun({
            runId: 'completed-run',
            status: 'completed',
            downloadUrl: '/parser/report?run=completed-run',
            downloadJsonUrl: null,
        });
        expect(window.location.href).toBe(
            'https://free-search.test/parser/report?run=completed-run&locale=ru'
        );
    });

    it('encodes the run identifier as a single status and stop path segment', async () => {
        apiRequest
            .mockResolvedValueOnce({
                ok: true,
                data: {
                    items: [{ runId: '../other?key=value', status: 'running' }],
                    retentionDays: 7,
                },
            })
            .mockResolvedValueOnce(
                statusResponse({ runId: '../other?key=value' })
            )
            .mockResolvedValueOnce(
                statusResponse({
                    runId: '../other?key=value',
                    status: 'stopped',
                    stage: 'stopped',
                })
            )
            .mockResolvedValueOnce(historyResponse(false));
        const parser = createParser();
        await vi.advanceTimersByTimeAsync(0);
        parser.stop();
        await vi.advanceTimersByTimeAsync(0);

        expect(apiRequest.mock.calls[1][0]).toBe(
            '/test/parser/status/..%2Fother%3Fkey%3Dvalue'
        );
        expect(apiRequest.mock.calls[2][0]).toBe(
            '/test/parser/stop/..%2Fother%3Fkey%3Dvalue'
        );
    });
});
