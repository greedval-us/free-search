<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import ModuleTabsLayout from '@/components/ui/ModuleTabsLayout.vue';
import { useI18n } from '@/composables/useI18n';
import { useRepeatTab } from '@/composables/useRepeatTab';
import { BLUESKY_TABS } from './bluesky/tabs';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Bluesky', href: '/bluesky' }],
    },
});

const { t } = useI18n();
const { activeTab, activeTabDefinition } = useRepeatTab(BLUESKY_TABS, 'search');
const pageTitle = computed(() => t('bluesky.headTitle'));
</script>

<template>
    <Head :title="pageTitle" />

    <ModuleTabsLayout v-model:active-tab="activeTab" :tabs="BLUESKY_TABS">
        <component :is="activeTabDefinition.component" />
    </ModuleTabsLayout>
</template>
