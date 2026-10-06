<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import { show } from '@/routes/monitoring/reports';
import { displayDate } from './presentation';
import type { Report } from './types';
defineProps<{ reports: Report[] }>();
const { t, locale } = useI18n();
</script>
<template>
    <p v-if="!reports.length" class="text-sm text-muted-foreground">
        {{ t('monitoring.noReports') }}
    </p>
    <div class="grid gap-3">
        <Link
            v-for="report in reports"
            :key="report.id"
            :href="show(report.id)"
            class="grid gap-2 rounded-xl border border-sidebar-border/70 p-4 hover:bg-muted/40"
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="font-medium"
                    >{{ report.project_name ?? t('monitoring.report') }} #{{
                        report.id
                    }}
                    · v{{ report.version }}</span
                ><span class="rounded-md bg-muted px-2 py-1 text-xs">{{
                    t(`monitoring.status.${report.status}`)
                }}</span>
            </div>
            <p class="text-sm">
                {{ t(`monitoring.period.${report.period}`) }} ·
                {{ displayDate(report.start_at, report.timezone, locale) }} →
                {{ displayDate(report.end_at, report.timezone, locale) }}
            </p>
            <p class="text-xs text-muted-foreground">
                {{ t('monitoring.created') }}:
                {{ displayDate(report.created_at, report.timezone, locale) }} ·
                {{ t('monitoring.delivery') }}:
                {{ t(`monitoring.deliveryStatus.${report.delivery_status}`) }}
            </p>
            <p v-if="report.expired" class="text-sm text-destructive">
                {{ t('monitoring.expired') }}
            </p>
        </Link>
    </div>
</template>
