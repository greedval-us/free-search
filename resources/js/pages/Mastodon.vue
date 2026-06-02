<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import ModuleTabsLayout from '@/components/ui/ModuleTabsLayout.vue';
import { useI18n } from '@/composables/useI18n';
import { useRepeatTab } from '@/composables/useRepeatTab';
import { MASTODON_TABS } from './mastodon/tabs';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Mastodon', href: '/mastodon' }],
    },
});

const { t } = useI18n();
const { activeTab, activeTabDefinition } = useRepeatTab(MASTODON_TABS, 'search');
const pageTitle = computed(() => t('mastodon.headTitle'));
</script>

<template>
    <Head :title="pageTitle" />

    <ModuleTabsLayout v-model:active-tab="activeTab" :tabs="MASTODON_TABS">
        <component :is="activeTabDefinition.component" />
    </ModuleTabsLayout>
</template>
