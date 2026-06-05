<script setup lang="ts">
import { ChevronDown, ChevronUp } from 'lucide-vue-next';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';

defineProps<{
    title: string;
    helpLabel: string;
    helpText: string;
    subtitle: string;
    collapsedText: string;
    collapsed: boolean;
    icon: object;
    iconClass?: string;
    bodyClass?: string;
}>();

const emit = defineEmits<{
    'update:collapsed': [value: boolean];
}>();
</script>

<template>
    <section class="intel-panel-strong sticky top-0 z-10 shrink-0">
        <div class="flex items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <component
                        :is="icon"
                        class="h-4 w-4"
                        :class="iconClass ?? 'text-cyan-400'"
                    />
                    <span>{{ title }}</span>
                    <HelpTooltip :label="helpLabel" :text="helpText" />
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ collapsed ? collapsedText : subtitle }}
                </p>
            </div>

            <button
                type="button"
                class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-input text-sm text-foreground transition hover:bg-accent"
                @click="emit('update:collapsed', !collapsed)"
            >
                <ChevronDown v-if="collapsed" class="h-4 w-4" />
                <ChevronUp v-else class="h-4 w-4" />
            </button>
        </div>

        <div v-if="!collapsed" class="mt-3" :class="bodyClass">
            <slot />
        </div>
    </section>
</template>
