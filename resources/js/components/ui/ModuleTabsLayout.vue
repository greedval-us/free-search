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
        <div
            class="intel-tabbar intel-scroll flex shrink-0 items-center justify-start gap-1.5 overflow-x-auto overscroll-x-contain sm:flex-wrap sm:justify-center"
            role="tablist"
        >
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                @click="selectTab(tab.key)"
                :id="`module-tab-${tab.key}`"
                role="tab"
                :aria-selected="activeTab === tab.key"
                :aria-controls="`module-panel-${tab.key}`"
                :tabindex="activeTab === tab.key ? 0 : -1"
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

        <div
            :id="`module-panel-${activeTab}`"
            class="flex min-h-0 min-w-0 flex-1 flex-col"
            role="tabpanel"
            :aria-labelledby="`module-tab-${activeTab}`"
        >
            <slot />
        </div>
    </IntelModuleLayout>
</template>
