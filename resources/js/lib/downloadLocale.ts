import { resolveSameOriginUrl } from '@/lib/sameOriginUrl';

const LOCALE_STORAGE_KEY = 'locale';

export const resolveDownloadLocale = (): 'en' | 'ru' => {
    if (typeof window !== 'undefined') {
        let storedLocale: string | null = null;

        try {
            storedLocale = window.localStorage.getItem(LOCALE_STORAGE_KEY);
        } catch {
            // Browsers may disable storage; the document still carries the locale.
        }

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

export const withDownloadLocale = (url: string): string | null => {
    const safeUrl = resolveSameOriginUrl(url);

    if (!safeUrl) {
        return null;
    }

    const nextUrl = new URL(safeUrl);
    nextUrl.searchParams.set('locale', resolveDownloadLocale());

    return nextUrl.toString();
};
