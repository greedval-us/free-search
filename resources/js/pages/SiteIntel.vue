<script setup lang="ts">
import ModulePage from '@/components/ui/ModulePage.vue';
import { SITE_INTEL_TABS } from './site-intel/tabs';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Site Intel',
                titleKey: 'siteIntel.headTitle',
                href: '/site-intel',
            },
        ],
    },
});
</script>

<template>
    <ModulePage
        title-key="siteIntel.headTitle"
        :tabs="SITE_INTEL_TABS"
        default-tab="siteHealth"
    />
</template>
