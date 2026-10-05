import { describe, expect, it } from 'vitest';
import { resolveArticleUrl } from './articleUrl';

describe('news article URLs', () => {
    it.each([
        'javascript:alert(1)',
        'data:text/html,<script>alert(1)</script>',
        'ftp://example.com/article',
        'mailto:reporter@example.com',
        '/relative/article',
        '//example.com/article',
        'https://user:password@example.com/article',
        'https://user@example.com/article',
        'https://example.com/article\n?injected=1',
        'https://example.com/\tarticle',
        'https://example.com/\u0000article',
        'https://example.com/\u007farticle',
        'https://example.com/\u0085article',
        'https://[invalid',
        '',
        null,
        {},
    ])('rejects unsafe or malformed article links: %s', (value) => {
        expect(resolveArticleUrl(value)).toBeNull();
    });

    it.each([
        'https://external-news.example/article?language=ru#details',
        'http://public-news.example/article',
    ])('keeps valid external HTTP(S) article links: %s', (value) => {
        expect(resolveArticleUrl(value)).toBe(value);
    });

    it('normalizes a valid absolute URL before using it as href', () => {
        expect(resolveArticleUrl('HTTPS://EXTERNAL-NEWS.EXAMPLE/article')).toBe(
            'https://external-news.example/article'
        );
    });
});
