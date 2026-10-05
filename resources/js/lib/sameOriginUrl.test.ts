import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { withDownloadLocale } from './downloadLocale';
import { resolveSameOriginUrl } from './sameOriginUrl';

describe('same-origin download URLs', () => {
    beforeEach(() => {
        vi.stubGlobal('window', {
            location: { origin: 'https://free-search.test' },
            localStorage: { getItem: () => 'ru' },
        });
        vi.stubGlobal('document', { documentElement: { lang: 'en' } });
    });

    afterEach(() => vi.unstubAllGlobals());

    it.each([
        'javascript:alert(1)',
        'data:text/html,attack',
        'https://other.test/report',
        '//other.test/report',
        'http://free-search.test/report',
        'https://user:password@free-search.test/report',
        '/report\n/injected',
        'https://[invalid',
        '',
        null,
        {},
    ])('rejects an unsafe API URL: %s', (url) => {
        expect(resolveSameOriginUrl(url)).toBeNull();

        if (typeof url === 'string') {
            expect(withDownloadLocale(url)).toBeNull();
        }
    });

    it.each([
        '/report?format=json',
        'https://free-search.test/report?format=json',
    ])('accepts same-origin relative and absolute URLs: %s', (url) => {
        expect(resolveSameOriginUrl(url)).toBe(
            'https://free-search.test/report?format=json'
        );
        expect(withDownloadLocale(url)).toBe(
            'https://free-search.test/report?format=json&locale=ru'
        );
    });

    it('preserves existing parameters and fragment while replacing the locale', () => {
        expect(withDownloadLocale('/report?format=json&locale=en#items')).toBe(
            'https://free-search.test/report?format=json&locale=ru#items'
        );
    });

    it('uses the document locale when browser storage is unavailable', () => {
        window.localStorage.getItem = () => {
            throw new Error('Storage denied');
        };
        expect(withDownloadLocale('/report')).toBe(
            'https://free-search.test/report?locale=en'
        );
    });
});
