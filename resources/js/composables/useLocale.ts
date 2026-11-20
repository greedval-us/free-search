import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { ComputedRef } from 'vue';
import { isLocale, LOCALE_STORAGE_KEY } from '@/lib/locale';
import type { Locale } from '@/lib/locale';

export type { Locale } from '@/lib/locale';

type LocaleState = {
    locale: ComputedRef<Locale>;
    setLocale: (next: Locale) => void;
};

const LOCALE_COOKIE_MAX_AGE_SECONDS = 60 * 60 * 24 * 365;

const browserLocale = ref<Locale | null>(null);

const normalizeLocale = (value: unknown): Locale =>
    isLocale(value) ? value : 'en';

const persistBrowserLocale = (locale: Locale): void => {
    document.documentElement.lang = locale;

    try {
        window.localStorage.setItem(LOCALE_STORAGE_KEY, locale);
    } catch {
        // Locale still persists in the cookie when storage is unavailable.
    }

    const secure = window.location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `locale=${locale}; Path=/; Max-Age=${LOCALE_COOKIE_MAX_AGE_SECONDS}; SameSite=Lax${secure}`;
};

export const useLocale = (): LocaleState => {
    const page = usePage();
    const serverLocale = computed(() => normalizeLocale(page.props.locale));

    if (typeof window === 'undefined') {
        return {
            locale: serverLocale,
            setLocale: (): void => undefined,
        };
    }

    if (browserLocale.value === null) {
        browserLocale.value = serverLocale.value;
        persistBrowserLocale(browserLocale.value);
    }

    const locale = computed(() => browserLocale.value ?? serverLocale.value);

    const setLocale = (next: Locale): void => {
        browserLocale.value = next;
        persistBrowserLocale(next);
    };

    return {
        locale,
        setLocale,
    };
};
