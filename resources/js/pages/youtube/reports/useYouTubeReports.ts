import { computed, ref } from 'vue';
import ReportRoutes from '@/actions/App/Http/Controllers/YouTube/YouTubeAnalyticsReportsController';
import { useI18n } from '@/composables/useI18n';
import {
    initialScheduleFields,
    reportInputLines,
    validScheduleTiming,
} from '@/features/scheduled-reports/constants';
import { useScheduledReports } from '@/features/scheduled-reports/useScheduledReports';
import {
    CHANNEL_HANDLE_PATTERN,
    CHANNEL_ID_PATTERN,
    normalizeReportChannel,
} from './channel-input';
import type { ReportSchedule, ReportScheduleForm, ReportsList } from './types';

export const useYouTubeReports = () => {
    const { t } = useI18n();
    const form = ref<ReportScheduleForm>({
        ...initialScheduleFields(),
        channels: '',
    });
    const reports = useScheduledReports<ReportsList, ReportSchedule>({
        namespace: 'youtubeReports',
        routes: ReportRoutes,
        onLoaded: (result, firstLoad) => {
            if (firstLoad) {
                form.value.timezone = result.timezone;
            }
        },
    });
    const { list } = reports;
    const channels = computed(() =>
        reportInputLines(form.value.channels).map(normalizeReportChannel)
    );
    const channelsError = computed(() => {
        if (channels.value.some((value) => /\s/u.test(value))) {
            return t('youtubeReports.channelsOnePerLine');
        }

        if (new Set(channels.value).size !== channels.value.length) {
            return t('youtubeReports.duplicateChannels');
        }

        if (
            channels.value.some(
                (channel) =>
                    !CHANNEL_ID_PATTERN.test(channel) &&
                    !CHANNEL_HANDLE_PATTERN.test(channel)
            )
        ) {
            return t('youtubeReports.invalidChannels');
        }

        if (list.value && channels.value.length > list.value.maxChannels) {
            return t('youtubeReports.tooManyChannels', {
                max: list.value.maxChannels,
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
            channels.value.length > 0 &&
            !channelsError.value
    );

    const create = () =>
        reports.create(
            canCreate.value,
            {
                ...form.value,
                name: form.value.name.trim(),
                timezone: form.value.timezone.trim(),
                channels: channels.value,
            },
            () => {
                form.value.name = '';
                form.value.channels = '';
            }
        );
    reports.startPolling();

    return { ...reports, form, channelsError, canCreate, create };
};
