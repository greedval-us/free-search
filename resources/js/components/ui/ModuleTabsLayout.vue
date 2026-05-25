<script setup lang="ts">
import type { Component } from 'vue';
import { useI18n } from '@/composables/useI18n';

export type ModuleTabDefinition = {
    key: string;
    labelKey: string;
    icon: Component;
    component?: Component;
};

defineProps<{
    tabs: readonly ModuleTabDefinition[];
    activeTab: string;
}>();

const emit = defineEmits<{
    'update:activeTab': [value: string];
}>();

const { t } = useI18n();
</script>

<template>
    <div class="flex h-full min-h-0 flex-1 flex-col gap-4 overflow-hidden rounded-xl p-4">
        <div class="flex flex-wrap items-center justify-center gap-1 rounded-lg border border-border/70 bg-card/70 p-1 shadow-sm backdrop-blur">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                @click="emit('update:activeTab', tab.key)"
                :aria-pressed="activeTab === tab.key"
                :class="[
                    'inline-flex min-h-8 items-center rounded-md px-3 py-1.5 text-xs font-medium transition-colors',
                    activeTab === tab.key
                        ? 'bg-primary text-primary-foreground shadow-sm'
                        : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                ]"
            >
                <component :is="tab.icon" class="mr-1.5 h-3.5 w-3.5 shrink-0" />
                <span>{{ t(tab.labelKey) }}</span>
            </button>
        </div>

        <slot />
    </div>
</template>
