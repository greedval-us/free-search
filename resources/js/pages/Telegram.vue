<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import ModuleTabsLayout from '@/components/ui/ModuleTabsLayout.vue';
import { useI18n } from '@/composables/useI18n';
import { useRepeatTab } from '@/composables/useRepeatTab';
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

const { t } = useI18n();
const { activeTab, activeTabDefinition } = useRepeatTab(
    TELEGRAM_TABS,
    'search'
);

const pageTitle = computed(() => t('telegram.headTitle'));
</script>

<template>
    <Head :title="pageTitle" />

    <ModuleTabsLayout v-model:active-tab="activeTab" :tabs="TELEGRAM_TABS">
        <component :is="activeTabDefinition.component" />
    </ModuleTabsLayout>
</template>
