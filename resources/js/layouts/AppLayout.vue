<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CookieNotice from '@/components/CookieNotice.vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();
</script>

<template>
    <Head>
        <meta head-key="robots" name="robots" content="noindex,nofollow" />
        <meta
            head-key="googlebot"
            name="googlebot"
            content="noindex,nofollow"
        />
    </Head>

    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
        <CookieNotice />
    </AppLayout>
</template>
