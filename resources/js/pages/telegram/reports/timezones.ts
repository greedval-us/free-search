type TimezonePreset = {
    value: string;
    city: string;
    group: 'russia' | 'world';
};

export const REPORT_TIMEZONES: readonly TimezonePreset[] = [
    { value: 'Europe/Kaliningrad', city: 'kaliningrad', group: 'russia' },
    { value: 'Europe/Moscow', city: 'moscow', group: 'russia' },
    { value: 'Europe/Samara', city: 'samara', group: 'russia' },
    { value: 'Asia/Yekaterinburg', city: 'yekaterinburg', group: 'russia' },
    { value: 'Asia/Omsk', city: 'omsk', group: 'russia' },
    { value: 'Asia/Krasnoyarsk', city: 'krasnoyarsk', group: 'russia' },
    { value: 'Asia/Irkutsk', city: 'irkutsk', group: 'russia' },
    { value: 'Asia/Yakutsk', city: 'yakutsk', group: 'russia' },
    { value: 'Asia/Vladivostok', city: 'vladivostok', group: 'russia' },
    { value: 'Asia/Magadan', city: 'magadan', group: 'russia' },
    { value: 'Asia/Kamchatka', city: 'kamchatka', group: 'russia' },
    { value: 'UTC', city: 'utc', group: 'world' },
    { value: 'Europe/London', city: 'london', group: 'world' },
    { value: 'Europe/Berlin', city: 'berlin', group: 'world' },
    { value: 'Europe/Kyiv', city: 'kyiv', group: 'world' },
    { value: 'Europe/Minsk', city: 'minsk', group: 'world' },
    { value: 'Europe/Istanbul', city: 'istanbul', group: 'world' },
    { value: 'Asia/Tbilisi', city: 'tbilisi', group: 'world' },
    { value: 'Asia/Dubai', city: 'dubai', group: 'world' },
    { value: 'Asia/Almaty', city: 'almaty', group: 'world' },
    { value: 'Asia/Tashkent', city: 'tashkent', group: 'world' },
    { value: 'Asia/Kolkata', city: 'kolkata', group: 'world' },
    { value: 'Asia/Bangkok', city: 'bangkok', group: 'world' },
    { value: 'Asia/Shanghai', city: 'shanghai', group: 'world' },
    { value: 'Asia/Tokyo', city: 'tokyo', group: 'world' },
    { value: 'America/New_York', city: 'newYork', group: 'world' },
    { value: 'America/Los_Angeles', city: 'losAngeles', group: 'world' },
];

export const getDeviceTimezone = (): string | null => {
    try {
        return Intl.DateTimeFormat().resolvedOptions().timeZone || null;
    } catch {
        return null;
    }
};

export const timezoneLabel = (
    timezone: string,
    locale: string,
    translate: (key: string) => string,
    date = new Date()
): string => {
    const preset = REPORT_TIMEZONES.find((item) => item.value === timezone);
    let name = preset
        ? translate(`telegramReports.timezoneCities.${preset.city}`)
        : timezone.split('/').at(-1)?.replaceAll('_', ' ') || timezone;

    try {
        if (!preset) {
            name =
                new Intl.DateTimeFormat(locale, {
                    timeZone: timezone,
                    timeZoneName: 'longGeneric',
                })
                    .formatToParts(date)
                    .find((part) => part.type === 'timeZoneName')?.value ||
                name;
        }

        const offset = new Intl.DateTimeFormat('en', {
            timeZone: timezone,
            timeZoneName: 'shortOffset',
        })
            .formatToParts(date)
            .find((part) => part.type === 'timeZoneName')?.value;
        const match = offset?.match(/^GMT([+-])(\d{1,2})(?::(\d{2}))?$/);
        const utc = match
            ? `UTC${match[1]}${match[2].padStart(2, '0')}:${match[3] || '00'}`
            : offset === 'GMT'
              ? 'UTC+00:00'
              : offset;

        return utc ? `${name} — ${utc}` : name;
    } catch {
        return name;
    }
};
