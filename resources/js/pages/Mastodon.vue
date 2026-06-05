<script setup lang="ts">
import ModulePage from '@/components/ui/ModulePage.vue';
import { MASTODON_TABS } from './mastodon/tabs';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Mastodon',
                titleKey: 'mastodon.headTitle',
                href: '/mastodon',
            },
        ],
    },
});
</script>

<template>
    <ModulePage
        title-key="mastodon.headTitle"
        :tabs="MASTODON_TABS"
        default-tab="search"
    />
</template>
