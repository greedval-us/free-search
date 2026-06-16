<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type SharedSeoProps = {
    siteUrl?: string;
};

const props = withDefaults(
    defineProps<{
        title: string;
        description: string;
        path?: string;
        robots?: string;
        type?: string;
        image?: string;
    }>(),
    {
        path: '/',
        robots: 'index,follow',
        type: 'website',
    }
);

const page = usePage();

const siteName = computed(() => String(page.props.name ?? 'Uraboros'));
const sharedSeo = computed(() => (page.props.seo ?? {}) as SharedSeoProps);

const normalizedSiteUrl = computed(() => {
    const raw = String(sharedSeo.value.siteUrl ?? '').trim();

    return raw.replace(/\/+$/, '');
});

const canonicalUrl = computed(() => {
    if (normalizedSiteUrl.value === '') {
        return undefined;
    }

    return new URL(props.path, `${normalizedSiteUrl.value}/`).toString();
});

const imageUrl = computed(() => {
    if (!props.image || normalizedSiteUrl.value === '') {
        return undefined;
    }

    return new URL(props.image, `${normalizedSiteUrl.value}/`).toString();
});
</script>

<template>
    <Head>
        <title>{{ title }}</title>
        <meta
            head-key="description"
            name="description"
            :content="description"
        />
        <meta head-key="robots" name="robots" :content="robots" />
        <meta head-key="googlebot" name="googlebot" :content="robots" />
        <link
            v-if="canonicalUrl"
            head-key="canonical"
            rel="canonical"
            :href="canonicalUrl"
        />

        <meta head-key="og:type" property="og:type" :content="type" />
        <meta head-key="og:title" property="og:title" :content="title" />
        <meta
            head-key="og:description"
            property="og:description"
            :content="description"
        />
        <meta
            head-key="og:site_name"
            property="og:site_name"
            :content="siteName"
        />
        <meta
            v-if="canonicalUrl"
            head-key="og:url"
            property="og:url"
            :content="canonicalUrl"
        />
        <meta
            v-if="imageUrl"
            head-key="og:image"
            property="og:image"
            :content="imageUrl"
        />

        <meta
            head-key="twitter:card"
            name="twitter:card"
            content="summary_large_image"
        />
        <meta head-key="twitter:title" name="twitter:title" :content="title" />
        <meta
            head-key="twitter:description"
            name="twitter:description"
            :content="description"
        />
        <meta
            v-if="imageUrl"
            head-key="twitter:image"
            name="twitter:image"
            :content="imageUrl"
        />
    </Head>
</template>
