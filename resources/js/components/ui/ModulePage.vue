<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useRepeatTab } from '@/composables/useRepeatTab';
import ModuleTabsLayout from './ModuleTabsLayout.vue';

type ModuleTabDefinition = {
    key: string;
    labelKey: string;
    icon: object;
    component: object;
    accessKey?: string;
};

const props = defineProps<{
    titleKey: string;
    tabs: readonly ModuleTabDefinition[];
    defaultTab: string;
}>();

const { t } = useI18n();
const { activeTab, activeTabDefinition } = useRepeatTab(
    props.tabs,
    props.defaultTab
);
const pageTitle = computed(() => t(props.titleKey));
</script>

<template>
    <Head :title="pageTitle" />

    <ModuleTabsLayout v-model:active-tab="activeTab" :tabs="tabs">
        <component :is="activeTabDefinition.component" />
    </ModuleTabsLayout>
</template>
