import { computed, onBeforeUnmount, onMounted, ref, shallowRef } from 'vue';
import { useI18n } from '@/composables/useI18n';
import {
    getRepeatQueryParams,
    isRepeatAutorunEnabled,
    readRepeatQueryParam,
} from '@/composables/useRepeatQuery';
import {
    ApiError,
    apiRequestOrThrow,
    resolveClientErrorMessage,
} from '@/lib/api';
import {
    analytics,
    lookup,
    options as searchOptions,
    report,
} from '@/routes/news-media-intel';
import {
    competitorNames,
    filterParameters,
    initialNewsFilters,
    marketingFormError,
    normalizeVisibilityDomain,
} from './form';
import type {
    MarketingForm,
    NewsAnalytics,
    NewsFilters,
    NewsOptions,
    NewsResult,
} from './types';

export const useNewsIntel = (mode: 'search' | 'analytics') => {
    const { t, locale } = useI18n();
    const form = ref<MarketingForm>({
        query: '',
        brand: '',
        competitors: '',
        domain: '',
    });
    const filters = ref<NewsFilters>(initialNewsFilters());
    const options = shallowRef<NewsOptions | null>(null);
    const searchResult = shallowRef<NewsResult | null>(null);
    const analyticsResult = shallowRef<NewsAnalytics | null>(null);
    const loading = shallowRef(false);
    const optionsLoading = shallowRef(false);
    const error = shallowRef('');
    const optionsError = shallowRef('');
    const optionsController = new AbortController();
    let controller: AbortController | undefined;
    let requestId = 0;
    let disposed = false;
    const validationError = computed(() => {
        const code =
            mode === 'analytics'
                ? marketingFormError(form.value)
                : form.value.query.trim().length < 2 ||
                    form.value.query.trim().length > 180
                  ? 'queryRequired'
                  : null;

        return code ? t(`newsMediaIntel.errors.${code}`) : '';
    });
    const canRun = computed(
        () =>
            Boolean(options.value) &&
            !validationError.value &&
            (mode === 'analytics' || filters.value.categories.length > 0)
    );
    const loadOptions = async () => {
        optionsLoading.value = true;
        optionsError.value = '';

        try {
            const result = await apiRequestOrThrow<NewsOptions>(
                searchOptions.url(),
                { signal: optionsController.signal, retry: { attempts: 0 } }
            );

            if (disposed) {
                return;
            }

            options.value = result;
            Object.assign(filters.value, result.defaults);

            if (mode === 'analytics') {
                filters.value.categories = ['news', 'general'];
            }
        } catch (exception) {
            if (!disposed && !optionsController.signal.aborted) {
                optionsError.value = resolveClientErrorMessage(
                    exception,
                    t('newsMediaIntel.errors.optionsFailed')
                );
            }
        } finally {
            if (!disposed) {
                optionsLoading.value = false;
            }
        }
    };
    const run = async () => {
        if (!canRun.value || disposed) {
            if (!disposed) {
                error.value = validationError.value;
            }

            return;
        }

        controller?.abort();
        controller = new AbortController();
        const activeController = controller;
        const activeId = ++requestId;
        loading.value = true;
        error.value = '';

        try {
            if (mode === 'search') {
                const result = await apiRequestOrThrow<NewsResult>(
                    lookup.url(),
                    {
                        query: {
                            query: form.value.query.trim(),
                            locale: locale.value,
                            ...filterParameters(filters.value),
                        },
                        signal: activeController.signal,
                        retry: { attempts: 0 },
                    }
                );

                if (!disposed && activeId === requestId) {
                    searchResult.value = result;
                }
            } else {
                const result = await apiRequestOrThrow<NewsAnalytics>(
                    analytics.url(),
                    {
                        method: 'POST',
                        body: {
                            query: form.value.query.trim(),
                            brand: form.value.brand.trim() || null,
                            competitors: competitorNames(
                                form.value.competitors
                            ),
                            domain: normalizeVisibilityDomain(
                                form.value.domain
                            ),
                            language: filters.value.language,
                            timeRange: filters.value.timeRange,
                            safeSearch: filters.value.safeSearch,
                            engines: [...filters.value.engines],
                            maxPages: filters.value.maxPages,
                            locale: locale.value,
                        },
                        signal: activeController.signal,
                        retry: { attempts: 0 },
                    }
                );

                if (!disposed && activeId === requestId) {
                    analyticsResult.value = result;
                }
            }
        } catch (exception) {
            if (
                !disposed &&
                activeId === requestId &&
                !activeController.signal.aborted
            ) {
                const fields =
                    exception instanceof ApiError
                        ? Object.values(exception.errors ?? {})
                              .flat()
                              .join(' ')
                        : '';
                error.value =
                    fields ||
                    resolveClientErrorMessage(
                        exception,
                        t('newsMediaIntel.errors.lookupFailed')
                    );
            }
        } finally {
            if (!disposed && activeId === requestId) {
                loading.value = false;
            }
        }
    };
    const cancel = () => {
        ++requestId;
        controller?.abort();
        loading.value = false;
    };
    const reportUrl = (format: 'html' | 'json', download = false) => {
        if (!analyticsResult.value?.reportId) {
            return '';
        }

        return report.url(analyticsResult.value.reportId, {
            query: { format, download: download ? 1 : 0, locale: locale.value },
        });
    };
    onMounted(async () => {
        const params = getRepeatQueryParams();

        if (params && readRepeatQueryParam(params, ['query'])) {
            form.value.query = readRepeatQueryParam(params, ['query']);
        }

        if (params && mode === 'analytics') {
            form.value.brand = readRepeatQueryParam(params, ['brand']);
            form.value.domain = readRepeatQueryParam(params, ['domain']);
        }

        await loadOptions();

        if (
            !disposed &&
            params &&
            isRepeatAutorunEnabled(params) &&
            canRun.value
        ) {
            void run();
        }
    });
    onBeforeUnmount(() => {
        disposed = true;
        optionsController.abort();
        cancel();
    });

    return {
        form,
        filters,
        options,
        loading,
        optionsLoading,
        error,
        optionsError,
        searchResult,
        analyticsResult,
        validationError,
        canRun,
        loadOptions,
        run,
        cancel,
        reportUrl,
    };
};
