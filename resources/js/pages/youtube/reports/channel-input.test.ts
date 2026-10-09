import { describe, expect, it } from 'vitest';
import {
    CHANNEL_HANDLE_PATTERN,
    CHANNEL_ID_PATTERN,
    normalizeReportChannel,
} from './channel-input';

const isChannel = (value: string) =>
    CHANNEL_ID_PATTERN.test(value) || CHANNEL_HANDLE_PATTERN.test(value);

describe('YouTube report channel inputs', () => {
    it.each([
        [' @Example ', '@example'],
        ['youtube.com/@Example/', '@example'],
        ['HTTP://WWW.YOUTUBE.COM/@Example', '@example'],
        [
            'https://m.youtube.com/@%D0%9D%D0%BE%D0%B2%D0%BE%D1%81%D1%82%D0%B8',
            '@новости',
        ],
        [
            'https://youtube.com/channel/UCAbCdEfGhIjKlMnOpQrStUv/',
            'UCAbCdEfGhIjKlMnOpQrStUv',
        ],
        ['UCAbCdEfGhIjKlMnOpQrStUv', 'UCAbCdEfGhIjKlMnOpQrStUv'],
    ])('normalizes %s to %s', (input, channel) => {
        expect(normalizeReportChannel(input)).toBe(channel);
        expect(isChannel(channel)).toBe(true);
    });

    it.each([
        'https://youtube.com:443/@news',
        'https://youtube.com:8080/@news',
        'https://youtube.com/@first/../@news',
        'https://youtube.com/@news?feature=shared',
        'https://youtube.com/@news#about',
        'https://someone@youtube.com/@news',
        'https://youtube.com.example.org/@news',
        'https://youtube.com/@news/videos',
        'https://youtube.com/c/news',
        'https://youtu.be/AbCdEfGhIjK',
        'https://youtube.com/channel/ucAbCdEfGhIjKlMnOpQrStUv',
        'https://youtube.com/channel/UCAbCdEfGhIjKlMnOpQrStUv/more',
        'https://youtube.com/@bad%2Fhandle',
        'https://youtube.com/@bad%escape',
        'news',
        'UCshort',
        '@a',
    ])('keeps unsupported input %s invalid', (input) => {
        expect(isChannel(normalizeReportChannel(input))).toBe(false);
    });
});
