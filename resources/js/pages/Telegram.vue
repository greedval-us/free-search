<script setup lang="ts">
import ModulePage from '@/components/ui/ModulePage.vue';
import { TELEGRAM_TABS } from './telegram/tabs';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Telegram',
                href: '/telegram',
            },
        ],
    },
});

</script>

<template>
    <ModulePage
        title-key="telegram.headTitle"
        :tabs="TELEGRAM_TABS"
        default-tab="search"
    />
</template>
