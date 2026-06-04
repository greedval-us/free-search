<script setup lang="ts">
import ModulePage from '@/components/ui/ModulePage.vue';
import { BLUESKY_TABS } from './bluesky/tabs';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Bluesky',
                titleKey: 'bluesky.headTitle',
                href: '/bluesky',
            },
        ],
    },
});
</script>

<template>
    <ModulePage
        title-key="bluesky.headTitle"
        :tabs="BLUESKY_TABS"
        default-tab="search"
    />
</template>
