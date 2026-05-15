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
    <div class="space-y-1">
        <div class="flex items-center gap-2 text-sm font-semibold">
            <component :is="icon" v-if="icon" class="h-4 w-4 text-cyan-400" />
            <span>{{ title }}</span>
            <HelpTooltip v-if="helpLabel && helpText" :label="helpLabel" :text="helpText" />
        </div>
        <p v-if="description" class="text-xs text-muted-foreground">{{ description }}</p>
    </div>
</template>
