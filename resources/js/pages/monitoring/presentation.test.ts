import { describe, expect, it } from 'vitest';
import en from '@/locales/en/monitoring.json';
import {
    displayDate,
    reportNeedsPolling,
    scheduleDescription,
    sourceUrl,
} from './presentation';
import { periods } from './types';
describe('monitoring presentation', () => {
    it.each([
        ['queued', 'pending', 'not_requested', false, true],
        ['completed', 'working', 'not_requested', false, true],
        ['partial', 'ready', 'pending', false, true],
        ['completed', 'ready', 'sent', false, false],
        ['partial', 'ready', 'not_linked', false, false],
        ['failed', 'pending', 'not_requested', false, false],
        ['cancelled', 'pending', 'not_requested', false, false],
        ['completed', 'ready', 'pending', true, false],
    ] as const)(
        'refreshes unfinished operations and stops for %s/%s/%s',
        (status, file_status, delivery_status, expired, expected) => {
            expect(
                reportNeedsPolling({
                    status,
                    file_status,
                    delivery_status,
                    expired,
                })
            ).toBe(expected);
        }
    );
    it.each(periods)(
        'describes %s cadence with time and timezone',
        (period) => {
            const t = (key: string, values?: Record<string, string | number>) =>
                Object.entries(values ?? {}).reduce(
                    (value, [name, replacement]) =>
                        value.replaceAll(`{${name}}`, String(replacement)),
                    en.schedule[period]
                );
            const result = scheduleDescription(
                period,
                '09:30:00',
                'Europe/Moscow',
                '2026-10-06',
                t
            );
            expect(result).toContain('09:30');
            expect(result).toContain('Europe/Moscow');

            if (period === 'three_days') {
                expect(result).toContain('2026-10-06');
            }
        }
    );
    it('rejects dangerous original publication links', () => {
        expect(sourceUrl('javascript:alert(1)')).toBeUndefined();
        expect(sourceUrl('https://user:secret@example.org')).toBeUndefined();
        expect(sourceUrl('https://example.org/post')).toBe(
            'https://example.org/post'
        );
    });
    it('shows unknown timestamps safely', () => {
        expect(displayDate(null)).toBe('—');
        expect(displayDate('invalid')).toBe('—');
    });
});
