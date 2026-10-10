<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import SharedReportScheduleCard from '@/features/scheduled-reports/ReportScheduleCard.vue';
import type { ReportSchedule } from './types';
defineProps<{ schedule: ReportSchedule; busy: boolean }>();
defineEmits<{
    change: [schedule: ReportSchedule];
    run: [schedule: ReportSchedule];
    remove: [schedule: ReportSchedule];
}>();
const { t } = useI18n();
</script>

<template>
    <SharedReportScheduleCard
        namespace="newsMediaReports"
        :schedule="schedule"
        :busy="busy"
        @change="$emit('change', $event)"
        @run="$emit('run', $event)"
        @remove="$emit('remove', $event)"
    >
        <template #summary
            ><p class="text-sm [overflow-wrap:anywhere] text-muted-foreground">
                {{ schedule.queries.join(', ') }}
            </p></template
        >
        <template #metadata>
            <p
                v-if="schedule.brand || schedule.competitors.length"
                class="text-xs [overflow-wrap:anywhere] text-muted-foreground"
            >
                {{
                    [schedule.brand, ...schedule.competitors]
                        .filter(Boolean)
                        .join(' · ')
                }}
            </p>
            <p
                v-if="schedule.domain"
                class="text-xs [overflow-wrap:anywhere] text-muted-foreground"
            >
                {{ schedule.domain }}
            </p>
            <p class="text-xs text-muted-foreground">
                {{ t('newsMediaReports.searchPeriod') }}:
                {{
                    t(
                        `newsMediaIntel.filters.${schedule.searchOptions.timeRange || 'allTime'}`
                    )
                }}
            </p>
        </template>
    </SharedReportScheduleCard>
</template>
