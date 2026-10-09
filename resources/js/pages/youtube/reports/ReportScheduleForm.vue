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
    maxChannels: number;
    limitReached: boolean;
    channelsError: string;
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
                maxlength="100"
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
                        v-for="interval in intervals"
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
        <div class="intel-field">
            <label for="youtube-report-timezone" class="intel-label">{{
                t('youtubeReports.timezone')
            }}</label>
            <select
                id="youtube-report-timezone"
                v-model="form.timezone"
                class="intel-select"
                aria-describedby="youtube-report-timezone-help"
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
                id="youtube-report-timezone-help"
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
