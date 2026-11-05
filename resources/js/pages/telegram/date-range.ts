import type { Translate } from '@/composables/useI18n';

const DAY_IN_MS = 24 * 60 * 60 * 1000;

const formatDate = (value: Date): string | null =>
    Number.isFinite(value.getTime()) ? value.toISOString().split('T')[0] : null;

const parseDate = (value: string): Date | null => {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return null;
    }

    const date = new Date(`${value}T00:00:00Z`);

    return formatDate(date) === value ? date : null;
};

export const shiftDate = (value: string, days: number): string | null => {
    const parsed = parseDate(value);

    if (!parsed) {
        return null;
    }

    return formatDate(new Date(parsed.getTime() + days * DAY_IN_MS));
};

export const customRangeError = (
    from: string,
    to: string,
    maxDays: number,
    t: Translate
): string | null => {
    const fromDate = parseDate(from);
    const toDate = parseDate(to);

    if (!fromDate || !toDate || fromDate > toDate) {
        return t('telegram.customRangeInvalid');
    }

    const inclusiveDays =
        (toDate.getTime() - fromDate.getTime()) / DAY_IN_MS + 1;

    return inclusiveDays > maxDays
        ? t('telegram.customRangeTooLong', { days: maxDays })
        : null;
};
