import { computed, ref } from 'vue';
import {
    getRepeatQueryParams,
    isRepeatAutorunEnabled,
    readRepeatQueryInt,
    readRepeatQueryParam,
} from '@/composables/useRepeatQuery';
import { apiRequest } from '@/lib/api';
import type { YouTubeAnalyticsPayload, YouTubeVideo } from '../types';

type TranslateFn = (key: string) => string;

type AnalyticsMode = 'video' | 'channel';
type PeriodDays = 1 | 3 | 7;

const DAY_IN_MS = 24 * 60 * 60 * 1000;

const formatDateInput = (date: Date) => {
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');

    return `${yyyy}-${mm}-${dd}`;
};

const parseDateInput = (value: string): Date | null => {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return null;
    }

    const date = new Date(`${value}T00:00:00`);

    return Number.isNaN(date.getTime()) ? null : date;
};

const diffDaysInclusive = (from: string, to: string): number | null => {
    const fromDate = parseDateInput(from);
    const toDate = parseDateInput(to);

    if (!fromDate || !toDate) {
        return null;
    }

    return Math.floor((toDate.getTime() - fromDate.getTime()) / DAY_IN_MS) + 1;
};

export const useYouTubeAnalytics = (
    t: TranslateFn,
    locale: { value: string }
) => {
    const PERIODS: readonly PeriodDays[] = [1, 3, 7];

    const form = ref({
        mode: 'channel' as AnalyticsMode,
        videoId: '',
        channelId: '',
        periodDays: 7 as PeriodDays,
        dateFrom: '',
        dateTo: '',
    });

    const loading = ref(false);
    const error = ref<string | null>(null);
    const result = ref<YouTubeAnalyticsPayload | null>(null);
    const panelCollapsed = ref(false);

    const canRun = computed(() =>
        form.value.mode === 'video'
            ? form.value.videoId.trim().length > 0
            : form.value.channelId.trim().length > 0
    );
    const canUseReportActions = computed(
        () => result.value !== null && canRun.value
    );

    const customPeriodTooLong = computed(() => {
        if (
            form.value.mode !== 'channel' ||
            form.value.dateFrom === '' ||
            form.value.dateTo === ''
        ) {
            return false;
        }

        const days = diffDaysInclusive(form.value.dateFrom, form.value.dateTo);

        return days !== null && days > 7;
    });

    const dateLimits = computed(() => {
        if (form.value.mode !== 'channel') {
            return {
                fromMin: null as string | null,
                fromMax: null as string | null,
                toMin: null as string | null,
                toMax: null as string | null,
            };
        }

        const today = new Date();
        const todayStr = formatDateInput(today);
        const fromDate = parseDateInput(form.value.dateFrom);
        const toDate = parseDateInput(form.value.dateTo);

        let fromMin: string | null = null;
        let fromMax: string | null = todayStr;
        let toMin: string | null = null;
        let toMax: string | null = todayStr;

        if (toDate) {
            const minFrom = new Date(toDate.getTime() - 6 * DAY_IN_MS);
            fromMin = formatDateInput(minFrom);
            fromMax = formatDateInput(toDate);
        }

        if (fromDate) {
            const maxTo = new Date(fromDate.getTime() + 6 * DAY_IN_MS);
            toMin = formatDateInput(fromDate);
            toMax = formatDateInput(maxTo > today ? today : maxTo);
        }

        return { fromMin, fromMax, toMin, toMax };
    });

    const applyPreset = (days: PeriodDays) => {
        form.value.periodDays = days;
        form.value.dateFrom = '';
        form.value.dateTo = '';
    };

    const runAnalytics = async () => {
        if (customPeriodTooLong.value) {
            error.value = t('youtube.analytics.customPeriodTooLong');

            return;
        }

        loading.value = true;
        error.value = null;

        const response = await apiRequest<YouTubeAnalyticsPayload>(
            '/youtube/analytics/summary',
            {
                query: {
                    mode: form.value.mode,
                    videoId:
                        form.value.mode === 'video' ? form.value.videoId : '',
                    channelId:
                        form.value.mode === 'channel'
                            ? form.value.channelId
                            : '',
                    periodDays:
                        form.value.mode === 'channel'
                            ? form.value.periodDays
                            : undefined,
                    dateFrom:
                        form.value.mode === 'channel'
                            ? form.value.dateFrom
                            : '',
                    dateTo:
                        form.value.mode === 'channel' ? form.value.dateTo : '',
                },
            }
        );

        loading.value = false;

        if (!response.ok) {
            error.value = response.message ?? t('youtube.common.requestFailed');

            return;
        }

        result.value = response.data;
    };

    const reportUrl = computed(() => {
        const query = new URLSearchParams({
            mode: form.value.mode,
            locale: locale.value,
        });

        if (form.value.mode === 'video') {
            query.set('videoId', form.value.videoId.trim());
        } else {
            query.set('channelId', form.value.channelId.trim());
            query.set('periodDays', String(form.value.periodDays));

            if (form.value.dateFrom && form.value.dateTo) {
                query.set('dateFrom', form.value.dateFrom);
                query.set('dateTo', form.value.dateTo);
            }
        }

        return `/youtube/analytics/report?${query.toString()}`;
    });

    const openReport = () => {
        window.open(reportUrl.value, '_blank', 'noopener,noreferrer');
    };

    const downloadReport = () => {
        window.open(
            `${reportUrl.value}&download=1`,
            '_blank',
            'noopener,noreferrer'
        );
    };

    const numberFormat = new Intl.NumberFormat();
    const fmt = (value: number) => numberFormat.format(value ?? 0);
    const pct = (value: number) => `${Number(value ?? 0).toFixed(2)}%`;
    const formatDate = (value: string) =>
        value ? new Date(value).toLocaleString() : '-';
    const compactText = (value: string, max = 220) =>
        value.length > max ? `${value.slice(0, max).trim()}...` : value;

    const leaderGroups = computed(() => {
        if (!result.value) {
            return [];
        }

        return [
            {
                key: 'views',
                title: t('youtube.analytics.leaders.views'),
                items: result.value.leaders.byViews,
            },
            {
                key: 'likes',
                title: t('youtube.analytics.leaders.likes'),
                items: result.value.leaders.byLikes,
            },
            {
                key: 'comments',
                title: t('youtube.analytics.leaders.comments'),
                items: result.value.leaders.byComments,
            },
            {
                key: 'engagement',
                title: t('youtube.analytics.leaders.engagement'),
                items: result.value.leaders.byEngagement,
            },
        ];
    });

    const videoMetric = (video: YouTubeVideo, key: string) => {
        if (key === 'engagement') {
            return pct(video.engagementRate);
        }

        if (key === 'likes') {
            return fmt(video.likes);
        }

        if (key === 'comments') {
            return fmt(video.comments);
        }

        return fmt(video.views);
    };

    const initializeFromRepeatQuery = () => {
        const params = getRepeatQueryParams();

        if (!params) {
            return;
        }

        const tab = readRepeatQueryParam(params, ['tab']);

        if (tab !== 'analytics') {
            return;
        }

        const mode = readRepeatQueryParam(params, ['mode']);
        const videoId = readRepeatQueryParam(params, ['videoId']);
        const channelId = readRepeatQueryParam(params, ['channelId']);
        const periodDays = readRepeatQueryInt(params, 'periodDays');
        const dateFrom = readRepeatQueryParam(params, ['dateFrom']);
        const dateTo = readRepeatQueryParam(params, ['dateTo']);

        if (mode === 'video' || mode === 'channel') {
            form.value.mode = mode;
        }

        if (videoId !== '') {
            form.value.videoId = videoId;
        }

        if (channelId !== '') {
            form.value.channelId = channelId;
        }

        if (periodDays === 1 || periodDays === 3 || periodDays === 7) {
            form.value.periodDays = periodDays;
        }

        if (dateFrom !== '' && dateTo !== '') {
            form.value.dateFrom = dateFrom;
            form.value.dateTo = dateTo;
        }

        if (isRepeatAutorunEnabled(params) && canRun.value) {
            void runAnalytics();
        }
    };

    const insightLabel = (key: string) => {
        const map: Record<string, string> = {
            focus_video: t('youtube.analytics.insightsLabels.focus_video'),
            top_video: t('youtube.analytics.insightsLabels.top_video'),
            engagement: t('youtube.analytics.insightsLabels.engagement'),
            duration_mix: t('youtube.analytics.insightsLabels.duration_mix'),
        };

        return map[key] ?? key;
    };

    return {
        PERIODS,
        form,
        loading,
        error,
        result,
        panelCollapsed,
        canRun,
        canUseReportActions,
        customPeriodTooLong,
        dateLimits,
        applyPreset,
        runAnalytics,
        openReport,
        downloadReport,
        fmt,
        pct,
        formatDate,
        compactText,
        leaderGroups,
        videoMetric,
        insightLabel,
        initializeFromRepeatQuery,
    };
};
