import { describe, expect, it, vi } from 'vitest';
import { createSSRApp, h } from 'vue';
import { renderToString } from 'vue/server-renderer';
import { formatReportCalendarDate } from './format';
import ReportHistoryPanel from './ReportHistoryPanel.vue';
import ReportScheduleCard from './ReportScheduleCard.vue';
import ReportTimezoneField from './ReportTimezoneField.vue';
import type { PersistedSchedule, SavedReport } from './types';

vi.mock('@/composables/useI18n', () => ({
    useI18n: () => ({ t: (key: string) => key, locale: { value: 'en' } }),
}));

describe('shared scheduled report component contracts', () => {
    it('renders typed report slots safely and exposes files only for completed reports', async () => {
        const failed: SavedReport & { target: string } = {
            id: 1,
            scheduleId: null,
            scheduleName: '<script>schedule</script>',
            scheduledFor: '2026-10-09T06:00:00Z',
            status: 'failed',
            errorCode: 'unavailable',
            errorMessage: '<img src=x onerror=alert(1)>',
            completedAt: null,
            target: '<script>target</script>',
        };
        const html = await renderToString(
            createSSRApp({
                render: () =>
                    h(
                        ReportHistoryPanel,
                        {
                            namespace: 'reports',
                            history: {
                                data: [
                                    failed,
                                    { ...failed, id: 2, status: 'completed' },
                                ],
                                currentPage: 1,
                                lastPage: 2,
                                total: 2,
                                perPage: 20,
                            },
                            busy: true,
                            viewUrl: (id: number) => `/reports/${id}/view`,
                            downloadUrl: (
                                id: number,
                                format: 'html' | 'json'
                            ) => `/reports/${id}/${format}`,
                        },
                        {
                            target: ({ report }: { report: typeof failed }) =>
                                h('span', report.target),
                            metadata: () => h('p', 'Module metadata'),
                        }
                    ),
            })
        );
        expect(html).toContain('&lt;script&gt;');
        expect(html).toContain('&lt;img');
        expect(html).toContain('Module metadata');
        expect(html).not.toContain('<script>');
        expect(html).not.toContain('href="/reports/1/view"');
        expect(html).toContain('href="/reports/2/view"');
        expect(html).toContain('href="/reports/2/html"');
        expect(html).toContain('reports.historyPages');
        expect(html).toContain('disabled');
    });

    it('retains schedule timing, custom module data and disabled actions in the shared card', async () => {
        const schedule: PersistedSchedule = {
            id: 1,
            name: '<script>monitor</script>',
            interval: 'month',
            sendTime: '09:00',
            timezone: 'Europe/Moscow',
            sendToBot: true,
            enabled: true,
            nextRunAt: '2026-11-01T06:00:00Z',
            createdAt: '2026-10-09T06:00:00Z',
        };
        const html = await renderToString(
            createSSRApp({
                render: () =>
                    h(
                        ReportScheduleCard,
                        { namespace: 'reports', schedule, busy: true },
                        {
                            summary: () => h('p', 'Module targets'),
                            metadata: () => h('p', 'Module filters'),
                        }
                    ),
            })
        );
        expect(html).toContain('&lt;script&gt;monitor');
        expect(html).toContain('Module targets');
        expect(html).toContain('Module filters');
        expect(html).toContain('reports.intervals.month');
        expect(html).toContain('reports.deliveryBot');
        expect(html).toContain('reportTimezones.cities.moscow');
        expect(html).toContain('reports.pause');
        expect(html).toContain('reports.runNow');
        expect(html).toContain('reports.delete');
        expect(html).not.toContain('reports.confirmDelete');
        expect(html).toContain('disabled');
    });

    it('keeps a timezone absent from the presets as a selected option without a free-text field', async () => {
        const html = await renderToString(
            createSSRApp(ReportTimezoneField, {
                id: 'timezone',
                labelKey: 'reports.timezone',
                modelValue: 'Pacific/Auckland',
                disabled: true,
            })
        );
        expect(html).toContain('value="Pacific/Auckland" selected');
        expect(html).toContain('aria-describedby="timezone-help"');
        expect(html).toContain('reportTimezones.help');
        expect(html).not.toContain('<input');
        expect(html).toContain('disabled');
    });

    it('formats reporting period calendar dates without applying the viewer timezone', () => {
        expect(
            formatReportCalendarDate('2026-10-09T23:00:00-05:00', 'en')
        ).toBe(
            new Date('2026-10-09T00:00:00Z').toLocaleDateString('en', {
                timeZone: 'UTC',
            })
        );
    });
});
