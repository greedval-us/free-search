import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Ref } from 'vue';

type SeoPageConfig = {
    path: string;
    title: Record<string, string>;
    description: Record<string, string>;
};

type SeoSharedProps = {
    pages?: Record<string, SeoPageConfig>;
};

export function useSeoPage(key: string, locale: Ref<string>) {
    const page = usePage();

    const config = computed(() => {
        const sharedSeo = (page.props.seo ?? {}) as SeoSharedProps;

        return sharedSeo.pages?.[key];
    });

    const fallbackLocale = 'en';

    const path = computed(() => config.value?.path ?? '/');
    const title = computed(
        () =>
            config.value?.title[locale.value] ??
            config.value?.title[fallbackLocale] ??
            String(page.props.name ?? 'Uraboros')
    );
    const description = computed(
        () =>
            config.value?.description[locale.value] ??
            config.value?.description[fallbackLocale] ??
            ''
    );

    return {
        path,
        title,
        description,
    };
}
