import type {
    MarketingForm,
    NewsCategory,
    NewsFilters,
    NewsOptions,
} from './types';

export const initialNewsFilters = (): NewsFilters => ({
    categories: ['news'],
    language: 'ru',
    timeRange: '',
    safeSearch: 1,
    engines: [],
    maxPages: 3,
});
export const competitorNames = (value: string): string[] =>
    value
        .split(/\r\n?|\n/)
        .map((name) => name.trim())
        .filter(Boolean);
export const marketingFormError = (
    form: MarketingForm
):
    | 'queryRequired'
    | 'competitorLimit'
    | 'duplicateCompetitors'
    | 'invalidDomain'
    | null => {
    if (form.query.trim().length < 2 || form.query.trim().length > 180) {
        return 'queryRequired';
    }

    const competitors = competitorNames(form.competitors);

    if (
        competitors.length > 3 ||
        competitors.some((name) => name.length < 2 || name.length > 80) ||
        (form.brand.trim().length > 0 && form.brand.trim().length < 2) ||
        form.brand.trim().length > 80
    ) {
        return 'competitorLimit';
    }

    const labels = [form.brand.trim(), ...competitors]
        .filter(Boolean)
        .map((name) => name.toLocaleLowerCase());

    if (new Set(labels).size !== labels.length) {
        return 'duplicateCompetitors';
    }

    if (form.domain.trim() && !normalizeVisibilityDomain(form.domain)) {
        return 'invalidDomain';
    }

    return null;
};
export const normalizeVisibilityDomain = (value: string): string | null => {
    const input = value.trim();

    if (!input || /[\s\\\p{Cc}]/u.test(input)) {
        return null;
    }

    try {
        const url = new URL(input.includes('://') ? input : `https://${input}`);

        if (
            !['https:', 'http:'].includes(url.protocol) ||
            url.username ||
            url.password ||
            url.port ||
            !url.hostname.includes('.') ||
            /^\d+(?:\.\d+){3}$/u.test(url.hostname) ||
            url.hostname
                .split('.')
                .some(
                    (label) =>
                        !/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/iu.test(label)
                ) ||
            /\.(?:local|localhost|internal|intranet|lan|home|test|invalid|example)$/iu.test(
                url.hostname
            )
        ) {
            return null;
        }

        return url.hostname.toLowerCase().replace(/^www\./u, '');
    } catch {
        return null;
    }
};
export const availableNewsEngines = (
    options: NewsOptions | null,
    categories: NewsCategory[]
): string[] =>
    [
        ...new Set(
            categories.flatMap((category) => options?.engines[category] ?? [])
        ),
    ].sort();
export const filterParameters = (
    filters: NewsFilters
): Record<string, string | number> => ({
    language: filters.language,
    timeRange: filters.timeRange,
    safeSearch: filters.safeSearch,
    maxPages: filters.maxPages,
    ...Object.fromEntries(
        filters.categories.map((category, index) => [
            `categories[${index}]`,
            category,
        ])
    ),
    ...Object.fromEntries(
        filters.engines.map((engine, index) => [`engines[${index}]`, engine])
    ),
});
