import { computed, ref } from 'vue';
import {
    getRepeatQueryParams,
    isRepeatAutorunEnabled,
    readRepeatQueryInt,
    readRepeatQueryParam,
} from '@/composables/useRepeatQuery';
import { apiRequest } from '@/lib/api';
import type {
    BlueskyActor,
    BlueskyAnalyticsPayload,
    BlueskyHashtagProfile,
} from '../types';

type TranslateFn = (key: string) => string;

type AnalyticsMode = 'account' | 'hashtag';

const LIMIT_MIN = 1;
const LIMIT_MAX = 20;
const DEFAULT_LIMIT = 10;
const DEFAULT_PAGES = 3;
const PAGES_MIN = 1;
const PAGES_MAX = 5;

export const useBlueskyAnalytics = (
    t: TranslateFn,
    locale: { value: string }
) => {
    const form = ref({
        mode: 'account' as AnalyticsMode,
        target: '',
        limit: DEFAULT_LIMIT,
        pages: DEFAULT_PAGES,
        dateFrom: '',
        dateTo: '',
        resolve: true,
    });

    const loading = ref(false);
    const error = ref<string | null>(null);
    const result = ref<BlueskyAnalyticsPayload | null>(null);
    const panelCollapsed = ref(false);

    const canRun = computed(() => form.value.target.trim().length > 0);
    const canUseReportActions = computed(
        () => result.value !== null && canRun.value
    );
    const isAccountMode = computed(() => form.value.mode === 'account');
    const accountProfile = computed(() =>
        result.value?.meta.mode === 'account' && result.value.profile
            ? (result.value.profile as BlueskyActor)
            : null
    );
    const hashtagProfile = computed(() =>
        result.value?.meta.mode === 'hashtag' && result.value.profile
            ? (result.value.profile as BlueskyHashtagProfile)
            : null
    );

    const clampNumber = (
        value: number,
        min: number,
        max: number,
        fallback: number
    ) => {
        if (!Number.isFinite(value)) {
            return fallback;
        }

        return Math.min(max, Math.max(min, Math.trunc(value)));
    };

    const clampLimit = () => {
        form.value.limit = clampNumber(
            form.value.limit,
            LIMIT_MIN,
            LIMIT_MAX,
            DEFAULT_LIMIT
        );
    };

    const clampPages = () => {
        form.value.pages = clampNumber(
            form.value.pages,
            PAGES_MIN,
            PAGES_MAX,
            DEFAULT_PAGES
        );
    };

    const readRepeatBoolean = (value: string): boolean | null => {
        if (value === '1' || value === 'true') {
            return true;
        }

        if (value === '0' || value === 'false') {
            return false;
        }

        return null;
    };

    const runAnalytics = async () => {
        if (!canRun.value) {
            return;
        }

        loading.value = true;
        error.value = null;

        const response = await apiRequest<BlueskyAnalyticsPayload>(
            '/bluesky/analytics/summary',
            {
                query: {
                    mode: form.value.mode,
                    target: form.value.target.trim(),
                    limit: form.value.limit,
                    pages: form.value.pages,
                    dateFrom: form.value.dateFrom || undefined,
                    dateTo: form.value.dateTo || undefined,
                    resolve: form.value.resolve ? 'true' : 'false',
                    locale: locale.value,
                },
            }
        );

        loading.value = false;

        if (!response.ok) {
            error.value =
                response.message ?? t('bluesky.analytics.errors.requestFailed');

            return;
        }

        result.value = response.data;
    };

    const reportUrl = computed(() => {
        const query = new URLSearchParams({
            mode: form.value.mode,
            target: form.value.target.trim(),
            limit: String(form.value.limit),
            pages: String(form.value.pages),
            resolve: form.value.resolve ? '1' : '0',
            locale: locale.value,
        });

        if (form.value.dateFrom) {
            query.set('dateFrom', form.value.dateFrom);
        }

        if (form.value.dateTo) {
            query.set('dateTo', form.value.dateTo);
        }

        return `/bluesky/analytics/report?${query.toString()}`;
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
        const target = readRepeatQueryParam(params, ['target']);
        const limit = readRepeatQueryInt(params, 'limit');
        const pages = readRepeatQueryInt(params, 'pages');
        const dateFrom = readRepeatQueryParam(params, ['dateFrom']);
        const dateTo = readRepeatQueryParam(params, ['dateTo']);
        const resolve = readRepeatQueryParam(params, ['resolve']);
        const resolveValue = readRepeatBoolean(resolve);

        if (mode === 'account' || mode === 'hashtag') {
            form.value.mode = mode;
        }

        if (target !== '') {
            form.value.target = target;
        }

        if (limit !== null) {
            form.value.limit = limit;
            clampLimit();
        }

        if (pages !== null) {
            form.value.pages = pages;
            clampPages();
        }

        if (dateFrom !== '') {
            form.value.dateFrom = dateFrom;
        }

        if (dateTo !== '') {
            form.value.dateTo = dateTo;
        }

        if (resolveValue !== null) {
            form.value.resolve = resolveValue;
        }

        if (isRepeatAutorunEnabled(params) && canRun.value) {
            void runAnalytics();
        }
    };

    return {
        limitMax: LIMIT_MAX,
        form,
        loading,
        error,
        result,
        panelCollapsed,
        canRun,
        canUseReportActions,
        isAccountMode,
        accountProfile,
        hashtagProfile,
        clampLimit,
        clampPages,
        runAnalytics,
        openReport,
        downloadReport,
        initializeFromRepeatQuery,
    };
};
