import { computed, reactive, ref } from 'vue';
import type { Translate } from '@/composables/useI18n';
import { apiRequest, resolveClientErrorMessage } from '@/lib/api';
import { getDocumentLocale } from '@/lib/locale';
import { report, summary } from '@/routes/telegram/analytics';
import { customRangeError, shiftDate } from '../date-range';
import type { TelegramAnalyticsSummary } from '../types';

export type AnalyticsPeriod = 1 | 3 | 7;
export type ScorePriority = 'balanced' | 'reach' | 'discussion' | 'virality';

export type TelegramAnalyticsForm = {
    chatUsername: string;
    keyword: string;
    periodDays: AnalyticsPeriod;
    dateFrom: string;
    dateTo: string;
    scorePriority: ScorePriority;
};

const PERIODS: readonly AnalyticsPeriod[] = [1, 3, 7];
const PRIORITIES: readonly ScorePriority[] = [
    'balanced',
    'reach',
    'discussion',
    'virality',
];

const extractYmdFromIso = (value: string | null | undefined): string | null => {
    if (!value) {
        return null;
    }

    const match = /^(\d{4}-\d{2}-\d{2})/.exec(value);

    return match ? match[1] : null;
};

export const useTelegramAnalytics = (
    t: Translate,
    customRangeMaxDays: () => number
) => {
    const form = reactive<TelegramAnalyticsForm>({
        chatUsername: '',
        keyword: '',
        periodDays: 7 as AnalyticsPeriod,
        dateFrom: '',
        dateTo: '',
        scorePriority: 'balanced' as ScorePriority,
    });

    const loading = ref(false);
    const comparisonLoading = ref(false);
    const error = ref<string | null>(null);
    const payload = ref<TelegramAnalyticsSummary | null>(null);
    const previousPayload = ref<TelegramAnalyticsSummary | null>(null);

    const periodLabel = computed(() => {
        if (form.dateFrom && form.dateTo) {
            return `${form.dateFrom} -> ${form.dateTo}`;
        }

        return t(`telegram.analytics.periods.${form.periodDays}`);
    });

    const normalizedChatUsername = () =>
        form.chatUsername.trim().replace(/^@+/, '');
    const normalizedKeyword = () => form.keyword.trim();
    const dateLimits = computed(() => {
        const maxOffset = customRangeMaxDays() - 1;

        return {
            fromMin: form.dateTo ? shiftDate(form.dateTo, -maxOffset) : null,
            fromMax: form.dateTo || null,
            toMin: form.dateFrom || null,
            toMax: form.dateFrom ? shiftDate(form.dateFrom, maxOffset) : null,
        };
    });

    const buildQuery = (snapshot?: TelegramAnalyticsSummary | null) => {
        let { dateFrom, dateTo } = form;
        const snapshotFrom = extractYmdFromIso(snapshot?.range.dateFrom);
        const snapshotTo = extractYmdFromIso(snapshot?.range.dateTo);

        if (snapshotFrom && snapshotTo) {
            dateFrom = snapshotFrom;
            dateTo = snapshotTo;
        }

        const keyword = snapshot
            ? snapshot.range.keyword?.trim()
            : normalizedKeyword();

        return {
            chatUsername:
                snapshot?.range.chatUsername ?? normalizedChatUsername(),
            scorePriority: snapshot?.score.priority ?? form.scorePriority,
            locale: getDocumentLocale(),
            keyword: keyword || undefined,
            ...(dateFrom || dateTo
                ? { dateFrom, dateTo }
                : { periodDays: form.periodDays }),
        };
    };

    const applyPreset = (days: AnalyticsPeriod) => {
        form.periodDays = days;
        form.dateFrom = '';
        form.dateTo = '';
    };

    const setPriority = (priority: ScorePriority) => {
        form.scorePriority = PRIORITIES.includes(priority)
            ? priority
            : 'balanced';
    };

    const loadAnalytics = async () => {
        if (!normalizedChatUsername()) {
            error.value = t('telegram.analytics.errors.channelRequired');

            return;
        }

        if (form.dateFrom && form.dateTo) {
            error.value = customRangeError(
                form.dateFrom,
                form.dateTo,
                customRangeMaxDays(),
                t
            );

            if (error.value !== null) {
                return;
            }
        }

        loading.value = true;
        comparisonLoading.value = false;
        error.value = null;
        previousPayload.value = null;

        try {
            const apiResult = await apiRequest<TelegramAnalyticsSummary>(
                summary.url({ query: buildQuery() }),
                {
                    method: 'GET',
                }
            );

            if (!apiResult.ok) {
                error.value =
                    apiResult.message ??
                    t('telegram.analytics.errors.loadFailed');
                payload.value = null;

                return;
            }

            payload.value = apiResult.data;

            previousPayload.value = apiResult.data.previousReport ?? null;
        } catch (exception) {
            error.value = resolveClientErrorMessage(
                exception,
                t('telegram.analytics.errors.loadFailed')
            );
            payload.value = null;
        } finally {
            loading.value = false;
        }
    };

    const showReport = (download: boolean) => {
        if (typeof window === 'undefined') {
            return;
        }

        const url = report.url({
            query: {
                ...buildQuery(payload.value),
                download: download ? 1 : undefined,
            },
        });
        window.open(url, '_blank', 'noopener,noreferrer');
    };

    const trendMax = computed(() => {
        const buckets = payload.value?.summary.timeline ?? [];

        return Math.max(
            1,
            ...buckets.map((bucket) =>
                Math.max(
                    bucket.messages,
                    bucket.views,
                    bucket.replies,
                    bucket.reactions,
                    bucket.interactions
                )
            )
        );
    });

    const totalMessages = computed(
        () => payload.value?.summary.totals.messages ?? 0
    );
    const activePriority = computed<ScorePriority>(
        () => payload.value?.score.priority ?? form.scorePriority
    );

    return {
        PERIODS,
        PRIORITIES,
        form,
        loading,
        comparisonLoading,
        error,
        payload,
        previousPayload,
        periodLabel,
        dateLimits,
        trendMax,
        totalMessages,
        activePriority,
        applyPreset,
        setPriority,
        loadAnalytics,
        openReport: () => showReport(false),
        downloadReport: () => showReport(true),
    };
};
