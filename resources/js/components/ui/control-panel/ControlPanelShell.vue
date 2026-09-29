<script setup lang="ts">
import { ChevronDown, ChevronUp } from 'lucide-vue-next';
import type { Component } from 'vue';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';

defineProps<{
    title: string;
    helpLabel: string;
    helpText: string;
    subtitle: string;
    collapsedText: string;
    collapsed: boolean;
    icon: Component;
    iconClass?: string;
    bodyClass?: string;
}>();

const emit = defineEmits<{
    'update:collapsed': [value: boolean];
}>();
</script>

<template>
    <section
        class="intel-panel-strong min-w-0 shrink-0 md:sticky md:top-0 md:z-10"
    >
        <div class="flex min-w-0 items-start justify-between gap-3">
            <div class="min-w-0 space-y-1">
                <div
                    class="flex min-w-0 items-center gap-2 text-base font-semibold"
                >
                    <component
                        :is="icon"
                        class="h-4 w-4 shrink-0"
                        :class="iconClass ?? 'text-primary'"
                    />
                    <h2 class="min-w-0 break-words">{{ title }}</h2>
                    <HelpTooltip :label="helpLabel" :text="helpText" />
                </div>
                <p
                    class="text-sm leading-relaxed break-words text-muted-foreground"
                >
                    {{ collapsed ? collapsedText : subtitle }}
                </p>
            </div>

            <button
                type="button"
                class="inline-flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-xl border border-input text-sm text-foreground transition-colors hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                :aria-expanded="!collapsed"
                :aria-label="collapsed ? subtitle : collapsedText"
                :title="collapsed ? subtitle : collapsedText"
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
