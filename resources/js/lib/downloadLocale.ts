import { getDocumentLocale, isLocale, LOCALE_STORAGE_KEY } from '@/lib/locale';
import type { Locale } from '@/lib/locale';
import { resolveSameOriginUrl } from '@/lib/sameOriginUrl';

export const resolveDownloadLocale = (): Locale => {
    if (typeof window !== 'undefined') {
        let storedLocale: string | null = null;

        try {
            storedLocale = window.localStorage.getItem(LOCALE_STORAGE_KEY);
        } catch {
            // Browsers may disable storage; the document still carries the locale.
        }

        if (isLocale(storedLocale)) {
            return storedLocale;
        }
    }

    return getDocumentLocale();
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
