import { marketingFormError } from '../form';
import type { ReportScheduleForm } from './types';

export const reportQueries = (value: string): string[] =>
    value
        .split(/\r\n?|\n/u)
        .map((query) => query.trim())
        .filter(Boolean);

export const reportFormError = (
    form: ReportScheduleForm,
    maxQueries: number
): string | null => {
    const queries = reportQueries(form.queries);

    if (queries.length > maxQueries) {
        return 'tooManyQueries';
    }

    if (
        new Set(queries.map((query) => query.toLocaleLowerCase())).size !==
        queries.length
    ) {
        return 'duplicateQueries';
    }

    if (queries.some((query) => /(?:^|\s)[!:][^\s]+|[\p{Cc}]/u.test(query))) {
        return 'queryRouting';
    }

    if (queries.some((query) => query.length < 2 || query.length > 180)) {
        return 'invalidQueries';
    }

    if (
        /[\p{Cc}]/u.test(form.brand) ||
        /[\p{Cc}]/u.test(form.competitors.replace(/\r\n?|\n/gu, ''))
    ) {
        return 'invalidNames';
    }

    return marketingFormError({
        query: queries[0] ?? 'valid query',
        brand: form.brand,
        competitors: form.competitors,
        domain: form.domain,
    });
};
