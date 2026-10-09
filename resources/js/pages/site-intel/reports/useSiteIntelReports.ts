import { computed, ref } from 'vue';
import ReportRoutes from '@/actions/App/Http/Controllers/SiteIntel/SiteIntelReportsController';
import { useI18n } from '@/composables/useI18n';
import {
    initialScheduleFields,
    reportInputLines,
    validScheduleTiming,
} from '@/features/scheduled-reports/constants';
import { useScheduledReports } from '@/features/scheduled-reports/useScheduledReports';
import { normalizeReportTarget } from './target-input';
import type { ReportSchedule, ReportScheduleForm, ReportsList } from './types';

export const useSiteIntelReports = () => {
    const { t } = useI18n();
    const form = ref<ReportScheduleForm>({
        ...initialScheduleFields(),
        targets: '',
        reportType: 'analytics',
        crawlLimit: 8,
        platformType: 'auto',
    });
    const reports = useScheduledReports<ReportsList, ReportSchedule>({
        namespace: 'siteIntelReports',
        routes: ReportRoutes,
        onLoaded: (result, firstLoad) => {
            if (firstLoad) {
                form.value.timezone = result.timezone;
            }

            if (!result.availableReportTypes.includes(form.value.reportType)) {
                form.value.reportType =
                    result.availableReportTypes[0] ?? 'analytics';
            }
        },
    });
    const { list } = reports;
    const targets = computed(() =>
        reportInputLines(form.value.targets).map(
            (value) => normalizeReportTarget(value) ?? value
        )
    );
    const targetsError = computed(() => {
        if (targets.value.some((value) => /\s/u.test(value))) {
            return t('siteIntelReports.targetsOnePerLine');
        }

        if (new Set(targets.value).size !== targets.value.length) {
            return t('siteIntelReports.duplicateTargets');
        }

        if (targets.value.some((target) => !normalizeReportTarget(target))) {
            return t('siteIntelReports.invalidTargets');
        }

        if (list.value && targets.value.length > list.value.maxTargets) {
            return t('siteIntelReports.tooManyTargets', {
                max: list.value.maxTargets,
            });
        }

        return '';
    });
    const canCreate = computed(
        () =>
            Boolean(
                list.value &&
                list.value.schedules.length < list.value.maxSchedules
            ) &&
            validScheduleTiming(form.value) &&
            targets.value.length > 0 &&
            !targetsError.value &&
            Boolean(
                list.value?.availableReportTypes.includes(form.value.reportType)
            ) &&
            (form.value.reportType !== 'seo-audit' ||
                (Number.isInteger(form.value.crawlLimit) &&
                    form.value.crawlLimit >= 3 &&
                    form.value.crawlLimit <= 20 &&
                    [
                        'auto',
                        'generic',
                        'media-platform',
                        'content-site',
                        'storefront',
                    ].includes(form.value.platformType)))
    );

    const create = () =>
        reports.create(
            canCreate.value,
            {
                ...form.value,
                name: form.value.name.trim(),
                timezone: form.value.timezone.trim(),
                targets: targets.value,
                crawlLimit:
                    form.value.reportType === 'seo-audit'
                        ? form.value.crawlLimit
                        : 8,
                platformType:
                    form.value.reportType === 'seo-audit'
                        ? form.value.platformType
                        : 'auto',
            },
            () => {
                form.value.name = '';
                form.value.targets = '';
            }
        );
    reports.startPolling();

    return { ...reports, form, targetsError, canCreate, create };
};
