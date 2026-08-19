import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type * as Vue from 'vue';
import { useParserRun } from '@/composables/useParserRun';

const { apiRequest } = vi.hoisted(() => ({
    apiRequest: vi.fn(),
}));

vi.mock('@/lib/api', () => ({
    apiRequest,
    resolveClientErrorMessage: vi.fn(),
}));

vi.mock('vue', async (importOriginal) => {
    const vue = await importOriginal<typeof Vue>();

    return {
        ...vue,
        onMounted: (callback: () => void) => callback(),
        onBeforeUnmount: vi.fn(),
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

describe('useParserRun', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.stubGlobal('window', {
            setTimeout,
            clearTimeout,
            location: { href: '' },
        });
        apiRequest.mockReset();
    });

    afterEach(() => {
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
            { method: 'GET' }
        );
    });
});
