<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import {
    REPORT_INTERVALS,
    REPORT_NAME_MAX_LENGTH,
} from '@/features/scheduled-reports/constants';
import ReportTimezoneField from '@/features/scheduled-reports/ReportTimezoneField.vue';
import { settings } from '@/routes/telegram-bot';
import NewsFilters from '../components/NewsFilters.vue';
import type { NewsOptions } from '../types';
import type { ReportScheduleForm } from './types';

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
const { t } = useI18n();
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
                :maxlength="REPORT_NAME_MAX_LENGTH"
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
                        v-for="interval in REPORT_INTERVALS"
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
        <ReportTimezoneField
            v-model="form.timezone"
            id="news-report-timezone"
            label-key="newsMediaReports.timezone"
            :disabled="busy"
        />
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
