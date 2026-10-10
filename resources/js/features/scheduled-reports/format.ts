export const formatReportInstant = (
    value: string,
    locale: string,
    timezone?: string
): string =>
    new Date(value).toLocaleString(
        locale,
        timezone ? { timeZone: timezone } : undefined
    );

// Reporting periods are calendar dates, so viewing them in another local time
// zone must not move the first or last date by a day.
export const formatReportCalendarDate = (
    value: string,
    locale: string
): string =>
    new Date(`${value.slice(0, 10)}T00:00:00Z`).toLocaleDateString(locale, {
        timeZone: 'UTC',
    });
