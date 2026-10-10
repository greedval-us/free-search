import { computed, ref } from 'vue';
import ReportRoutes from '@/actions/App/Http/Controllers/Mastodon/MastodonAnalyticsReportsController';
import { useI18n } from '@/composables/useI18n';
import {
    initialScheduleFields,
    reportInputLines,
    validScheduleTiming,
} from '@/features/scheduled-reports/constants';
import { useScheduledReports } from '@/features/scheduled-reports/useScheduledReports';
import { isReportAccount, normalizeReportAccount } from './account-input';
import type { ReportSchedule, ReportScheduleForm, ReportsList } from './types';

export const useMastodonReports = () => {
    const { t } = useI18n();
    const form = ref<ReportScheduleForm>({
        ...initialScheduleFields(),
        accounts: '',
    });
    const reports = useScheduledReports<ReportsList, ReportSchedule>({
        namespace: 'mastodonReports',
        routes: ReportRoutes,
        onLoaded: (result, firstLoad) => {
            if (firstLoad) {
                form.value.timezone = result.timezone;
            }
        },
    });
    const { list } = reports;
    const accounts = computed(() =>
        reportInputLines(form.value.accounts).map(normalizeReportAccount)
    );
    const accountsError = computed(() => {
        if (accounts.value.some((value) => /\s/u.test(value))) {
            return t('mastodonReports.accountsOnePerLine');
        }

        if (new Set(accounts.value).size !== accounts.value.length) {
            return t('mastodonReports.duplicateAccounts');
        }

        if (accounts.value.some((account) => !isReportAccount(account))) {
            return t('mastodonReports.invalidAccounts');
        }

        if (list.value && accounts.value.length > list.value.maxAccounts) {
            return t('mastodonReports.tooManyAccounts', {
                max: list.value.maxAccounts,
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
            accounts.value.length > 0 &&
            !accountsError.value
    );

    const create = () =>
        reports.create(
            canCreate.value,
            {
                ...form.value,
                name: form.value.name.trim(),
                timezone: form.value.timezone.trim(),
                accounts: accounts.value,
            },
            () => {
                form.value.name = '';
                form.value.accounts = '';
            }
        );
    reports.startPolling();

    return { ...reports, form, accountsError, canCreate, create };
};
