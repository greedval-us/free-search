import { describe, expect, it, vi } from 'vitest';
import { createSSRApp } from 'vue';
import { renderToString } from 'vue/server-renderer';
import CoverageSummary from './CoverageSummary.vue';
import DomainVisibility from './DomainVisibility.vue';
import NewsDiscovery from './NewsDiscovery.vue';
vi.mock('@/composables/useI18n', () => ({
    useI18n: () => ({
        t: (key: string, params?: Record<string, unknown>) =>
            `${key}${params ? JSON.stringify(params) : ''}`,
        locale: { value: 'en' },
    }),
}));
describe('partial source result presentation', () => {
    it('does not report zero domain visibility when web search is unavailable', async () => {
        const html = await renderToString(
            createSSRApp(DomainVisibility, {
                available: false,
                visibility: {
                    domain: 'example.com',
                    generalResults: 0,
                    domainMatches: 0,
                    share: 0,
                    bestObservedPosition: null,
                    pages: [],
                    method: 'observed_general_results',
                },
            })
        );
        expect(html).toContain(
            'newsMediaIntel.analytics.visibilityUnavailable'
        );
        expect(html).not.toContain('newsMediaIntel.analytics.domainNotFound');
        expect(html).not.toContain('newsMediaIntel.analytics.domainShare');
    });
    it('renders an unavailable category even when counters and engine arrays are omitted', async () => {
        const html = await renderToString(
            createSSRApp(CoverageSummary, {
                coverage: {
                    status: 'unavailable',
                    truncated: true,
                    stopReason: 'service_unavailable',
                },
            })
        );
        expect(html).toContain('newsMediaIntel.coverage.unavailable');
        expect(html).toContain('&quot;loaded&quot;:0');
    });
    it('does not describe an exhausted result set as a failed partial search', async () => {
        const html = await renderToString(
            createSSRApp(CoverageSummary, {
                coverage: {
                    pagesLoaded: 1,
                    pagesRequested: 3,
                    engines: [],
                    unresponsiveEngines: [],
                    truncated: false,
                    stopReason: 'exhausted',
                },
            })
        );
        expect(html).toContain('newsMediaIntel.coverage.loaded');
        expect(html).not.toContain('newsMediaIntel.coverage.partial');
    });
    it('escapes plain answers and infobox content and accepts only safe source links', async () => {
        const html = await renderToString(
            createSSRApp(NewsDiscovery, {
                answers: ['<script>alert(1)</script>'],
                infoboxes: [
                    {
                        title: 'Source',
                        content: '<img src=x onerror=alert(1)>',
                        urls: [
                            'https://example.com',
                            { title: 'Unsafe', url: 'javascript:alert(1)' },
                        ],
                    },
                ],
            })
        );
        expect(html).toContain('&lt;script&gt;');
        expect(html).toContain('&lt;img');
        expect(html).toContain('href="https://example.com/"');
        expect(html).not.toContain('href="javascript:');
    });
});
