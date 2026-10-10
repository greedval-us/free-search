import { describe, expect, it } from 'vitest';
import { normalizeReportTarget } from './target-input';

describe('scheduled site report targets', () => {
    it('accepts domains and preserves case-sensitive paths and queries', () => {
        expect(normalizeReportTarget(' Example.COM ')).toBe(
            'https://example.com/'
        );
        expect(
            normalizeReportTarget(
                'HTTPS://EXAMPLE.COM/Catalog?Product=ABC#section'
            )
        ).toBe('https://example.com/Catalog?Product=ABC');
        expect(normalizeReportTarget('http://example.com/page')).toBe(
            'http://example.com/page'
        );
    });

    it.each([
        'https://user:password@example.com/',
        'https://example.com:443/',
        'https://example.com:8080/',
        'https://127.0.0.1/',
        'https://[::1]/',
        'https://localhost/',
        'https://app.local/',
        'https://app.internal/',
        'https://app.test/',
        'https://example.com/\u0000path',
        'https://example.com./',
        'ftp://example.com/',
        'javascript:alert(1)',
        'example.com another.com',
        'https://example.com/\\evil.com',
        '',
    ])('rejects unsupported or ambiguous input %s', (input) => {
        expect(normalizeReportTarget(input)).toBeNull();
    });

    it('rejects normalized URLs that exceed the server target limit', () => {
        expect(
            normalizeReportTarget(`example.com/${'a'.repeat(500)}`)
        ).toBeNull();
    });
});
