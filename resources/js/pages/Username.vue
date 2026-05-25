<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import ModuleTabsLayout from '@/components/ui/ModuleTabsLayout.vue';
import { useI18n } from '@/composables/useI18n';
import { useRepeatTab } from '@/composables/useRepeatTab';
import { USERNAME_TABS } from './username/tabs';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Username',
                href: '/username',
            },
        ],
    },
});

const { t } = useI18n();
const { activeTab, activeTabDefinition } = useRepeatTab(
    USERNAME_TABS,
    'search'
);

const pageTitle = computed(() => t('username.headTitle'));
</script>

<template>
    <Head :title="pageTitle" />

    <ModuleTabsLayout v-model:active-tab="activeTab" :tabs="USERNAME_TABS">
        <component :is="activeTabDefinition.component" />
    </ModuleTabsLayout>
</template>
