import { describe, expect, it } from 'vitest';
import { isReportAccount, normalizeReportAccount } from './account-input';

describe('Mastodon report accounts', () => {
    it.each([
        [' @News@Mastodon.Social ', 'news@mastodon.social'],
        ['News@Mastodon.Social', 'news@mastodon.social'],
        ['https://mastodon.social/@News/', 'news@mastodon.social'],
        ['https://mastodon.social/users/News/', 'news@mastodon.social'],
        ['@Local_News', 'local_news'],
        ['Local_News', 'local_news'],
    ])('normalizes %s to %s', (input, expected) => {
        const normalized = normalizeReportAccount(input);
        expect(normalized).toBe(expected);
        expect(isReportAccount(normalized)).toBe(true);
    });

    it.each([
        '@',
        'user@@mastodon.social',
        'user@',
        'user@-example.org',
        'user@localhost',
        'user@server.test',
        'user@server.local',
        `${'a'.repeat(65)}@mastodon.social`,
        'https://mastodon.social/@news/123',
        'https://mastodon.social/@news?tab=posts',
        'https://mastodon.social/@news#about',
        'https://mastodon.social:8443/@news',
        'https://mastodon.social:443/@news',
        'https://user@mastodon.social/@news',
        'https://127.0.0.1/@news',
        'https://mastodon.social/@news%2Fother',
        'https://mastodon.social/users/first/../@news',
    ])('rejects unsupported or ambiguous account %s', (input) => {
        expect(isReportAccount(normalizeReportAccount(input))).toBe(false);
    });
});
