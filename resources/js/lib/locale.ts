export type Locale = 'en' | 'ru';

export const LOCALE_STORAGE_KEY = 'locale';

export const isLocale = (value: unknown): value is Locale =>
    value === 'en' || value === 'ru';

export const getDocumentLocale = (): Locale =>
    typeof document !== 'undefined' &&
    document.documentElement.lang.toLowerCase().startsWith('ru')
        ? 'ru'
        : 'en';
