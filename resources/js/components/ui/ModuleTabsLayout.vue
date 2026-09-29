<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import type { Component } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { edit as billing } from '@/routes/billing';
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

    if (access && access.limit <= 0) {
        router.visit(
            billing({ query: { feature: accessKey, reason: 'plan' } })
        );
        return;
    }

    emit('update:activeTab', tab);
};

const focusTab = (event: KeyboardEvent): void => {
    const buttons = Array.from(
        (
            event.currentTarget as HTMLElement
        ).querySelectorAll<HTMLButtonElement>('[role="tab"]')
    );
    const index = buttons.indexOf(event.target as HTMLButtonElement);
    if (index < 0) return;
    let next: number;
    switch (event.key) {
        case 'ArrowRight':
            next = (index + 1) % buttons.length;
            break;
        case 'ArrowLeft':
            next = (index - 1 + buttons.length) % buttons.length;
            break;
        case 'Home':
            next = 0;
            break;
        case 'End':
            next = buttons.length - 1;
            break;
        default:
            return;
    }
    event.preventDefault();
    buttons[next]?.focus();
    buttons[next]?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
};
</script>

<template>
    <IntelModuleLayout>
        <div
            class="intel-tabbar intel-scroll flex shrink-0 items-center justify-start gap-1.5 overflow-x-auto overscroll-x-contain"
            role="tablist"
            :aria-label="t('navigation.moduleTabs')"
            @keydown="focusTab"
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
            class="flex min-h-0 min-w-0 flex-none flex-col gap-4 md:flex-1"
            role="tabpanel"
            :aria-labelledby="`module-tab-${activeTab}`"
        >
            <slot />
        </div>
    </IntelModuleLayout>
</template>
