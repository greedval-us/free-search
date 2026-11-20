<script setup lang="ts">
import ModulePage from '@/components/ui/ModulePage.vue';
import { TELEGRAM_TABS } from './telegram/tabs';
import type { TelegramPageProps } from './telegram/types';

defineProps<TelegramPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Telegram',
                titleKey: 'telegram.headTitle',
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
