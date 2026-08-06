const LOCALE_STORAGE_KEY = 'locale';

export const resolveDownloadLocale = (): 'en' | 'ru' => {
    if (typeof window !== 'undefined') {
        const storedLocale = window.localStorage.getItem(LOCALE_STORAGE_KEY);

        if (storedLocale === 'ru' || storedLocale === 'en') {
            return storedLocale;
        }
    }

    if (typeof document !== 'undefined') {
        return document.documentElement.lang.toLowerCase().startsWith('ru')
            ? 'ru'
            : 'en';
    }

    return 'en';
};

export const withDownloadLocale = (url: string): string => {
    const nextUrl = new URL(url, window.location.origin);
    nextUrl.searchParams.set('locale', resolveDownloadLocale());

    return nextUrl.toString();
};
