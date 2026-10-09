import { computed, ref } from 'vue';
import ReportRoutes from '@/actions/App/Http/Controllers/Telegram/TelegramAnalyticsReportsController';
import { useI18n } from '@/composables/useI18n';
import {
    initialScheduleFields,
    reportInputLines,
    validScheduleTiming,
} from '@/features/scheduled-reports/constants';
import { useScheduledReports } from '@/features/scheduled-reports/useScheduledReports';

import type { ReportSchedule, ReportScheduleForm, ReportsList } from './types';

export const useTelegramReports = () => {
    const { t } = useI18n();
    const form = ref<ReportScheduleForm>({
        ...initialScheduleFields(),
        groups: '',
    });
    const reports = useScheduledReports<ReportsList, ReportSchedule>({
        namespace: 'telegramReports',
        routes: ReportRoutes,
        onLoaded: (result, firstLoad) => {
            if (firstLoad) {
                form.value.timezone = result.timezone;
            }
        },
    });
    const { list } = reports;
    const groups = computed(() =>
        reportInputLines(form.value.groups).map((value) =>
            value
                .replace(/^(?:https?:\/\/)?t\.me\/(?:s\/)?|^@/i, '')
                .replace(/\/$/, '')
                .toLowerCase()
        )
    );
    const groupsError = computed(() => {
        if (groups.value.some((value) => /\s/u.test(value))) {
            return t('telegramReports.groupsOnePerLine');
        }

        if (new Set(groups.value).size !== groups.value.length) {
            return t('telegramReports.duplicateGroups');
        }

        if (
            groups.value.some(
                (group) =>
                    !/^[a-z][a-z0-9_]{3,31}$/i.test(group) || group === 'self'
            )
        ) {
            return t('telegramReports.invalidGroups');
        }

        if (list.value && groups.value.length > list.value.maxGroups) {
            return t('telegramReports.tooManyGroups', {
                max: list.value.maxGroups,
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
            groups.value.length > 0 &&
            !groupsError.value
    );

    const create = () =>
        reports.create(
            canCreate.value,
            {
                ...form.value,
                name: form.value.name.trim(),
                timezone: form.value.timezone.trim(),
                groups: groups.value,
            },
            () => {
                form.value.name = '';
                form.value.groups = '';
            }
        );
    reports.startPolling();

    return { ...reports, form, groupsError, canCreate, create };
};
