<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import type { Component } from 'vue';
import { useI18n } from '@/composables/useI18n';
import IntelModuleLayout from './IntelModuleLayout.vue';

export type ModuleTabDefinition = {
    key: string;
    labelKey: string;
    icon: Component;
    component?: Component;
    accessKey?: string;
};

const props = defineProps<{
    tabs: readonly ModuleTabDefinition[];
    activeTab: string;
}>();

const emit = defineEmits<{
    'update:activeTab': [value: string];
}>();

const { t } = useI18n();
const page = usePage();

const selectTab = (tab: string): void => {
    const definition = props.tabs.find((item) => item.key === tab);
    const accessKey = definition?.accessKey ?? tab;
    const access = page.props.auth?.access?.features?.[accessKey];

    if (access && !access.allowed) {
        const reason = access.limit > 0 ? 'quota' : 'plan';
        router.visit(`/settings/billing?feature=${accessKey}&reason=${reason}`);
        return;
    }

    emit('update:activeTab', tab);
};
</script>

<template>
    <IntelModuleLayout>
        <div class="intel-tabbar flex flex-wrap items-center justify-center gap-1.5">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                @click="selectTab(tab.key)"
                :aria-pressed="activeTab === tab.key"
                :class="[
                    'intel-tab',
                    activeTab === tab.key
                        ? 'intel-tab-active'
                        : 'intel-tab-inactive',
                ]"
            >
                <component :is="tab.icon" class="mr-1.5 h-3.5 w-3.5 shrink-0" />
                <span>{{ t(tab.labelKey) }}</span>
            </button>
        </div>

        <slot />
    </IntelModuleLayout>
</template>
