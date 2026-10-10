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
        namespace="siteIntelReports"
        :schedule="schedule"
        :busy="busy"
        @change="$emit('change', $event)"
        @run="$emit('run', $event)"
        @remove="$emit('remove', $event)"
    >
        <template #summary
            ><p class="text-sm [overflow-wrap:anywhere] text-muted-foreground">
                {{ schedule.targets.join(', ') }}
            </p></template
        >
        <template #metadata>
            <p class="text-xs text-muted-foreground">
                {{ t(`siteIntelReports.types.${schedule.reportType}`)
                }}<template v-if="schedule.reportType === 'seo-audit'">
                    ·
                    {{
                        t('siteIntelReports.crawlPages', {
                            count: schedule.crawlLimit,
                        })
                    }}
                    ·
                    {{
                        t(
                            schedule.platformType === 'auto'
                                ? 'siteIntel.seoAudit.platformAuto'
                                : `siteIntel.seoAudit.profile.${schedule.platformType}`
                        )
                    }}</template
                >
            </p>
        </template>
    </SharedReportScheduleCard>
</template>
