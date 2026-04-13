import { computed, reactive, ref } from 'vue';
import type { GdeltArticle, GdeltSearchResponse, GdeltSort } from '../types';

type TranslateFn = (key: string) => string;

const LIMIT_MIN = 1;
const LIMIT_MAX = 250;

const SORT_OPTIONS: Array<{ value: GdeltSort; labelKey: string }> = [
    { value: 'datedesc', labelKey: 'gdelt.filters.sortOptions.datedesc' },
    { value: 'dateasc', labelKey: 'gdelt.filters.sortOptions.dateasc' },
    { value: 'hybridrel', labelKey: 'gdelt.filters.sortOptions.hybridrel' },
    { value: 'toneasc', labelKey: 'gdelt.filters.sortOptions.toneasc' },
    { value: 'tonedesc', labelKey: 'gdelt.filters.sortOptions.tonedesc' },
];

const COUNTRY_OPTIONS = [
    { value: '', labelKey: 'gdelt.filters.countryOptions.any' },
    { value: 'US', labelKey: 'gdelt.filters.countryOptions.us' },
    { value: 'GB', labelKey: 'gdelt.filters.countryOptions.gb' },
    { value: 'RU', labelKey: 'gdelt.filters.countryOptions.ru' },
    { value: 'UA', labelKey: 'gdelt.filters.countryOptions.ua' },
    { value: 'DE', labelKey: 'gdelt.filters.countryOptions.de' },
    { value: 'FR', labelKey: 'gdelt.filters.countryOptions.fr' },
    { value: 'CN', labelKey: 'gdelt.filters.countryOptions.cn' },
    { value: 'IN', labelKey: 'gdelt.filters.countryOptions.in' },
    { value: 'BR', labelKey: 'gdelt.filters.countryOptions.br' },
    { value: 'TR', labelKey: 'gdelt.filters.countryOptions.tr' },
    { value: 'IR', labelKey: 'gdelt.filters.countryOptions.ir' },
    { value: 'IL', labelKey: 'gdelt.filters.countryOptions.il' },
    { value: 'SY', labelKey: 'gdelt.filters.countryOptions.sy' },
];

const SOURCE_LANG_OPTIONS = [
    { value: '', labelKey: 'gdelt.filters.sourceLangOptions.any' },
    { value: 'english', labelKey: 'gdelt.filters.sourceLangOptions.english' },
    { value: 'russian', labelKey: 'gdelt.filters.sourceLangOptions.russian' },
    { value: 'ukrainian', labelKey: 'gdelt.filters.sourceLangOptions.ukrainian' },
    { value: 'german', labelKey: 'gdelt.filters.sourceLangOptions.german' },
    { value: 'french', labelKey: 'gdelt.filters.sourceLangOptions.french' },
    { value: 'spanish', labelKey: 'gdelt.filters.sourceLangOptions.spanish' },
    { value: 'arabic', labelKey: 'gdelt.filters.sourceLangOptions.arabic' },
    { value: 'chinese', labelKey: 'gdelt.filters.sourceLangOptions.chinese' },
    { value: 'turkish', labelKey: 'gdelt.filters.sourceLangOptions.turkish' },
];

const quoteIfNeeded = (value: string) =>
    value.includes(' ') ? `"${value}"` : value;

const toDateInputValue = (date: Date) => date.toISOString().slice(0, 10);

const getDefaultRange = () => {
    const end = new Date();
    const start = new Date();
    start.setDate(end.getDate() - 6);

    return {
        dateFrom: toDateInputValue(start),
        dateTo: toDateInputValue(end),
    };
};

const formatSeenDate = (value: string) => {
    const normalized = value.trim();

    if (!normalized) {
        return '-';
    }

    if (/^\d{14}$/.test(normalized)) {
        const year = Number(normalized.slice(0, 4));
        const month = Number(normalized.slice(4, 6)) - 1;
        const day = Number(normalized.slice(6, 8));
        const hour = Number(normalized.slice(8, 10));
        const minute = Number(normalized.slice(10, 12));
        const second = Number(normalized.slice(12, 14));
        const date = new Date(Date.UTC(year, month, day, hour, minute, second));

        if (Number.isFinite(date.getTime())) {
            return date.toLocaleString();
        }
    }

    const parsed = new Date(normalized);

    if (!Number.isFinite(parsed.getTime())) {
        return normalized;
    }

    return parsed.toLocaleString();
};

const normalizeTone = (value: GdeltArticle['tone']) => {
    if (typeof value === 'number') {
        return Number.isFinite(value) ? value : null;
    }

    if (typeof value === 'string') {
        const parsed = Number(value);

        return Number.isFinite(parsed) ? parsed : null;
    }

    return null;
};

export const useGdeltSearch = (t: TranslateFn) => {
    const defaultRange = getDefaultRange();

    const form = reactive({
        keywords: '',
        exactPhrase: '',
        anyWords: '',
        excludeWords: '',
        domain: '',
        domainExact: '',
        sourceCountry: '',
        sourceLang: '',
        theme: '',
        person: '',
        organization: '',
        location: '',
        queryRaw: '',
        dateFrom: defaultRange.dateFrom,
        dateTo: defaultRange.dateTo,
        maxRecords: 50,
        sort: 'datedesc' as GdeltSort,
        toneMin: '',
        toneMax: '',
        onlyWithImage: false,
    });

    const loading = ref(false);
    const error = ref<string | null>(null);
    const items = ref<GdeltArticle[]>([]);
    const total = ref(0);
    const searchPanelCollapsed = ref(false);
    const showAdvanced = ref(false);
    const showQueryBuilder = ref(false);
    const lastQuery = ref('');

    const clampLimit = () => {
        const value = Number(form.maxRecords);

        if (!Number.isFinite(value)) {
            form.maxRecords = 50;

            return;
        }

        form.maxRecords = Math.min(LIMIT_MAX, Math.max(LIMIT_MIN, Math.trunc(value)));
    };

    const setDefaultWeekRange = () => {
        const range = getDefaultRange();
        form.dateFrom = range.dateFrom;
        form.dateTo = range.dateTo;
    };

    const applyPresetRange = (preset: '1d' | '3d' | '7d') => {
        const end = new Date();
        const start = new Date();
        const daysByPreset: Record<typeof preset, number> = {
            '1d': 0,
            '3d': 2,
            '7d': 6,
        };

        start.setDate(end.getDate() - daysByPreset[preset]);
        form.dateFrom = toDateInputValue(start);
        form.dateTo = toDateInputValue(end);
    };

    const isRangeWithinWeek = () => {
        if (!form.dateFrom || !form.dateTo) {
            return false;
        }

        const from = new Date(`${form.dateFrom}T00:00:00`);
        const to = new Date(`${form.dateTo}T00:00:00`);

        if (!Number.isFinite(from.getTime()) || !Number.isFinite(to.getTime())) {
            return false;
        }

        if (to < from) {
            return false;
        }

        const diffMs = to.getTime() - from.getTime();
        const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

        return diffDays <= 6;
    };

    const buildQueryTokens = () => {
        const tokens: string[] = [];
        const keywords = form.keywords.trim();
        const exactPhrase = form.exactPhrase.trim();
        const anyWords = form.anyWords.trim();
        const excludeWords = form.excludeWords.trim();
        const domain = form.domain.trim();
        const domainExact = form.domainExact.trim();
        const sourceLang = form.sourceLang.trim();
        const theme = form.theme.trim();
        const person = form.person.trim();
        const organization = form.organization.trim();
        const location = form.location.trim();
        const queryRaw = form.queryRaw.trim();

        if (keywords) {
            tokens.push(keywords);
        }

        if (exactPhrase) {
            tokens.push(`"${exactPhrase}"`);
        }

        if (anyWords) {
            const words = anyWords
                .split(/[,\s]+/)
                .map((word) => word.trim())
                .filter(Boolean);

            if (words.length > 0) {
                tokens.push(`(${words.join(' OR ')})`);
            }
        }

        if (excludeWords) {
            const words = excludeWords
                .split(/[,\s]+/)
                .map((word) => word.trim())
                .filter(Boolean);

            for (const word of words) {
                tokens.push(`-${word}`);
            }
        }

        if (domain) {
            tokens.push(`domain:${domain}`);
        }

        if (domainExact) {
            tokens.push(`domainis:${domainExact}`);
        }

        if (sourceLang) {
            tokens.push(`sourcelang:${sourceLang}`);
        }

        if (theme) {
            tokens.push(`theme:${theme}`);
        }

        if (person) {
            tokens.push(`person:${quoteIfNeeded(person)}`);
        }

        if (organization) {
            tokens.push(`organization:${quoteIfNeeded(organization)}`);
        }

        if (location) {
            tokens.push(`location:${quoteIfNeeded(location)}`);
        }

        if (queryRaw) {
            tokens.push(queryRaw);
        }

        return tokens;
    };

    const builtQueryPreview = computed(() => buildQueryTokens().join(' ').trim());

    const hasRequiredKeywords = computed(() => form.keywords.trim().length > 0);

    const canSearch = computed(() =>
        hasRequiredKeywords.value &&
        builtQueryPreview.value.length > 0 &&
        Boolean(form.dateFrom) &&
        Boolean(form.dateTo) &&
        isRangeWithinWeek()
    );

    const filteredItems = computed(() => {
        const toneMinValue = form.toneMin.trim();
        const toneMaxValue = form.toneMax.trim();
        const toneMin = toneMinValue === '' ? null : Number(toneMinValue);
        const toneMax = toneMaxValue === '' ? null : Number(toneMaxValue);

        const hasToneMin = toneMin !== null && Number.isFinite(toneMin);
        const hasToneMax = toneMax !== null && Number.isFinite(toneMax);

        return items.value.filter((item) => {
            if (form.onlyWithImage && !item.socialImage) {
                return false;
            }

            if (!hasToneMin && !hasToneMax) {
                return true;
            }

            const tone = normalizeTone(item.tone);

            if (tone === null) {
                return false;
            }

            if (hasToneMin && tone < (toneMin as number)) {
                return false;
            }

            if (hasToneMax && tone > (toneMax as number)) {
                return false;
            }

            return true;
        });
    });

    const search = async () => {
        if (!canSearch.value) {
            if (!hasRequiredKeywords.value) {
                error.value = t('gdelt.errors.keywordsRequired');
            } else if (builtQueryPreview.value.length === 0) {
                error.value = t('gdelt.errors.emptyQuery');
            } else if (!form.dateFrom || !form.dateTo) {
                error.value = t('gdelt.errors.periodRequired');
            } else {
                error.value = t('gdelt.errors.rangeTooLong');
            }

            return;
        }

        clampLimit();
        error.value = null;
        loading.value = true;
        items.value = [];
        total.value = 0;

        const query = builtQueryPreview.value;
        lastQuery.value = query;

        const params = new URLSearchParams();
        params.set('query', query);
        params.set('maxRecords', String(form.maxRecords));
        params.set('sort', form.sort);

        params.set('dateFrom', form.dateFrom);
        params.set('dateTo', form.dateTo);

        if (form.sourceCountry) {
            params.set('sourceCountry', form.sourceCountry);
        }

        try {
            const response = await fetch(`/gdelt/search/articles?${params.toString()}`, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
            });

            const payload = (await response.json()) as GdeltSearchResponse & {
                errors?: Record<string, string[]>;
            };

            if (!response.ok || !payload.ok) {
                if (payload.errors) {
                    const firstKey = Object.keys(payload.errors)[0];

                    if (firstKey && payload.errors[firstKey]?.length) {
                        error.value = payload.errors[firstKey][0];
                    } else {
                        error.value = payload.message ?? t('gdelt.errors.validationFailed');
                    }
                } else {
                    error.value = payload.message ?? t('gdelt.errors.requestFailed');
                }

                return;
            }

            items.value = Array.isArray(payload.items) ? payload.items : [];
            total.value = Number(payload.total ?? items.value.length);
        } catch (exception) {
            error.value = exception instanceof Error ? exception.message : t('gdelt.errors.unknownError');
        } finally {
            loading.value = false;
        }
    };

    return {
        LIMIT_MAX,
        SORT_OPTIONS,
        COUNTRY_OPTIONS,
        SOURCE_LANG_OPTIONS,
        form,
        loading,
        error,
        items,
        total,
        filteredItems,
        canSearch,
        builtQueryPreview,
        searchPanelCollapsed,
        showAdvanced,
        showQueryBuilder,
        lastQuery,
        clampLimit,
        applyPresetRange,
        setDefaultWeekRange,
        formatSeenDate,
        normalizeTone,
        search,
    };
};
