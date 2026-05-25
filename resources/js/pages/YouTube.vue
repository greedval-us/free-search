<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import ModuleTabsLayout from '@/components/ui/ModuleTabsLayout.vue';
import { useI18n } from '@/composables/useI18n';
import { useRepeatTab } from '@/composables/useRepeatTab';
import { YOUTUBE_TABS } from './youtube/tabs';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'YouTube', href: '/youtube' }],
    },
});

const { t } = useI18n();
const { activeTab, activeTabDefinition } = useRepeatTab(YOUTUBE_TABS, 'search');
const pageTitle = computed(() => t('youtube.headTitle'));
</script>

<template>
    <Head :title="pageTitle" />

    <ModuleTabsLayout v-model:active-tab="activeTab" :tabs="YOUTUBE_TABS">
        <component :is="activeTabDefinition.component" />
    </ModuleTabsLayout>
</template>
