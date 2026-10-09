import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type * as Vue from 'vue';
import { ApiError } from '@/lib/api';
import type * as Api from '@/lib/api';
import type { NewsAnalytics, NewsOptions, NewsResult } from './types';
import { useNewsIntel } from './useNewsIntel';

const hooks = vi.hoisted(() => ({
    mounted: [] as (() => Promise<void>)[],
    unmounted: [] as (() => void)[],
    request: vi.fn(),
    params: null as URLSearchParams | null,
}));
vi.mock('vue', async (original) => ({
    ...(await original<typeof Vue>()),
    onMounted: (callback: () => Promise<void>) => hooks.mounted.push(callback),
    onBeforeUnmount: (callback: () => void) => hooks.unmounted.push(callback),
}));
vi.mock('@/composables/useI18n', () => ({
    useI18n: () => ({ t: (key: string) => key, locale: { value: 'ru' } }),
}));
vi.mock('@/lib/api', async (original) => ({
    ...(await original<typeof Api>()),
    apiRequestOrThrow: hooks.request,
}));
vi.mock('@/composables/useRepeatQuery', () => ({
    getRepeatQueryParams: () => hooks.params,
    readRepeatQueryParam: (params: URLSearchParams, keys: string[]) =>
        params.get(keys[0]) ?? '',
    isRepeatAutorunEnabled: (params: URLSearchParams) =>
        params.get('autorun') === '1',
}));

const options: NewsOptions = {
    categories: ['news', 'general'],
    languages: ['ru', 'en'],
    engines: { news: ['bing news'], general: ['google'] },
    maxPages: 3,
    defaults: { language: 'ru', timeRange: '', safeSearch: 1, maxPages: 3 },
};
const search = (query: string): NewsResult => ({
    query,
    mentions: [],
    topics: [],
    timeline: [],
    sentiment: { positive: 0, negative: 0, neutral: 0 },
});
const snapshot = (query: string) =>
    ({
        query,
        reportId: 'f2870275-5756-40ed-afaf-85ef422b14ee',
        reportExpiresAt: '2026-10-09T09:00:00Z',
    }) as NewsAnalytics;
const deferred = <T>() => {
    let resolve!: (result: T) => void;
    const promise = new Promise<T>((done) => {
        resolve = done;
    });

    return { promise, resolve };
};

describe('news search and marketing analytics requests', () => {
    beforeEach(() => {
        hooks.request.mockReset();
        hooks.mounted.length = 0;
        hooks.unmounted.length = 0;
        hooks.params = null;
    });
    afterEach(() => {
        hooks.unmounted.forEach((callback) => callback());
    });

    it('loads actual options before allowing a search and preserves blank time range', async () => {
        hooks.request
            .mockResolvedValueOnce(options)
            .mockResolvedValueOnce(search('topic'));
        const state = useNewsIntel('search');
        state.form.value.query = 'topic';
        expect(state.canRun.value).toBe(false);
        await state.loadOptions();
        expect(state.canRun.value).toBe(true);
        state.filters.value.categories = ['news', 'general'];
        state.filters.value.engines = ['bing news', 'google'];
        await state.run();
        expect(hooks.request).toHaveBeenLastCalledWith(
            '/news-media-intel/lookup',
            expect.objectContaining({
                query: expect.objectContaining({
                    query: 'topic',
                    locale: 'ru',
                    timeRange: '',
                    'categories[0]': 'news',
                    'categories[1]': 'general',
                    'engines[0]': 'bing news',
                    'engines[1]': 'google',
                }),
                retry: { attempts: 0 },
            })
        );
    });

    it('submits all three marketing scenarios with normalized labels and URL domain', async () => {
        hooks.request
            .mockResolvedValueOnce(options)
            .mockResolvedValueOnce(snapshot('seo tools'));
        const state = useNewsIntel('analytics');
        await state.loadOptions();
        Object.assign(state.form.value, {
            query: ' seo tools ',
            brand: ' Brand ',
            competitors: ' Rival one \r\nRival two\n',
            domain: 'https://www.Example.com/Products?ref=1',
        });
        await state.run();
        expect(hooks.request).toHaveBeenLastCalledWith(
            '/news-media-intel/analytics',
            expect.objectContaining({
                method: 'POST',
                body: {
                    query: 'seo tools',
                    brand: 'Brand',
                    competitors: ['Rival one', 'Rival two'],
                    domain: 'example.com',
                    language: 'ru',
                    timeRange: '',
                    safeSearch: 1,
                    engines: [],
                    maxPages: 3,
                    locale: 'ru',
                },
            })
        );
        expect(state.analyticsResult.value?.query).toBe('seo tools');
    });

    it('prevents duplicate entity requests and invalid domain inputs', async () => {
        hooks.request.mockResolvedValue(options);
        const state = useNewsIntel('analytics');
        await state.loadOptions();
        Object.assign(state.form.value, {
            query: 'topic',
            brand: 'Brand',
            competitors: 'brand',
        });
        await state.run();
        expect(hooks.request).toHaveBeenCalledTimes(1);
        expect(state.error.value).toBe(
            'newsMediaIntel.errors.duplicateCompetitors'
        );
        state.form.value.competitors = '';
        state.form.value.domain = 'https://user:pass@example.com';
        expect(state.canRun.value).toBe(false);
    });

    it('aborts an older search and ignores its response even when it resolves last', async () => {
        const first = deferred<NewsResult>();
        const second = deferred<NewsResult>();
        hooks.request
            .mockResolvedValueOnce(options)
            .mockReturnValueOnce(first.promise)
            .mockReturnValueOnce(second.promise);
        const state = useNewsIntel('search');
        await state.loadOptions();
        state.form.value.query = 'first';
        const firstRun = state.run();
        const firstSignal = hooks.request.mock.calls[1][1]
            .signal as AbortSignal;
        state.form.value.query = 'second';
        const secondRun = state.run();
        expect(firstSignal.aborted).toBe(true);
        second.resolve(search('second'));
        await secondRun;
        first.resolve(search('first'));
        await firstRun;
        expect(state.searchResult.value?.query).toBe('second');
        expect(state.loading.value).toBe(false);
    });

    it('keeps the completed report while another query runs and exports its snapshot without a request', async () => {
        const next = deferred<NewsAnalytics>();
        hooks.request
            .mockResolvedValueOnce(options)
            .mockResolvedValueOnce(snapshot('first'))
            .mockReturnValueOnce(next.promise);
        const state = useNewsIntel('analytics');
        await state.loadOptions();
        state.form.value.query = 'first';
        await state.run();
        state.form.value.query = 'second';
        const pending = state.run();
        expect(state.analyticsResult.value?.query).toBe('first');
        const url = new URL(state.reportUrl('json', true), 'https://app.test');
        expect(url.pathname).toContain(snapshot('first').reportId);
        expect(url.searchParams.get('download')).toBe('1');
        expect(url.searchParams.get('locale')).toBe('ru');
        expect(hooks.request).toHaveBeenCalledTimes(3);
        state.cancel();
        next.resolve(snapshot('second'));
        await pending;
        expect(state.analyticsResult.value?.query).toBe('first');
        expect(state.error.value).toBe('');
    });

    it('shows source-unavailable and validation errors without invented results', async () => {
        hooks.request.mockResolvedValueOnce(options).mockRejectedValueOnce(
            new ApiError({
                ok: false,
                message: 'SearXNG is unavailable.',
                status: 503,
            })
        );
        const state = useNewsIntel('analytics');
        await state.loadOptions();
        state.form.value.query = 'topic';
        await state.run();
        expect(state.analyticsResult.value).toBeNull();
        expect(state.error.value).toBe('SearXNG is unavailable.');
        hooks.request.mockRejectedValueOnce(
            new ApiError({
                ok: false,
                message: 'Validation failed.',
                status: 422,
                errors: { query: ['Choose another query.'] },
            })
        );
        await state.run();
        expect(state.error.value).toBe('Choose another query.');
    });

    it('preserves query-repeat autorun after options have loaded', async () => {
        hooks.params = new URLSearchParams({ query: 'repeated', autorun: '1' });
        hooks.request
            .mockResolvedValueOnce(options)
            .mockResolvedValueOnce(search('repeated'));
        const state = useNewsIntel('search');
        await hooks.mounted[0]();
        await Promise.resolve();
        expect(state.form.value.query).toBe('repeated');
        expect(state.searchResult.value?.query).toBe('repeated');
    });

    it('aborts options and an in-flight request when its tab unmounts', async () => {
        const pending = deferred<NewsAnalytics>();
        hooks.request
            .mockResolvedValueOnce(options)
            .mockReturnValueOnce(pending.promise);
        const state = useNewsIntel('analytics');
        await state.loadOptions();
        state.form.value.query = 'topic';
        const running = state.run();
        const optionSignal = hooks.request.mock.calls[0][1]
            .signal as AbortSignal;
        const runSignal = hooks.request.mock.calls[1][1].signal as AbortSignal;
        hooks.unmounted[0]();
        pending.resolve(snapshot('late'));
        await running;
        expect(optionSignal.aborted).toBe(true);
        expect(runSignal.aborted).toBe(true);
        expect(state.analyticsResult.value).toBeNull();
    });
});
