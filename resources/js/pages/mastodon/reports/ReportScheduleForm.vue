<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, shallowRef } from 'vue';
import { useI18n } from '@/composables/useI18n';
import {
    getDeviceTimezone,
    REPORT_TIMEZONES,
    timezoneLabel,
} from '@/lib/report-timezones';
import { settings } from '@/routes/telegram-bot';
import type { ReportInterval, ReportScheduleForm } from './types';

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
const { t, locale } = useI18n();
const intervals: readonly ReportInterval[] = ['1', '3', '7', 'month'];
const deviceTimezone = shallowRef<string | null>(null);
const timezoneDate = new Date();
const timezoneGroups = computed(() =>
    (['russia', 'world'] as const).map((group) => ({
        group,
        options: REPORT_TIMEZONES.filter((item) => item.group === group).map(
            (item) => ({
                value: item.value,
                label: timezoneLabel(item.value, locale.value, t, timezoneDate),
            })
        ),
    }))
);
const additionalTimezone = computed(() =>
    form.value.timezone &&
    !REPORT_TIMEZONES.some((item) => item.value === form.value.timezone)
        ? {
              value: form.value.timezone,
              label: timezoneLabel(
                  form.value.timezone,
                  locale.value,
                  t,
                  timezoneDate
              ),
          }
        : null
);
onMounted(() => {
    deviceTimezone.value = getDeviceTimezone();
});
const useDeviceTimezone = () => {
    if (deviceTimezone.value) {
        form.value.timezone = deviceTimezone.value;
    }
};
</script>

<template>
    <form class="min-w-0 space-y-4" @submit.prevent="$emit('create')">
        <p
            v-if="limitReached"
            role="status"
            class="rounded-lg border border-border p-3 text-sm text-muted-foreground"
        >
            {{ t('mastodonReports.limitReached') }}
        </p>
        <div class="intel-field">
            <label for="mastodon-report-schedule-name" class="intel-label">{{
                t('mastodonReports.name')
            }}</label>
            <input
                id="mastodon-report-schedule-name"
                v-model="form.name"
                class="intel-input"
                maxlength="100"
                required
            />
        </div>
        <div class="intel-field">
            <label
                for="mastodon-report-schedule-accounts"
                class="intel-label"
                >{{
                    t('mastodonReports.accounts', { max: maxAccounts })
                }}</label
            >
            <textarea
                id="mastodon-report-schedule-accounts"
                v-model="form.accounts"
                class="intel-scroll intel-input min-h-24 resize-y"
                rows="4"
                :placeholder="t('mastodonReports.accountsPlaceholder')"
                :aria-invalid="Boolean(accountsError)"
                aria-describedby="mastodon-report-accounts-help mastodon-report-accounts-error"
                required
            />
            <span
                id="mastodon-report-accounts-help"
                class="text-xs text-muted-foreground"
                >{{ t('mastodonReports.accountsHelp') }}</span
            >
            <span
                v-if="accountsError"
                id="mastodon-report-accounts-error"
                role="alert"
                class="text-xs text-destructive"
                >{{ accountsError }}</span
            >
        </div>
        <div class="grid min-w-0 gap-4 sm:grid-cols-2">
            <div class="intel-field min-w-0">
                <label for="mastodon-report-interval" class="intel-label">{{
                    t('mastodonReports.interval')
                }}</label>
                <select
                    id="mastodon-report-interval"
                    v-model="form.interval"
                    class="intel-select"
                >
                    <option
                        v-for="interval in intervals"
                        :key="interval"
                        :value="interval"
                    >
                        {{ t(`mastodonReports.intervals.${interval}`) }}
                    </option>
                </select>
            </div>
            <div class="intel-field min-w-0">
                <label for="mastodon-report-send-time" class="intel-label">{{
                    t('mastodonReports.sendTime')
                }}</label>
                <input
                    id="mastodon-report-send-time"
                    v-model="form.sendTime"
                    type="time"
                    class="intel-input"
                    required
                />
            </div>
        </div>
        <p class="text-xs text-muted-foreground">
            {{ t('mastodonReports.periodHelp') }}
        </p>
        <p class="text-xs text-muted-foreground">
            {{ t('mastodonReports.dataHelp') }}
        </p>
        <p class="text-xs text-muted-foreground">
            {{ t('mastodonReports.quotaHelp') }}
        </p>
        <div class="intel-field">
            <label for="mastodon-report-timezone" class="intel-label">{{
                t('mastodonReports.timezone')
            }}</label>
            <select
                id="mastodon-report-timezone"
                v-model="form.timezone"
                class="intel-select"
                aria-describedby="mastodon-report-timezone-help"
                required
            >
                <option
                    v-if="additionalTimezone"
                    :value="additionalTimezone.value"
                >
                    {{ additionalTimezone.label }}
                </option>
                <optgroup
                    v-for="group in timezoneGroups"
                    :key="group.group"
                    :label="t(`reportTimezones.groups.${group.group}`)"
                >
                    <option
                        v-for="option in group.options"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </optgroup>
            </select>
            <p
                id="mastodon-report-timezone-help"
                class="text-xs text-muted-foreground"
            >
                {{ t('reportTimezones.help') }}
            </p>
            <button
                v-if="deviceTimezone"
                type="button"
                class="intel-button-secondary self-start"
                :disabled="busy"
                @click="useDeviceTimezone"
            >
                {{ t('reportTimezones.useDevice') }}
            </button>
        </div>
        <div class="space-y-2 rounded-lg border border-border/70 p-3">
            <label class="flex min-h-9 items-center gap-2 text-sm">
                <input
                    v-model="form.sendToBot"
                    type="checkbox"
                    class="size-4 shrink-0 accent-primary"
                />
                {{ t('mastodonReports.sendToBot') }}
            </label>
            <p class="text-xs text-muted-foreground">
                {{ t('mastodonReports.websiteHelp') }}
            </p>
            <div v-if="!botLinked || !botExportsEnabled" class="space-y-1">
                <p class="text-xs text-muted-foreground">
                    {{
                        t(
                            botLinked
                                ? 'mastodonReports.botExportsHelp'
                                : 'mastodonReports.botHelp'
                        )
                    }}
                </p>
                <Link
                    :href="settings()"
                    class="inline-flex min-h-9 items-center text-xs text-primary underline"
                    >{{ t('mastodonReports.botSettings') }}</Link
                >
            </div>
        </div>
        <div class="flex justify-end border-t border-border/60 pt-4">
            <button class="intel-button-primary" :disabled="busy || !canCreate">
                {{ t('mastodonReports.create') }}
            </button>
        </div>
    </form>
</template>
