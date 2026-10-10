<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import {
    REPORT_INTERVALS,
    REPORT_NAME_MAX_LENGTH,
} from '@/features/scheduled-reports/constants';
import ReportTimezoneField from '@/features/scheduled-reports/ReportTimezoneField.vue';
import { settings } from '@/routes/telegram-bot';
import type { ReportScheduleForm } from './types';

defineProps<{
    busy: boolean;
    botLinked: boolean;
    botExportsEnabled: boolean;
    maxGroups: number;
    limitReached: boolean;
    groupsError: string;
    canCreate: boolean;
}>();
defineEmits<{ create: [] }>();
const form = defineModel<ReportScheduleForm>({ required: true });
const { t } = useI18n();
</script>

<template>
    <form class="min-w-0 space-y-4" @submit.prevent="$emit('create')">
        <p
            v-if="limitReached"
            role="status"
            class="rounded-lg border border-border p-3 text-sm text-muted-foreground"
        >
            {{ t('telegramReports.limitReached') }}
        </p>
        <div class="intel-field">
            <label for="report-schedule-name" class="intel-label">{{
                t('telegramReports.name')
            }}</label>
            <input
                id="report-schedule-name"
                v-model="form.name"
                class="intel-input"
                :maxlength="REPORT_NAME_MAX_LENGTH"
                required
            />
        </div>
        <div class="intel-field">
            <label for="report-schedule-groups" class="intel-label">{{
                t('telegramReports.groups', { max: maxGroups })
            }}</label>
            <textarea
                id="report-schedule-groups"
                v-model="form.groups"
                class="intel-scroll intel-input min-h-24 resize-y"
                rows="4"
                :placeholder="t('telegramReports.groupsPlaceholder')"
                :aria-invalid="Boolean(groupsError)"
                aria-describedby="report-groups-help report-groups-error"
                required
            />
            <span
                id="report-groups-help"
                class="text-xs text-muted-foreground"
                >{{ t('telegramReports.groupsHelp') }}</span
            >
            <span
                v-if="groupsError"
                id="report-groups-error"
                role="alert"
                class="text-xs text-destructive"
                >{{ groupsError }}</span
            >
        </div>
        <div class="grid min-w-0 gap-4 sm:grid-cols-2">
            <div class="intel-field min-w-0">
                <label for="report-interval" class="intel-label">{{
                    t('telegramReports.interval')
                }}</label>
                <select
                    id="report-interval"
                    v-model="form.interval"
                    class="intel-select"
                >
                    <option
                        v-for="interval in REPORT_INTERVALS"
                        :key="interval"
                        :value="interval"
                    >
                        {{ t(`telegramReports.intervals.${interval}`) }}
                    </option>
                </select>
            </div>
            <div class="intel-field min-w-0">
                <label for="report-send-time" class="intel-label">{{
                    t('telegramReports.sendTime')
                }}</label>
                <input
                    id="report-send-time"
                    v-model="form.sendTime"
                    type="time"
                    class="intel-input"
                    required
                />
            </div>
        </div>
        <p class="text-xs text-muted-foreground">
            {{ t('telegramReports.periodHelp') }}
        </p>
        <p class="text-xs text-muted-foreground">
            {{ t('telegramReports.quotaHelp') }}
        </p>
        <ReportTimezoneField
            v-model="form.timezone"
            id="report-timezone"
            label-key="telegramReports.timezone"
            :disabled="busy"
        />
        <div class="space-y-2 rounded-lg border border-border/70 p-3">
            <label class="flex min-h-9 items-center gap-2 text-sm">
                <input
                    v-model="form.sendToBot"
                    type="checkbox"
                    class="size-4 shrink-0 accent-primary"
                />
                {{ t('telegramReports.sendToBot') }}
            </label>
            <p class="text-xs text-muted-foreground">
                {{ t('telegramReports.websiteHelp') }}
            </p>
            <div v-if="!botLinked || !botExportsEnabled" class="space-y-1">
                <p class="text-xs text-muted-foreground">
                    {{
                        t(
                            botLinked
                                ? 'telegramReports.botExportsHelp'
                                : 'telegramReports.botHelp'
                        )
                    }}
                </p>
                <Link
                    :href="settings()"
                    class="inline-flex min-h-9 items-center text-xs text-primary underline"
                    >{{ t('telegramReports.botSettings') }}</Link
                >
            </div>
        </div>
        <div class="flex justify-end border-t border-border/60 pt-4">
            <button class="intel-button-primary" :disabled="busy || !canCreate">
                {{ t('telegramReports.create') }}
            </button>
        </div>
    </form>
</template>
