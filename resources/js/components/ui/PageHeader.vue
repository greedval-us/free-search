<script setup lang="ts">
import type { Component } from 'vue';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';

defineProps<{
    icon?: Component;
    title: string;
    description?: string;
    helpLabel?: string;
    helpText?: string;
}>();
</script>

<template>
    <header class="min-w-0 space-y-1.5">
        <div class="flex min-w-0 items-center gap-2">
            <component
                :is="icon"
                v-if="icon"
                class="h-5 w-5 shrink-0 text-primary"
            />
            <h2
                class="min-w-0 break-words text-base font-semibold tracking-tight sm:text-lg"
            >
                {{ title }}
            </h2>
            <HelpTooltip
                v-if="helpLabel && helpText"
                :label="helpLabel"
                :text="helpText"
            />
        </div>
        <p
            v-if="description"
            class="max-w-3xl break-words text-sm leading-relaxed text-muted-foreground"
        >
            {{ description }}
        </p>
    </header>
</template>
