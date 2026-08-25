import { useLocale } from '@/composables/useLocale';
import type { Locale } from '@/composables/useLocale';
import en from '@/locales/en';
import ru from '@/locales/ru';

type Messages = Record<string, unknown>;
type TranslationReplacements = Record<string, string | number>;

const dictionaries: Record<Locale, Messages> = { en, ru };

const getNestedValue = (dictionary: Messages, key: string): string | null => {
    const value = key.split('.').reduce<unknown>((accumulator, segment) => {
        if (
            accumulator &&
            typeof accumulator === 'object' &&
            segment in accumulator
        ) {
            return (accumulator as Record<string, unknown>)[segment];
        }

        return null;
    }, dictionary);

    return typeof value === 'string' ? value : null;
};

const interpolate = (
    message: string,
    replacements?: TranslationReplacements
): string => {
    if (!replacements) {
        return message;
    }

    return Object.entries(replacements).reduce(
        (result, [token, value]) =>
            result.replaceAll(`{${token}}`, String(value)),
        message
    );
};

export const useI18n = () => {
    const { locale, setLocale } = useLocale();

    const t = (key: string, replacements?: TranslationReplacements): string => {
        return interpolate(
            getNestedValue(dictionaries[locale.value], key) ??
                getNestedValue(dictionaries.en, key) ??
                key,
            replacements
        );
    };

    return {
        locale,
        t,
        setLocale,
    };
};
