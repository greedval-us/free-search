import { computed, ref } from 'vue';
import en from '@/locales/en.json';
import ru from '@/locales/ru.json';

type Locale = 'en' | 'ru';
type Messages = Record<string, unknown>;

const dictionaries: Record<Locale, Messages> = { en, ru };
const LOCALE_STORAGE_KEY = 'locale';

const isLocale = (value: unknown): value is Locale => value === 'en' || value === 'ru';

const detectLocale = (): Locale => {
    if (typeof window !== 'undefined') {
        const storedLocale = window.localStorage.getItem(LOCALE_STORAGE_KEY);

        if (isLocale(storedLocale)) {
            return storedLocale;
        }
    }

    if (typeof document !== 'undefined') {
        const htmlLang = document.documentElement.lang.toLowerCase();

        if (htmlLang.startsWith('ru')) {
            return 'ru';
        }
    }

    return 'en';
};

const activeLocale = ref<Locale>(detectLocale());

if (typeof document !== 'undefined') {
    document.documentElement.lang = activeLocale.value;
}

const getNestedValue = (dictionary: Messages, key: string): string | null => {
    const value = key.split('.').reduce<unknown>((accumulator, segment) => {
        if (accumulator && typeof accumulator === 'object' && segment in accumulator) {
            return (accumulator as Record<string, unknown>)[segment];
        }

        return null;
    }, dictionary);

    return typeof value === 'string' ? value : null;
};

export const useI18n = () => {
    const locale = computed(() => activeLocale.value);

    const t = (key: string): string => {
        return (
            getNestedValue(dictionaries[activeLocale.value], key) ??
            getNestedValue(dictionaries.en, key) ??
            key
        );
    };

    const setLocale = (next: Locale) => {
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
        t,
        setLocale,
    };
};
