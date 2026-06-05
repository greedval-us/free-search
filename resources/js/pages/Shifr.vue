<script setup lang="ts">
import ModulePage from '@/components/ui/ModulePage.vue';
import { SHIFR_TABS } from './shifr/tabs';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Shifr',
                titleKey: 'shifr.headTitle',
                href: '/shifr',
            },
        ],
    },
});
</script>

<template>
    <ModulePage
        title-key="shifr.headTitle"
        :tabs="SHIFR_TABS"
        default-tab="hash"
    />
</template>
