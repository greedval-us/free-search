import { describe, expect, it } from 'vitest';
import { isReportAccount, normalizeReportAccount } from './account-input';

describe('Bluesky report accounts', () => {
    it.each([
        [' @News.Bsky.Social ', 'news.bsky.social'],
        ['https://bsky.app/profile/News.Bsky.Social/', 'news.bsky.social'],
        ['bsky.app/profile/news.example.org', 'news.example.org'],
        [
            'did:plc:abcdefghijklmnopqrstuvwx',
            'did:plc:abcdefghijklmnopqrstuvwx',
        ],
        ['https://bsky.app/profile/did:web:Example.com', 'did:web:Example.com'],
    ])('normalizes %s to %s', (input, expected) => {
        const normalized = normalizeReportAccount(input);
        expect(normalized).toBe(expected);
        expect(isReportAccount(normalized)).toBe(true);
    });

    it.each([
        'news',
        '@',
        '-news.bsky.social',
        'news..social',
        'did:plc:short',
        'did:web:localhost',
        'did:web:example.com:actor',
        'news.local',
        'news.test',
        'news.example',
        'http://bsky.app/profile/news.bsky.social',
        'https://bsky.app/profile/news.bsky.social/post/123',
        'https://bsky.app/profile/news.bsky.social?tab=posts',
        'https://bsky.app/profile/news.bsky.social#posts',
        'https://bsky.app:8443/profile/news.bsky.social',
        'https://user@bsky.app/profile/news.bsky.social',
        'https://bsky.app.example.org/profile/news.bsky.social',
        'https://bsky.app/profile/news%2Fbsky.social',
        'https://bsky.app/profile/first/../news.bsky.social',
    ])('rejects unsupported or ambiguous account %s', (input) => {
        expect(isReportAccount(normalizeReportAccount(input))).toBe(false);
    });
});
