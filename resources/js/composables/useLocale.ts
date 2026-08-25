import { computed, ref } from 'vue';

export type Locale = 'en' | 'ru';

const LOCALE_STORAGE_KEY = 'locale';

const isLocale = (value: unknown): value is Locale =>
    value === 'en' || value === 'ru';

const detectLocale = (): Locale => {
    if (typeof window !== 'undefined') {
        const storedLocale = window.localStorage.getItem(LOCALE_STORAGE_KEY);

        if (isLocale(storedLocale)) {
            return storedLocale;
        }
    }

    if (
        typeof document !== 'undefined' &&
        document.documentElement.lang.toLowerCase().startsWith('ru')
    ) {
        return 'ru';
    }

    return 'en';
};

const activeLocale = ref<Locale>(detectLocale());

if (typeof document !== 'undefined') {
    document.documentElement.lang = activeLocale.value;
}

export const useLocale = () => {
    const locale = computed(() => activeLocale.value);

    const setLocale = (next: Locale): void => {
        activeLocale.value = next;

        if (typeof document !== 'undefined') {
            document.documentElement.lang = next;
        }

        if (typeof window !== 'undefined') {
            window.localStorage.setItem(LOCALE_STORAGE_KEY, next);
        }
    };

    return {
        locale,
        setLocale,
    };
};
