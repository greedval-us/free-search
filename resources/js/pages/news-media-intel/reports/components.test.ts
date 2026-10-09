import { describe, expect, it, vi } from 'vitest';
import { createSSRApp } from 'vue';
import { renderToString } from 'vue/server-renderer';
import { initialNewsFilters } from '../form';
import ReportHistoryPanel from './ReportHistoryPanel.vue';
import ReportScheduleForm from './ReportScheduleForm.vue';
import type { ReportScheduleForm as ScheduleForm, SavedReport } from './types';

vi.mock('@/composables/useI18n', () => ({
    useI18n: () => ({ t: (key: string) => key, locale: { value: 'en' } }),
}));

describe('news schedule interface', () => {
    it('provides city time zones and four cadence choices with independent analytics filters', async () => {
        const form: ScheduleForm = {
            name: '',
            queries: '',
            brand: '',
            competitors: '',
            domain: '',
            filters: initialNewsFilters(),
            interval: '7',
            sendTime: '09:00',
            timezone: 'Europe/Moscow',
            sendToBot: false,
        };
        const html = await renderToString(
            createSSRApp(ReportScheduleForm, {
                modelValue: form,
                busy: false,
                botLinked: true,
                botExportsEnabled: true,
                maxQueries: 3,
                limitReached: false,
                validationError: '',
                canCreate: false,
                options: null,
                optionsError: '',
            })
        );
        expect(html).toContain('id="news-report-timezone"');
        expect(html).toContain('value="Europe/Moscow"');
        expect(html).toContain('reportTimezones.cities.moscow');

        for (const value of ['1', '3', '7', 'month']) {
            expect(html).toContain(`value="${value}"`);
        }

        expect(html).toContain('newsMediaReports.dataHelp');
        expect(html).toContain('newsMediaIntel.filters.timeRange');
    });
    it('escapes saved query and error text and only exposes completed report downloads', async () => {
        const base: SavedReport = {
            id: 8,
            scheduleId: null,
            scheduleName: '<img src=x onerror=alert(1)>',
            query: '<script>alert(1)</script>',
            scheduledFor: '2026-10-09T06:00:00Z',
            status: 'failed',
            errorCode: 'unavailable',
            errorMessage: '<svg onload=alert(1)>',
            completedAt: null,
        };
        const html = await renderToString(
            createSSRApp(ReportHistoryPanel, {
                history: {
                    data: [base, { ...base, id: 9, status: 'completed' }],
                    currentPage: 1,
                    lastPage: 1,
                    total: 2,
                    perPage: 20,
                },
                busy: false,
                viewUrl: (id: number) => `/reports/${id}/view`,
                downloadUrl: (id: number, format: 'html' | 'json') =>
                    `/reports/${id}/${format}`,
            })
        );
        expect(html).toContain('&lt;script&gt;');
        expect(html).toContain('&lt;svg');
        expect(html).not.toContain('<script>');
        expect(html).not.toContain('href="/reports/8/view"');
        expect(html).toContain('href="/reports/9/view"');
        expect(html).toContain('href="/reports/9/html"');
        expect(html).toContain('href="/reports/9/json"');
    });
});
