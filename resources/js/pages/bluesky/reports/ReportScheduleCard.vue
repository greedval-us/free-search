<script setup lang="ts">
import SharedReportScheduleCard from '@/features/scheduled-reports/ReportScheduleCard.vue';
import type { ReportSchedule } from './types';
defineProps<{ schedule: ReportSchedule; busy: boolean }>();
defineEmits<{
    change: [schedule: ReportSchedule];
    run: [schedule: ReportSchedule];
    remove: [schedule: ReportSchedule];
}>();
</script>

<template>
    <SharedReportScheduleCard
        namespace="blueskyReports"
        :schedule="schedule"
        :busy="busy"
        @change="$emit('change', $event)"
        @run="$emit('run', $event)"
        @remove="$emit('remove', $event)"
    >
        <template #summary
            ><p class="text-sm [overflow-wrap:anywhere] text-muted-foreground">
                {{ schedule.accounts.join(', ') }}
            </p></template
        >
    </SharedReportScheduleCard>
</template>
