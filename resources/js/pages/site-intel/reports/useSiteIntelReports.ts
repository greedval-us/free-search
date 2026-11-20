import { computed, ref } from 'vue';
import ReportRoutes from '@/actions/App/Http/Controllers/SiteIntel/SiteIntelReportsController';
import { useI18n } from '@/composables/useI18n';
import {
    initialScheduleFields,
    reportInputLines,
    validScheduleTiming,
} from '@/features/scheduled-reports/constants';
import { useScheduledReports } from '@/features/scheduled-reports/useScheduledReports';
import {
    DEFAULT_SEO_AUDIT_OPTIONS,
    SCHEDULED_CRAWL_LIMIT,
    SEO_AUDIT_PLATFORMS,
} from '../lib/seo-audit-options';
import { normalizeReportTarget } from './target-input';
import type { ReportSchedule, ReportScheduleForm, ReportsList } from './types';

export const useSiteIntelReports = () => {
    const { t } = useI18n();
    const form = ref<ReportScheduleForm>({
        ...initialScheduleFields(),
        targets: '',
        reportType: 'analytics',
        ...DEFAULT_SEO_AUDIT_OPTIONS,
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
                    form.value.crawlLimit >= SCHEDULED_CRAWL_LIMIT.min &&
                    form.value.crawlLimit <= SCHEDULED_CRAWL_LIMIT.max &&
                    SEO_AUDIT_PLATFORMS.some(
                        ({ value }) => value === form.value.platformType
                    )))
    );

    const create = () =>
        reports.create(
            canCreate.value,
            {
                ...form.value,
                name: form.value.name.trim(),
                timezone: form.value.timezone.trim(),
                targets: targets.value,
                ...(form.value.reportType === 'seo-audit'
                    ? {}
                    : DEFAULT_SEO_AUDIT_OPTIONS),
            },
            () => {
                form.value.name = '';
                form.value.targets = '';
            }
        );
    reports.startPolling();

    return { ...reports, form, targetsError, canCreate, create };
};
