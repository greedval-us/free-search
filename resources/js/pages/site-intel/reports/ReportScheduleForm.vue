<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import {
    REPORT_INTERVALS,
    REPORT_NAME_MAX_LENGTH,
} from '@/features/scheduled-reports/constants';
import ReportTimezoneField from '@/features/scheduled-reports/ReportTimezoneField.vue';
import { settings } from '@/routes/telegram-bot';
import type { ReportScheduleForm, SiteReportType } from './types';

defineProps<{
    busy: boolean;
    botLinked: boolean;
    botExportsEnabled: boolean;
    maxTargets: number;
    limitReached: boolean;
    targetsError: string;
    canCreate: boolean;
    availableReportTypes: SiteReportType[];
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
            {{ t('siteIntelReports.limitReached') }}
        </p>
        <div class="intel-field">
            <label for="site-intel-report-schedule-name" class="intel-label">{{
                t('siteIntelReports.name')
            }}</label>
            <input
                id="site-intel-report-schedule-name"
                v-model="form.name"
                class="intel-input"
                :maxlength="REPORT_NAME_MAX_LENGTH"
                required
            />
        </div>
        <div class="intel-field">
            <label for="site-intel-report-type" class="intel-label">{{
                t('siteIntelReports.reportType')
            }}</label>
            <select
                id="site-intel-report-type"
                v-model="form.reportType"
                class="intel-select"
                required
            >
                <option
                    v-for="reportType in availableReportTypes"
                    :key="reportType"
                    :value="reportType"
                >
                    {{ t(`siteIntelReports.types.${reportType}`) }}
                </option>
            </select>
        </div>
        <div
            v-if="form.reportType === 'seo-audit'"
            class="grid min-w-0 gap-4 sm:grid-cols-2"
        >
            <div class="intel-field min-w-0">
                <label
                    for="site-intel-report-crawl-limit"
                    class="intel-label"
                    >{{ t('siteIntel.seoAudit.crawlLimit') }}</label
                >
                <input
                    id="site-intel-report-crawl-limit"
                    v-model.number="form.crawlLimit"
                    type="number"
                    min="3"
                    max="20"
                    step="1"
                    class="intel-input"
                    required
                />
            </div>
            <div class="intel-field min-w-0">
                <label
                    for="site-intel-report-platform-type"
                    class="intel-label"
                    >{{ t('siteIntel.seoAudit.platformType') }}</label
                >
                <select
                    id="site-intel-report-platform-type"
                    v-model="form.platformType"
                    class="intel-select"
                >
                    <option value="auto">
                        {{ t('siteIntel.seoAudit.platformAuto') }}
                    </option>
                    <option
                        v-for="platform in [
                            'generic',
                            'media-platform',
                            'content-site',
                            'storefront',
                        ]"
                        :key="platform"
                        :value="platform"
                    >
                        {{ t(`siteIntel.seoAudit.profile.${platform}`) }}
                    </option>
                </select>
            </div>
        </div>
        <div class="intel-field">
            <label
                for="site-intel-report-schedule-targets"
                class="intel-label"
                >{{ t('siteIntelReports.targets', { max: maxTargets }) }}</label
            >
            <textarea
                id="site-intel-report-schedule-targets"
                v-model="form.targets"
                class="intel-scroll intel-input min-h-24 resize-y"
                rows="4"
                :placeholder="t('siteIntelReports.targetsPlaceholder')"
                :aria-invalid="Boolean(targetsError)"
                aria-describedby="site-intel-report-targets-help site-intel-report-targets-error"
                required
            />
            <span
                id="site-intel-report-targets-help"
                class="text-xs text-muted-foreground"
                >{{ t('siteIntelReports.targetsHelp') }}</span
            >
            <span
                v-if="targetsError"
                id="site-intel-report-targets-error"
                role="alert"
                class="text-xs text-destructive"
                >{{ targetsError }}</span
            >
        </div>
        <div class="grid min-w-0 gap-4 sm:grid-cols-2">
            <div class="intel-field min-w-0">
                <label for="site-intel-report-interval" class="intel-label">{{
                    t('siteIntelReports.interval')
                }}</label>
                <select
                    id="site-intel-report-interval"
                    v-model="form.interval"
                    class="intel-select"
                >
                    <option
                        v-for="interval in REPORT_INTERVALS"
                        :key="interval"
                        :value="interval"
                    >
                        {{ t(`siteIntelReports.intervals.${interval}`) }}
                    </option>
                </select>
            </div>
            <div class="intel-field min-w-0">
                <label for="site-intel-report-send-time" class="intel-label">{{
                    t('siteIntelReports.sendTime')
                }}</label>
                <input
                    id="site-intel-report-send-time"
                    v-model="form.sendTime"
                    type="time"
                    class="intel-input"
                    required
                />
            </div>
        </div>
        <p class="text-xs text-muted-foreground">
            {{ t('siteIntelReports.periodHelp') }}
        </p>
        <p class="text-xs text-muted-foreground">
            {{ t('siteIntelReports.dataHelp') }}
        </p>
        <p class="text-xs text-muted-foreground">
            {{ t('siteIntelReports.quotaHelp') }}
        </p>
        <ReportTimezoneField
            v-model="form.timezone"
            id="site-intel-report-timezone"
            label-key="siteIntelReports.timezone"
            :disabled="busy"
        />
        <div class="space-y-2 rounded-lg border border-border/70 p-3">
            <label class="flex min-h-9 items-center gap-2 text-sm">
                <input
                    v-model="form.sendToBot"
                    type="checkbox"
                    class="size-4 shrink-0 accent-primary"
                />
                {{ t('siteIntelReports.sendToBot') }}
            </label>
            <p class="text-xs text-muted-foreground">
                {{ t('siteIntelReports.websiteHelp') }}
            </p>
            <div v-if="!botLinked || !botExportsEnabled" class="space-y-1">
                <p class="text-xs text-muted-foreground">
                    {{
                        t(
                            botLinked
                                ? 'siteIntelReports.botExportsHelp'
                                : 'siteIntelReports.botHelp'
                        )
                    }}
                </p>
                <Link
                    :href="settings()"
                    class="inline-flex min-h-9 items-center text-xs text-primary underline"
                    >{{ t('siteIntelReports.botSettings') }}</Link
                >
            </div>
        </div>
        <div class="flex justify-end border-t border-border/60 pt-4">
            <button class="intel-button-primary" :disabled="busy || !canCreate">
                {{ t('siteIntelReports.create') }}
            </button>
        </div>
    </form>
</template>
