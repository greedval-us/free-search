<script setup lang="ts">
import ModulePage from '@/components/ui/ModulePage.vue';
import { YOUTUBE_TABS } from './youtube/tabs';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'YouTube',
                titleKey: 'youtube.headTitle',
                href: '/youtube',
            },
        ],
    },
});
</script>

<template>
    <ModulePage
        title-key="youtube.headTitle"
        :tabs="YOUTUBE_TABS"
        default-tab="search"
    />
</template>
