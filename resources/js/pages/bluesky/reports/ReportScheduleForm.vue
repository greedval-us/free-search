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
    maxAccounts: number;
    limitReached: boolean;
    accountsError: string;
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
            {{ t('blueskyReports.limitReached') }}
        </p>
        <div class="intel-field">
            <label for="bluesky-report-schedule-name" class="intel-label">{{
                t('blueskyReports.name')
            }}</label>
            <input
                id="bluesky-report-schedule-name"
                v-model="form.name"
                class="intel-input"
                :maxlength="REPORT_NAME_MAX_LENGTH"
                required
            />
        </div>
        <div class="intel-field">
            <label for="bluesky-report-schedule-accounts" class="intel-label">{{
                t('blueskyReports.accounts', { max: maxAccounts })
            }}</label>
            <textarea
                id="bluesky-report-schedule-accounts"
                v-model="form.accounts"
                class="intel-scroll intel-input min-h-24 resize-y"
                rows="4"
                :placeholder="t('blueskyReports.accountsPlaceholder')"
                :aria-invalid="Boolean(accountsError)"
                aria-describedby="bluesky-report-accounts-help bluesky-report-accounts-error"
                required
            />
            <span
                id="bluesky-report-accounts-help"
                class="text-xs text-muted-foreground"
                >{{ t('blueskyReports.accountsHelp') }}</span
            >
            <span
                v-if="accountsError"
                id="bluesky-report-accounts-error"
                role="alert"
                class="text-xs text-destructive"
                >{{ accountsError }}</span
            >
        </div>
        <div class="grid min-w-0 gap-4 sm:grid-cols-2">
            <div class="intel-field min-w-0">
                <label for="bluesky-report-interval" class="intel-label">{{
                    t('blueskyReports.interval')
                }}</label>
                <select
                    id="bluesky-report-interval"
                    v-model="form.interval"
                    class="intel-select"
                >
                    <option
                        v-for="interval in REPORT_INTERVALS"
                        :key="interval"
                        :value="interval"
                    >
                        {{ t(`blueskyReports.intervals.${interval}`) }}
                    </option>
                </select>
            </div>
            <div class="intel-field min-w-0">
                <label for="bluesky-report-send-time" class="intel-label">{{
                    t('blueskyReports.sendTime')
                }}</label>
                <input
                    id="bluesky-report-send-time"
                    v-model="form.sendTime"
                    type="time"
                    class="intel-input"
                    required
                />
            </div>
        </div>
        <p class="text-xs text-muted-foreground">
            {{ t('blueskyReports.periodHelp') }}
        </p>
        <p class="text-xs text-muted-foreground">
            {{ t('blueskyReports.dataHelp') }}
        </p>
        <p class="text-xs text-muted-foreground">
            {{ t('blueskyReports.quotaHelp') }}
        </p>
        <ReportTimezoneField
            v-model="form.timezone"
            id="bluesky-report-timezone"
            label-key="blueskyReports.timezone"
            :disabled="busy"
        />
        <div class="space-y-2 rounded-lg border border-border/70 p-3">
            <label class="flex min-h-9 items-center gap-2 text-sm">
                <input
                    v-model="form.sendToBot"
                    type="checkbox"
                    class="size-4 shrink-0 accent-primary"
                />
                {{ t('blueskyReports.sendToBot') }}
            </label>
            <p class="text-xs text-muted-foreground">
                {{ t('blueskyReports.websiteHelp') }}
            </p>
            <div v-if="!botLinked || !botExportsEnabled" class="space-y-1">
                <p class="text-xs text-muted-foreground">
                    {{
                        t(
                            botLinked
                                ? 'blueskyReports.botExportsHelp'
                                : 'blueskyReports.botHelp'
                        )
                    }}
                </p>
                <Link
                    :href="settings()"
                    class="inline-flex min-h-9 items-center text-xs text-primary underline"
                    >{{ t('blueskyReports.botSettings') }}</Link
                >
            </div>
        </div>
        <div class="flex justify-end border-t border-border/60 pt-4">
            <button class="intel-button-primary" :disabled="busy || !canCreate">
                {{ t('blueskyReports.create') }}
            </button>
        </div>
    </form>
</template>
