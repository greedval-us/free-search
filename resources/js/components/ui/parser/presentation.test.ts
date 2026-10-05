import { describe, expect, it } from 'vitest';
import {
    boundedProgress,
    formatParserDateTime,
    isPartialParserExport,
    normalizeParserStatus,
    parserProgressStatus,
} from './presentation';

describe('parser presentation', () => {
    it('keeps progress within the accessible progressbar range', () => {
        expect(boundedProgress(-12)).toBe(0);
        expect(boundedProgress(123)).toBe(100);
        expect(boundedProgress(42.5)).toBe(42.5);
        expect(boundedProgress(Number.NaN)).toBe(0);
        expect(boundedProgress(Number.POSITIVE_INFINITY)).toBe(0);
    });

    it('shows an unknown status for new or missing server statuses', () => {
        expect(normalizeParserStatus('completed')).toBe('completed');
        expect(normalizeParserStatus('paused')).toBe('unknown');
        expect(normalizeParserStatus(null)).toBe('unknown');
    });

    it('presents an active collection as running without confusing its stage with status', () => {
        expect(parserProgressStatus('comments', true)).toBe('running');
        expect(parserProgressStatus('finishing', true)).toBe('running');
        expect(parserProgressStatus('completed', false)).toBe('completed');
        expect(parserProgressStatus('failed', false)).toBe('failed');
        expect(parserProgressStatus('stopped', false)).toBe('stopped');
        expect(parserProgressStatus('idle', false)).toBe('idle');
    });

    it('marks downloadable interrupted results as partial', () => {
        expect(
            isPartialParserExport({ status: 'stopped', downloadable: true })
        ).toBe(true);
        expect(
            isPartialParserExport({ status: 'failed', downloadable: true })
        ).toBe(true);
        expect(
            isPartialParserExport({ status: 'completed', downloadable: true })
        ).toBe(false);
        expect(
            isPartialParserExport({ status: 'running', downloadable: false })
        ).toBe(false);
        expect(
            isPartialParserExport({ status: 'failed', downloadable: false })
        ).toBe(false);
    });

    it('allows history to render when a date is missing or malformed', () => {
        expect(formatParserDateTime(null, 'en')).toBeNull();
        expect(formatParserDateTime('invalid-date', 'ru')).toBeNull();
        expect(formatParserDateTime('2026-10-06T10:00:00Z', 'en')).toContain(
            '2026'
        );
        expect(formatParserDateTime('2026-10-06T10:00:00Z', 'ru')).toContain(
            '2026'
        );
    });
});
