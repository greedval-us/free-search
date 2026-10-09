import { computed, ref, shallowRef } from 'vue';
import ReportRoutes from '@/actions/App/Http/Controllers/NewsMediaIntel/NewsMediaReportsController';
import { useI18n } from '@/composables/useI18n';
import {
    initialScheduleFields,
    validScheduleTiming,
} from '@/features/scheduled-reports/constants';
import { useScheduledReports } from '@/features/scheduled-reports/useScheduledReports';
import { resolveClientErrorMessage } from '@/lib/api';
import { options as searchOptions } from '@/routes/news-media-intel';
import {
    competitorNames,
    initialNewsFilters,
    normalizeVisibilityDomain,
} from '../form';
import type { NewsOptions } from '../types';
import { reportFormError, reportQueries } from './form';
import type { ReportSchedule, ReportScheduleForm, ReportsList } from './types';

export const useNewsReports = () => {
    const { t } = useI18n();
    const options = shallowRef<NewsOptions | null>(null);
    const optionsError = shallowRef('');
    const form = ref<ReportScheduleForm>({
        ...initialScheduleFields(),
        queries: '',
        brand: '',
        competitors: '',
        domain: '',
        filters: { ...initialNewsFilters(), categories: ['news', 'general'] },
    });
    const reports = useScheduledReports<ReportsList, ReportSchedule>({
        namespace: 'newsMediaReports',
        routes: ReportRoutes,
        onLoaded: (result, firstLoad) => {
            if (firstLoad) {
                form.value.timezone = result.timezone;
            }
        },
    });
    const { list } = reports;
    const loadOptions = async () => {
        optionsError.value = '';

        try {
            const result = await reports.request<NewsOptions>(
                searchOptions.url()
            );

            if (reports.isActive()) {
                if (!options.value) {
                    Object.assign(form.value.filters, result.defaults);
                }

                options.value = result;
            }
        } catch (exception) {
            if (reports.isActive()) {
                optionsError.value = resolveClientErrorMessage(
                    exception,
                    t('newsMediaReports.optionsError')
                );
            }
        }
    };
    const queries = computed(() => reportQueries(form.value.queries));
    const validationError = computed(() => {
        const code = reportFormError(form.value, list.value?.maxQueries ?? 3);

        if (!code) {
            return '';
        }

        const namespace = [
            'competitorLimit',
            'duplicateCompetitors',
            'invalidDomain',
        ].includes(code)
            ? 'newsMediaIntel.errors'
            : 'newsMediaReports';

        return t(`${namespace}.${code}`, { max: list.value?.maxQueries ?? 3 });
    });
    const canCreate = computed(() =>
        Boolean(
            list.value &&
            options.value &&
            list.value.schedules.length < list.value.maxSchedules &&
            validScheduleTiming(form.value) &&
            queries.value.length &&
            !validationError.value
        )
    );

    const create = () =>
        reports.create(
            canCreate.value,
            {
                name: form.value.name.trim(),
                queries: queries.value,
                brand: form.value.brand.trim() || null,
                competitors: competitorNames(form.value.competitors),
                domain: normalizeVisibilityDomain(form.value.domain),
                language: form.value.filters.language,
                timeRange: form.value.filters.timeRange,
                safeSearch: form.value.filters.safeSearch,
                engines: [...form.value.filters.engines],
                maxPages: form.value.filters.maxPages,
                interval: form.value.interval,
                sendTime: form.value.sendTime,
                timezone: form.value.timezone.trim(),
                sendToBot: form.value.sendToBot,
            },
            () => {
                form.value.name = '';
                form.value.queries = '';
            }
        );
    const refresh = (nextPage = reports.page.value) =>
        reports.perform(async () => {
            await Promise.all([
                reports.load(nextPage),
                options.value ? Promise.resolve() : loadOptions(),
            ]);
        });
    reports.startPolling(refresh);

    return {
        ...reports,
        options,
        optionsError,
        form,
        queries,
        validationError,
        canCreate,
        create,
        refresh,
    };
};
