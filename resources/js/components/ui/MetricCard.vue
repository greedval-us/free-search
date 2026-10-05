<script setup lang="ts">
import type { Component } from 'vue';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';

withDefaults(
    defineProps<{
        title: string;
        value: string | number;
        helpText?: string;
        tone?: 'default' | 'positive' | 'warning' | 'danger';
        caption?: string;
        icon?: Component;
        prominent?: boolean;
    }>(),
    {
        helpText: undefined,
        tone: 'default',
        caption: undefined,
    }
);
</script>

<template>
    <div
        class="intel-surface"
        :class="{
            'border-emerald-500/35 bg-emerald-500/8': tone === 'positive',
            'border-amber-500/35 bg-amber-500/8': tone === 'warning',
            'border-rose-500/35 bg-rose-500/8': tone === 'danger',
        }"
    >
        <p class="intel-title flex min-w-0 items-center gap-2">
            <component
                :is="icon"
                v-if="icon"
                class="size-4 shrink-0 text-primary"
                aria-hidden="true"
            />
            <span>{{ title }}</span>
            <HelpTooltip
                v-if="helpText"
                :label="title"
                :text="helpText"
                width-class="w-64"
                align="right"
            />
        </p>
        <p class="intel-value" :class="{ 'text-3xl!': prominent }">
            {{ value }}
        </p>
        <p v-if="caption" class="intel-caption mt-1">
            {{ caption }}
        </p>
    </div>
</template>
