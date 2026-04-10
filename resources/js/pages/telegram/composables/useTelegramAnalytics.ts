import { computed, reactive, ref } from 'vue';
import type { TelegramAnalyticsSummary } from '../types';

type TranslateFn = (key: string) => string;
type AnalyticsPeriod = 1 | 3 | 7;
type ScorePriority = 'balanced' | 'reach' | 'discussion' | 'virality';

const PERIODS: readonly AnalyticsPeriod[] = [1, 3, 7];
const PRIORITIES: readonly ScorePriority[] = ['balanced', 'reach', 'discussion', 'virality'];
const DAY_IN_MS = 24 * 60 * 60 * 1000;

const parseDate = (value: string): Date | null => {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
    if (!match) {
        return null;
    }

    const year = Number(match[1]);
    const month = Number(match[2]);
    const day = Number(match[3]);

    return new Date(Date.UTC(year, month - 1, day));
};

const formatDate = (value: Date): string => {
    const year = value.getUTCFullYear();
    const month = String(value.getUTCMonth() + 1).padStart(2, '0');
    const day = String(value.getUTCDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const shiftDate = (value: string, days: number): string | null => {
    const parsed = parseDate(value);
    if (!parsed) {
        return null;
    }

    return formatDate(new Date(parsed.getTime() + days * DAY_IN_MS));
};

const diffDays = (from: string, to: string): number | null => {
    const fromDate = parseDate(from);
    const toDate = parseDate(to);

    if (!fromDate || !toDate) {
        return null;
    }

    return Math.floor((toDate.getTime() - fromDate.getTime()) / DAY_IN_MS);
};

export const useTelegramAnalytics = (t: TranslateFn) => {
    const form = reactive({
        chatUsername: 'durov',
        keyword: '',
        periodDays: 7 as AnalyticsPeriod,
        dateFrom: '',
        dateTo: '',
        scorePriority: 'balanced' as ScorePriority,
    });

    const loading = ref(false);
    const error = ref<string | null>(null);
    const payload = ref<TelegramAnalyticsSummary | null>(null);

    const periodLabel = computed(() => {
        if (form.dateFrom && form.dateTo) {
            return `${form.dateFrom} -> ${form.dateTo}`;
        }

        return t(`telegram.analytics.periods.${form.periodDays}`);
    });

    const normalizedChatUsername = () => form.chatUsername.trim().replace(/^@+/, '');
    const normalizedKeyword = () => form.keyword.trim();
    const dateLimits = computed(() => ({
        fromMin: form.dateTo ? shiftDate(form.dateTo, -6) : null,
        fromMax: form.dateTo || null,
        toMin: form.dateFrom || null,
        toMax: form.dateFrom ? shiftDate(form.dateFrom, 6) : null,
    }));

    const buildQuery = () => {
        const locale =
            typeof document !== 'undefined' && document.documentElement.lang.toLowerCase().startsWith('ru')
                ? 'ru'
                : 'en';
        const query = new URLSearchParams({
            chatUsername: normalizedChatUsername(),
            scorePriority: form.scorePriority,
            locale,
        });

        if (normalizedKeyword()) {
            query.set('keyword', normalizedKeyword());
        }

        if (form.dateFrom || form.dateTo) {
            query.set('dateFrom', form.dateFrom);
            query.set('dateTo', form.dateTo);
        } else {
            query.set('periodDays', String(form.periodDays));
        }

        return query;
    };

    const summaryUrl = () => `/telegram/analytics/summary?${buildQuery().toString()}`;
    const reportUrl = () => `/telegram/analytics/report?${buildQuery().toString()}`;

    const applyPreset = (days: AnalyticsPeriod) => {
        form.periodDays = days;
        form.dateFrom = '';
        form.dateTo = '';
    };

    const setPriority = (priority: ScorePriority) => {
        form.scorePriority = PRIORITIES.includes(priority) ? priority : 'balanced';
    };

    const loadAnalytics = async () => {
        if (!normalizedChatUsername()) {
            error.value = t('telegram.analytics.errors.channelRequired');

            return;
        }

        if (form.dateFrom && form.dateTo) {
            const days = diffDays(form.dateFrom, form.dateTo);

            if (days !== null && days > 6) {
                error.value = t('telegram.analytics.errors.rangeTooLong');

                return;
            }
        }

        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(summaryUrl(), {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
            });

            const data = await response.json();

            if (!response.ok || !data?.ok) {
                const firstError =
                    data?.errors && typeof data.errors === 'object'
                        ? Object.values(data.errors).flat().find((message) => typeof message === 'string')
                        : null;

                error.value =
                    (typeof firstError === 'string' ? firstError : null) ??
                    data?.message ??
                    t('telegram.analytics.errors.loadFailed');
                payload.value = null;

                return;
            }

            payload.value = data.data as TelegramAnalyticsSummary;
        } catch (exception) {
            error.value = exception instanceof Error ? exception.message : t('telegram.analytics.errors.loadFailed');
            payload.value = null;
        } finally {
            loading.value = false;
        }
    };

    const openReport = () => {
        if (typeof window === 'undefined') {
            return;
        }

        window.open(reportUrl(), '_blank', 'noopener,noreferrer');
    };

    const trendMax = computed(() => {
        const buckets = payload.value?.summary.timeline ?? [];

        return Math.max(
            1,
            ...buckets.map((bucket) =>
                Math.max(bucket.messages, bucket.views, bucket.replies, bucket.reactions, bucket.interactions)
            )
        );
    });

    const totalMessages = computed(() => payload.value?.summary.totals.messages ?? 0);
    const activePriority = computed<ScorePriority>(() => payload.value?.score.priority ?? form.scorePriority);

    return {
        PERIODS,
        PRIORITIES,
        form,
        loading,
        error,
        payload,
        periodLabel,
        dateLimits,
        trendMax,
        totalMessages,
        activePriority,
        applyPreset,
        setPriority,
        loadAnalytics,
        openReport,
    };
};
