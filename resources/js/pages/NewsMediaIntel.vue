<script setup lang="ts">
import ModulePage from '@/components/ui/ModulePage.vue';
import { newsMediaIntel } from '@/routes';
import { NEWS_MEDIA_TABS } from './news-media-intel/tabs';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'News & Media Intel',
                titleKey: 'newsMediaIntel.headTitle',
                href: newsMediaIntel(),
            },
        ],
    },
});
</script>

<template>
    <ModulePage
        title-key="newsMediaIntel.headTitle"
        :tabs="NEWS_MEDIA_TABS"
        default-tab="search"
    />
</template>
