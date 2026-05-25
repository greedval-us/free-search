<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import ModuleTabsLayout from '@/components/ui/ModuleTabsLayout.vue';
import { useI18n } from '@/composables/useI18n';
import { useRepeatTab } from '@/composables/useRepeatTab';
import { SHIFR_TABS } from './shifr/tabs';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Shifr',
                href: '/shifr',
            },
        ],
    },
});

const { t } = useI18n();
const { activeTab, activeTabDefinition } = useRepeatTab(SHIFR_TABS, 'hash');

const pageTitle = computed(() => t('shifr.headTitle'));
</script>

<template>
    <Head :title="pageTitle" />

    <ModuleTabsLayout v-model:active-tab="activeTab" :tabs="SHIFR_TABS">
        <component :is="activeTabDefinition.component" :key="activeTab" />
    </ModuleTabsLayout>
</template>
