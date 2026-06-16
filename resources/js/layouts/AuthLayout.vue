<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CookieNotice from '@/components/CookieNotice.vue';
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
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

    <AuthLayout :title="title" :description="description">
        <slot />
        <CookieNotice />
    </AuthLayout>
</template>
