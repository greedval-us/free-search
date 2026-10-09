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
import NewsFilters from '../components/NewsFilters.vue';
import type { NewsOptions } from '../types';
import type { ReportInterval, ReportScheduleForm } from './types';

defineProps<{
    busy: boolean;
    botLinked: boolean;
    botExportsEnabled: boolean;
    maxQueries: number;
    limitReached: boolean;
    validationError: string;
    canCreate: boolean;
    options: NewsOptions | null;
    optionsError: string;
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
            {{ t('newsMediaReports.limitReached') }}
        </p>
        <div class="intel-field">
            <label for="news-report-schedule-name" class="intel-label">{{
                t('newsMediaReports.name')
            }}</label>
            <input
                id="news-report-schedule-name"
                v-model="form.name"
                class="intel-input"
                maxlength="100"
                required
            />
        </div>
        <div class="intel-field">
            <label for="news-report-schedule-queries" class="intel-label">{{
                t('newsMediaReports.queries', { max: maxQueries })
            }}</label>
            <textarea
                id="news-report-schedule-queries"
                v-model="form.queries"
                class="intel-scroll intel-input min-h-24 resize-y"
                rows="4"
                :placeholder="t('newsMediaReports.queriesPlaceholder')"
                :aria-invalid="Boolean(validationError)"
                aria-describedby="news-report-queries-help news-report-queries-error"
                required
            />
            <span
                id="news-report-queries-help"
                class="text-xs text-muted-foreground"
                >{{ t('newsMediaReports.queriesHelp') }}</span
            >
            <span
                v-if="validationError"
                id="news-report-queries-error"
                role="alert"
                class="text-xs text-destructive"
                >{{ validationError }}</span
            >
        </div>
        <div class="grid min-w-0 gap-4 sm:grid-cols-2">
            <label class="intel-field">
                <span class="intel-label">{{
                    t('newsMediaIntel.analytics.brand')
                }}</span>
                <input
                    v-model="form.brand"
                    maxlength="80"
                    class="intel-input"
                    :placeholder="
                        t('newsMediaIntel.analytics.brandPlaceholder')
                    "
                />
            </label>
            <label class="intel-field">
                <span class="intel-label">{{
                    t('newsMediaIntel.analytics.domain')
                }}</span>
                <input
                    v-model="form.domain"
                    maxlength="253"
                    class="intel-input"
                    placeholder="example.com"
                />
            </label>
        </div>
        <label class="intel-field">
            <span class="intel-label">{{
                t('newsMediaIntel.analytics.competitors')
            }}</span>
            <textarea
                v-model="form.competitors"
                rows="3"
                class="intel-input resize-y"
                :placeholder="
                    t('newsMediaIntel.analytics.competitorsPlaceholder')
                "
            />
            <span class="text-xs text-muted-foreground">{{
                t('newsMediaIntel.analytics.competitorsHelp')
            }}</span>
        </label>
        <NewsFilters
            v-model="form.filters"
            :options="options"
            analytics
            :disabled="busy || !options"
        />
        <p v-if="optionsError" role="alert" class="text-sm text-destructive">
            {{ optionsError }}
        </p>
        <div class="grid min-w-0 gap-4 sm:grid-cols-2">
            <div class="intel-field min-w-0">
                <label for="news-report-interval" class="intel-label">{{
                    t('newsMediaReports.interval')
                }}</label>
                <select
                    id="news-report-interval"
                    v-model="form.interval"
                    class="intel-select"
                >
                    <option
                        v-for="interval in intervals"
                        :key="interval"
                        :value="interval"
                    >
                        {{ t(`newsMediaReports.intervals.${interval}`) }}
                    </option>
                </select>
            </div>
            <div class="intel-field min-w-0">
                <label for="news-report-send-time" class="intel-label">{{
                    t('newsMediaReports.sendTime')
                }}</label>
                <input
                    id="news-report-send-time"
                    v-model="form.sendTime"
                    type="time"
                    class="intel-input"
                    required
                />
            </div>
        </div>
        <p class="text-xs text-muted-foreground">
            {{ t('newsMediaReports.periodHelp') }}
        </p>
        <p class="text-xs text-muted-foreground">
            {{ t('newsMediaReports.dataHelp') }}
        </p>
        <div class="intel-field">
            <label for="news-report-timezone" class="intel-label">{{
                t('newsMediaReports.timezone')
            }}</label>
            <select
                id="news-report-timezone"
                v-model="form.timezone"
                class="intel-select"
                aria-describedby="news-report-timezone-help"
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
                id="news-report-timezone-help"
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
                {{ t('newsMediaReports.sendToBot') }}
            </label>
            <p class="text-xs text-muted-foreground">
                {{ t('newsMediaReports.websiteHelp') }}
            </p>
            <div v-if="!botLinked || !botExportsEnabled" class="space-y-1">
                <p class="text-xs text-muted-foreground">
                    {{
                        t(
                            botLinked
                                ? 'newsMediaReports.botExportsHelp'
                                : 'newsMediaReports.botHelp'
                        )
                    }}
                </p>
                <Link
                    :href="settings()"
                    class="inline-flex min-h-9 items-center text-xs text-primary underline"
                    >{{ t('newsMediaReports.botSettings') }}</Link
                >
            </div>
        </div>
        <div class="flex justify-end border-t border-border/60 pt-4">
            <button class="intel-button-primary" :disabled="busy || !canCreate">
                {{ t('newsMediaReports.create') }}
            </button>
        </div>
    </form>
</template>
