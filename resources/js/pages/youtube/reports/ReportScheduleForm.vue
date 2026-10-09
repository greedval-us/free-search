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
    maxChannels: number;
    limitReached: boolean;
    channelsError: string;
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
            {{ t('youtubeReports.limitReached') }}
        </p>
        <div class="intel-field">
            <label for="youtube-report-schedule-name" class="intel-label">{{
                t('youtubeReports.name')
            }}</label>
            <input
                id="youtube-report-schedule-name"
                v-model="form.name"
                class="intel-input"
                :maxlength="REPORT_NAME_MAX_LENGTH"
                required
            />
        </div>
        <div class="intel-field">
            <label for="youtube-report-schedule-channels" class="intel-label">{{
                t('youtubeReports.channels', { max: maxChannels })
            }}</label>
            <textarea
                id="youtube-report-schedule-channels"
                v-model="form.channels"
                class="intel-scroll intel-input min-h-24 resize-y"
                rows="4"
                :placeholder="t('youtubeReports.channelsPlaceholder')"
                :aria-invalid="Boolean(channelsError)"
                aria-describedby="youtube-report-channels-help youtube-report-channels-error"
                required
            />
            <span
                id="youtube-report-channels-help"
                class="text-xs text-muted-foreground"
                >{{ t('youtubeReports.channelsHelp') }}</span
            >
            <span
                v-if="channelsError"
                id="youtube-report-channels-error"
                role="alert"
                class="text-xs text-destructive"
                >{{ channelsError }}</span
            >
        </div>
        <div class="grid min-w-0 gap-4 sm:grid-cols-2">
            <div class="intel-field min-w-0">
                <label for="youtube-report-interval" class="intel-label">{{
                    t('youtubeReports.interval')
                }}</label>
                <select
                    id="youtube-report-interval"
                    v-model="form.interval"
                    class="intel-select"
                >
                    <option
                        v-for="interval in REPORT_INTERVALS"
                        :key="interval"
                        :value="interval"
                    >
                        {{ t(`youtubeReports.intervals.${interval}`) }}
                    </option>
                </select>
            </div>
            <div class="intel-field min-w-0">
                <label for="youtube-report-send-time" class="intel-label">{{
                    t('youtubeReports.sendTime')
                }}</label>
                <input
                    id="youtube-report-send-time"
                    v-model="form.sendTime"
                    type="time"
                    class="intel-input"
                    required
                />
            </div>
        </div>
        <p class="text-xs text-muted-foreground">
            {{ t('youtubeReports.periodHelp') }}
        </p>
        <p class="text-xs text-muted-foreground">
            {{ t('youtubeReports.dataHelp') }}
        </p>
        <p class="text-xs text-muted-foreground">
            {{ t('youtubeReports.quotaHelp') }}
        </p>
        <ReportTimezoneField
            v-model="form.timezone"
            id="youtube-report-timezone"
            label-key="youtubeReports.timezone"
            :disabled="busy"
        />
        <div class="space-y-2 rounded-lg border border-border/70 p-3">
            <label class="flex min-h-9 items-center gap-2 text-sm">
                <input
                    v-model="form.sendToBot"
                    type="checkbox"
                    class="size-4 shrink-0 accent-primary"
                />
                {{ t('youtubeReports.sendToBot') }}
            </label>
            <p class="text-xs text-muted-foreground">
                {{ t('youtubeReports.websiteHelp') }}
            </p>
            <div v-if="!botLinked || !botExportsEnabled" class="space-y-1">
                <p class="text-xs text-muted-foreground">
                    {{
                        t(
                            botLinked
                                ? 'youtubeReports.botExportsHelp'
                                : 'youtubeReports.botHelp'
                        )
                    }}
                </p>
                <Link
                    :href="settings()"
                    class="inline-flex min-h-9 items-center text-xs text-primary underline"
                    >{{ t('youtubeReports.botSettings') }}</Link
                >
            </div>
        </div>
        <div class="flex justify-end border-t border-border/60 pt-4">
            <button class="intel-button-primary" :disabled="busy || !canCreate">
                {{ t('youtubeReports.create') }}
            </button>
        </div>
    </form>
</template>
