import { describe, expect, it } from 'vitest';
import { initialNewsFilters } from '../form';
import { reportFormError, reportQueries } from './form';
import type { ReportScheduleForm } from './types';

const form = (
    values: Partial<ReportScheduleForm> = {}
): ReportScheduleForm => ({
    name: 'Monitoring',
    queries: 'Industry news',
    brand: '',
    competitors: '',
    domain: '',
    filters: initialNewsFilters(),
    interval: '7',
    sendTime: '09:00',
    timezone: 'Europe/Moscow',
    sendToBot: false,
    ...values,
});

describe('news report schedule input', () => {
    it('keeps spaces within queries and trims blank lines', () => {
        expect(reportQueries(' Industry news \r\n\n Product launch ')).toEqual([
            'Industry news',
            'Product launch',
        ]);
    });
    it('rejects duplicate topics regardless of case and bounds the number of reports', () => {
        expect(reportFormError(form({ queries: 'Новости\nНОВОСТИ' }), 3)).toBe(
            'duplicateQueries'
        );
        expect(
            reportFormError(form({ queries: 'one\ntwo\nthree\nfour' }), 3)
        ).toBe('tooManyQueries');
        expect(reportFormError(form({ queries: 'a' }), 3)).toBe(
            'invalidQueries'
        );
    });
    it.each(['!google Brand', 'Brand :en', 'Brand\u0000'])(
        'rejects query routing and control characters: %s',
        (queries) => {
            expect(reportFormError(form({ queries }), 3)).toBe('queryRouting');
        }
    );
    it('allows three distinct competitor names while checking brand duplicates and public domains', () => {
        expect(
            reportFormError(
                form({
                    brand: 'Brand',
                    competitors: 'Other\nThird\nFourth',
                    domain: 'https://www.example.com/path',
                }),
                3
            )
        ).toBeNull();
        expect(
            reportFormError(form({ brand: 'Brand', competitors: 'brand' }), 3)
        ).toBe('duplicateCompetitors');
        expect(
            reportFormError(form({ domain: 'https://user@example.com' }), 3)
        ).toBe('invalidDomain');
        expect(
            reportFormError(form({ competitors: 'Safe\nBad\u0000' }), 3)
        ).toBe('invalidNames');
    });
});
