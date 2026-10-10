import { describe, expect, it } from 'vitest';
import {
    availableNewsEngines,
    competitorNames,
    marketingFormError,
    normalizeVisibilityDomain,
} from './form';
import type { MarketingForm, NewsOptions } from './types';
const form: MarketingForm = {
    query: 'topic',
    brand: '',
    competitors: '',
    domain: '',
};
describe('marketing form normalization', () => {
    it('accepts topic-only analytics and separates competitor names by lines', () => {
        expect(marketingFormError(form)).toBeNull();
        expect(competitorNames(' One \r\n Two\n\n Three ')).toEqual([
            'One',
            'Two',
            'Three',
        ]);
    });
    it('extracts a website host while rejecting credentials, unsafe schemes and malformed domains', () => {
        expect(
            normalizeVisibilityDomain('https://www.Example.com/News?q=1#top')
        ).toBe('example.com');

        for (const value of [
            'javascript:alert(1)',
            'ftp://example.com',
            'https://user:pass@example.com',
            'example.com:8080',
            'localhost',
            'exam ple.com',
            'example.com\\path',
            '.example.com',
            '127.0.0.1',
            'private.local',
            'bad-.example.com',
            'example..com',
        ]) {
            expect(normalizeVisibilityDomain(value)).toBeNull();
        }
    });
    it('enforces backend query and entity length limits', () => {
        expect(marketingFormError({ ...form, query: 'q'.repeat(181) })).toBe(
            'queryRequired'
        );
        expect(marketingFormError({ ...form, brand: 'a' })).toBe(
            'competitorLimit'
        );
        expect(
            marketingFormError({ ...form, competitors: 'a'.repeat(81) })
        ).toBe('competitorLimit');
        expect(
            marketingFormError({
                ...form,
                competitors: 'one\ntwo\nthree\nfour',
            })
        ).toBe('competitorLimit');
    });
    it('rejects duplicated brand and competitor names ignoring case', () => {
        expect(
            marketingFormError({
                ...form,
                brand: 'BRAND',
                competitors: 'brand',
            })
        ).toBe('duplicateCompetitors');
        expect(
            marketingFormError({ ...form, competitors: 'First\nfirst' })
        ).toBe('duplicateCompetitors');
    });
    it('offers only configured engines belonging to selected categories', () => {
        const options = {
            engines: {
                news: ['shared', 'news only'],
                general: ['shared', 'web only'],
            },
        } as NewsOptions;
        expect(availableNewsEngines(options, ['news'])).toEqual([
            'news only',
            'shared',
        ]);
        expect(availableNewsEngines(options, ['news', 'general'])).toEqual([
            'news only',
            'shared',
            'web only',
        ]);
        expect(availableNewsEngines(null, ['news'])).toEqual([]);
    });
});
