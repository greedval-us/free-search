<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import ModuleTabsLayout from '@/components/ui/ModuleTabsLayout.vue';
import { useI18n } from '@/composables/useI18n';
import { useRepeatTab } from '@/composables/useRepeatTab';
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

const { t } = useI18n();
const { activeTab, activeTabDefinition } = useRepeatTab(
    SITE_INTEL_TABS,
    'siteHealth'
);

const pageTitle = computed(() => t('siteIntel.headTitle'));
</script>

<template>
    <Head :title="pageTitle" />

    <ModuleTabsLayout v-model:active-tab="activeTab" :tabs="SITE_INTEL_TABS">
        <component :is="activeTabDefinition.component" />
    </ModuleTabsLayout>
</template>
