<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import { formatReportCalendarDate } from '@/features/scheduled-reports/format';
import SharedReportHistoryPanel from '@/features/scheduled-reports/ReportHistoryPanel.vue';
import type { ReportFormat } from '@/features/scheduled-reports/types';
import type { ReportHistory } from './types';

defineProps<{
    history: ReportHistory;
    busy: boolean;
    viewUrl: (id: number) => string;
    downloadUrl: (id: number, format: ReportFormat) => string;
}>();
defineEmits<{ page: [page: number] }>();
const { t, locale } = useI18n();
</script>

<template>
    <SharedReportHistoryPanel
        namespace="mastodonReports"
        :history="history"
        :busy="busy"
        :view-url="viewUrl"
        :download-url="downloadUrl"
        @page="$emit('page', $event)"
    >
        <template #target="{ report }">{{ report.accountInput }}</template>
        <template #metadata="{ report }">
            <p class="text-xs text-muted-foreground">
                {{ t('mastodonReports.reportPeriod') }}:
                {{ formatReportCalendarDate(report.dateFrom, locale) }} —
                {{ formatReportCalendarDate(report.dateTo, locale) }}
            </p>
        </template>
    </SharedReportHistoryPanel>
</template>
