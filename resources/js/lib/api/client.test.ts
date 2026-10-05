import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { apiRequest, apiRequestOrThrow } from './client';
import { defaultRetryPolicy, toRetryPolicy } from './retry';
import { resolveEndpointRetryPolicy } from './retry-policies';

const success = () =>
    new Response(JSON.stringify({ ok: true, data: { saved: true } }));
const unavailable = () =>
    new Response(JSON.stringify({ ok: false }), { status: 503 });
const fetchMock = vi.fn<typeof fetch>();
const headersFor = (index = 0) =>
    new Headers(fetchMock.mock.calls[index][1]?.headers);

describe('API request boundaries', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.spyOn(Math, 'random').mockReturnValue(0);
        vi.stubGlobal('fetch', fetchMock);
        vi.stubGlobal('window', {
            location: { origin: 'https://free-search.test' },
        });
        vi.stubGlobal('document', {
            cookie: 'XSRF-TOKEN=fresh%2Bcookie',
            querySelector: () => ({ content: 'page-token' }),
        });
        fetchMock.mockReset();
    });

    afterEach(() => {
        vi.clearAllTimers();
        vi.useRealTimers();
        vi.restoreAllMocks();
        vi.unstubAllGlobals();
    });

    it.each([
        new Headers({ 'X-Client': 'custom' }),
        [['X-Client', 'custom']] as [string, string][],
        { 'X-Client': 'custom' },
    ])(
        'preserves HeadersInit while adding JSON and same-origin CSRF headers',
        async (headers) => {
            fetchMock.mockResolvedValueOnce(success());

            expect(
                (
                    await apiRequest('/parser/start', {
                        method: 'POST',
                        body: { value: 1 },
                        headers,
                    })
                ).ok
            ).toBe(true);
            expect(headersFor().get('X-Client')).toBe('custom');
            expect(headersFor().get('Accept')).toBe('application/json');
            expect(headersFor().get('Content-Type')).toBe('application/json');
            expect(headersFor().get('X-XSRF-TOKEN')).toBe('fresh+cookie');
            expect(headersFor().has('X-CSRF-TOKEN')).toBe(false);
            expect(fetchMock.mock.calls[0][1]?.body).toBe('{"value":1}');
        }
    );

    it('adds Accept to bodyless requests and preserves caller content headers', async () => {
        fetchMock.mockResolvedValue(success());
        await apiRequest('/parser/status/1', {
            headers: { 'X-Client': 'custom' },
        });
        await apiRequest('/parser/start', {
            method: 'POST',
            body: {},
            headers: {
                Accept: 'application/custom',
                'Content-Type': 'application/custom',
                'X-CSRF-TOKEN': 'explicit',
            },
        });

        expect(headersFor().get('Accept')).toBe('application/json');
        expect(headersFor().has('Content-Type')).toBe(false);
        expect(headersFor(1).get('Accept')).toBe('application/custom');
        expect(headersFor(1).get('Content-Type')).toBe('application/custom');
        expect(headersFor(1).get('X-CSRF-TOKEN')).toBe('explicit');
        expect(headersFor(1).has('X-XSRF-TOKEN')).toBe(false);
    });

    it.each(['', 'XSRF-TOKEN=%broken'])(
        'falls back to the page CSRF token for an absent or malformed cookie',
        async (cookie) => {
            document.cookie = cookie;
            fetchMock.mockResolvedValueOnce(success());

            await apiRequest('/parser/stop/1', { method: 'POST' });

            expect(headersFor().get('X-CSRF-TOKEN')).toBe('page-token');
            expect(headersFor().has('X-XSRF-TOKEN')).toBe(false);
        }
    );

    it.each([
        ['https://other.test/api', 'POST'],
        ['http://free-search.test/api', 'POST'],
        ['https://user:password@free-search.test/api', 'POST'],
        ['/api', 'GET'],
    ] as const)(
        'does not automatically expose CSRF tokens to %s (%s)',
        async (url, method) => {
            fetchMock.mockResolvedValueOnce(success());

            await apiRequest(url, { method });

            expect(headersFor().has('X-CSRF-TOKEN')).toBe(false);
            expect(headersFor().has('X-XSRF-TOKEN')).toBe(false);
        }
    );

    it.each(['POST', 'PUT', 'PATCH', 'DELETE'] as const)(
        'does not replay an uncertain %s write after a network error',
        async (method) => {
            fetchMock.mockRejectedValue(new TypeError('connection lost'));

            expect(
                await apiRequest('/parser/start', { method, body: {} })
            ).toMatchObject({ ok: false, code: 'network_error' });
            expect(fetchMock).toHaveBeenCalledTimes(1);
            expect(vi.getTimerCount()).toBe(0);
        }
    );

    it('does not replay a parser start after a transient HTTP failure', async () => {
        fetchMock.mockResolvedValue(unavailable());

        expect(
            await apiRequest('/bluesky/parser/start', {
                method: 'POST',
                body: {},
            })
        ).toMatchObject({ ok: false, status: 503 });
        expect(fetchMock).toHaveBeenCalledTimes(1);
    });

    it('retries a transient GET and permits explicit opt-in for writes', async () => {
        fetchMock
            .mockResolvedValueOnce(unavailable())
            .mockResolvedValueOnce(success());
        const read = apiRequest('/parser/status/1');
        await vi.advanceTimersByTimeAsync(250);
        expect(await read).toMatchObject({ ok: true, data: { saved: true } });

        fetchMock
            .mockRejectedValueOnce(new TypeError('connection lost'))
            .mockResolvedValueOnce(success());
        const write = apiRequest('/retry-safe-write', {
            method: 'POST',
            retry: { attempts: 1, baseDelayMs: 10 },
        });
        await vi.advanceTimersByTimeAsync(10);
        expect(await write).toMatchObject({ ok: true });
        expect(fetchMock).toHaveBeenCalledTimes(4);
    });

    it('returns cancellation without fetch for an already aborted signal', async () => {
        const controller = new AbortController();
        controller.abort();

        expect(
            await apiRequest('/parser/status/1', { signal: controller.signal })
        ).toMatchObject({ ok: false, code: 'aborted' });
        expect(fetchMock).not.toHaveBeenCalled();
    });

    it('does not retry an AbortError from fetch or response reading', async () => {
        fetchMock.mockRejectedValueOnce(
            new DOMException('Cancelled', 'AbortError')
        );
        expect(await apiRequest('/parser/status/1')).toMatchObject({
            ok: false,
            code: 'aborted',
        });
        fetchMock.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: () =>
                Promise.reject(new DOMException('Cancelled', 'AbortError')),
        } as Response);
        expect(await apiRequest('/parser/status/1')).toMatchObject({
            ok: false,
            code: 'aborted',
        });
        expect(fetchMock).toHaveBeenCalledTimes(2);
        expect(vi.getTimerCount()).toBe(0);
    });

    it.each(['network', 'status'])(
        'cancels a %s retry delay immediately without another request',
        async (failure) => {
            if (failure === 'network') {
                fetchMock.mockRejectedValueOnce(
                    new TypeError('connection lost')
                );
            } else {
                fetchMock.mockResolvedValueOnce(unavailable());
            }

            const controller = new AbortController();
            const result = apiRequest('/parser/status/1', {
                signal: controller.signal,
            });
            await vi.advanceTimersByTimeAsync(0);
            expect(vi.getTimerCount()).toBe(1);
            controller.abort();

            expect(await result).toMatchObject({ ok: false, code: 'aborted' });
            expect(vi.getTimerCount()).toBe(0);
            await vi.advanceTimersByTimeAsync(10000);
            expect(fetchMock).toHaveBeenCalledTimes(1);
        }
    );

    it('preserves cancellation code in the throwing API', async () => {
        const controller = new AbortController();
        controller.abort();

        await expect(
            apiRequestOrThrow('/parser/status/1', { signal: controller.signal })
        ).rejects.toMatchObject({ code: 'aborted' });
    });

    it('preserves existing query parameters and the fragment when appending options', async () => {
        fetchMock.mockResolvedValueOnce(success());

        await apiRequest('/parser/history?existing=1#section', {
            query: { page: 2, search: 'one two' },
        });

        expect(fetchMock.mock.calls[0][0]).toBe(
            '/parser/history?existing=1&page=2&search=one+two#section'
        );
    });

    it('keeps retry defaults for partial runtime policy fields', async () => {
        window.__OSINT_FRONTEND_CONFIG__ = {
            apiRetry: { default: { attempts: 1, base_delay_ms: 10 } },
        };
        fetchMock
            .mockRejectedValueOnce(new TypeError('connection lost'))
            .mockResolvedValueOnce(success());
        const result = apiRequest('/parser/status/1');
        await vi.advanceTimersByTimeAsync(10);

        expect(await result).toMatchObject({ ok: true });
        expect(fetchMock).toHaveBeenCalledTimes(2);
        expect(
            toRetryPolicy({ attempts: undefined, baseDelayMs: undefined })
        ).toEqual(defaultRetryPolicy);
    });

    it('matches endpoint retry rules by pathname rather than query text', () => {
        expect(
            resolveEndpointRetryPolicy(
                '/unrelated?redirect=/telegram/parser/status/run',
                'GET'
            )
        ).toBeUndefined();
        expect(
            resolveEndpointRetryPolicy(
                '/telegram/parser/status/run?verbose=true',
                'GET'
            )
        ).toMatchObject({ attempts: 4 });
        expect(
            resolveEndpointRetryPolicy('/telegram/parser/start-extra', 'POST')
        ).toBeUndefined();
    });
});
