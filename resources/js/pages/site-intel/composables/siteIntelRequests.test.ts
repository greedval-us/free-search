import { afterEach, expect, it, vi } from 'vitest';
import { createSSRApp } from 'vue';
import { renderToString } from 'vue/server-renderer';
import { apiRequest } from '@/lib/api';
import SeoAuditTab from '../tabs/SeoAuditTab.vue';
import { useDomainLite } from './useDomainLite';
import { useSeoAudit } from './useSeoAudit';
import { useSiteHealth } from './useSiteHealth';
import { useSiteIntelAnalytics } from './useSiteIntelAnalytics';

vi.mock('@/lib/api', () => ({ apiRequest: vi.fn() }));
vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: { locale: 'en' } }),
}));

const t = (key: string) => key;

afterEach(() => {
    vi.unstubAllGlobals();
    vi.resetAllMocks();
});

it('renders the empty manual audit with its crawl presets and platform choices during SSR', async () => {
    const html = await renderToString(createSSRApp(SeoAuditTab));
    const optionValues = Array.from(
        html.matchAll(/<option[^>]*value="([^"]+)"/g),
        ([, value]) => value
    );
    expect(optionValues).toEqual([
        '5',
        '8',
        '12',
        '20',
        'auto',
        'generic',
        'media-platform',
        'content-site',
        'storefront',
    ]);
    expect(html).toContain('<option value="8" selected>');
    expect(html).toContain('<option value="auto" selected>');
    expect(html).toContain('class="intel-empty"');
    expect(apiRequest).not.toHaveBeenCalled();
});

it('sends domain lookup and site health inputs to their respective endpoints', async () => {
    vi.mocked(apiRequest).mockResolvedValue({ ok: true, data: {} });
    const domain = useDomainLite(t);
    domain.form.domain = '  example.org ';
    await domain.lookup();
    expect(apiRequest).toHaveBeenLastCalledWith('/site-intel/domain-lite', {
        method: 'GET',
        query: { domain: 'example.org' },
    });

    const health = useSiteHealth(t);
    health.form.target = '  https://example.org ';
    await health.check();
    expect(apiRequest).toHaveBeenLastCalledWith('/site-intel/site-health', {
        method: 'GET',
        query: { target: 'https://example.org' },
    });
});

it('opens or downloads the checked SEO URL with the current audit settings and document language', async () => {
    const open = vi.fn();
    vi.stubGlobal('window', { open });
    vi.stubGlobal('document', { documentElement: { lang: 'ru-RU' } });
    vi.mocked(apiRequest).mockResolvedValue({
        ok: true,
        data: { target: { finalUrl: 'https://example.org/path?a=b&c=d' } },
    });
    const audit = useSeoAudit(t);
    Object.assign(audit.form, {
        target: ' example.org ',
        crawlLimit: 12,
        platformType: 'storefront',
    });
    await audit.analyze();
    expect(apiRequest).toHaveBeenCalledWith('/site-intel/seo-audit', {
        method: 'GET',
        query: {
            target: 'example.org',
            crawl_limit: '12',
            platform_type: 'storefront',
        },
    });
    audit.openReport();
    audit.downloadReport();
    const url =
        '/site-intel/seo-report?target=https%3A%2F%2Fexample.org%2Fpath%3Fa%3Db%26c%3Dd&locale=ru&crawl_limit=12&platform_type=storefront';
    expect(open.mock.calls).toEqual([
        [url, '_blank', 'noopener,noreferrer'],
        [url + '&download=1', '_blank', 'noopener,noreferrer'],
    ]);
});

it('enables analytics report actions after analysis and exports the resolved target', async () => {
    const open = vi.fn();
    vi.stubGlobal('window', { open });
    vi.stubGlobal('document', { documentElement: { lang: 'en' } });
    vi.mocked(apiRequest).mockResolvedValue({
        ok: true,
        data: { target: { url: 'https://example.org/' } },
    });
    const state = useSiteIntelAnalytics(t);
    state.form.target = ' example.org ';
    state.openReport();
    expect(open).not.toHaveBeenCalled();
    await state.analyze();
    expect(apiRequest).toHaveBeenCalledWith('/site-intel/analytics', {
        method: 'GET',
        query: { target: 'example.org' },
    });
    state.openReport();
    state.downloadReport();
    const url =
        '/site-intel/report?target=https%3A%2F%2Fexample.org%2F&locale=en';
    expect(open.mock.calls).toEqual([
        [url, '_blank', 'noopener,noreferrer'],
        [url + '&download=1', '_blank', 'noopener,noreferrer'],
    ]);
});
