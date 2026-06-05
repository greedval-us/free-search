<script setup lang="ts">
import HelpTooltip from '@/components/ui/HelpTooltip.vue';

withDefaults(
    defineProps<{
        title: string;
        value: string | number;
        helpText?: string;
        tone?: 'default' | 'positive' | 'warning' | 'danger';
        caption?: string;
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
        <p class="intel-title inline-flex items-center gap-1">
            <span>{{ title }}</span>
            <HelpTooltip
                v-if="helpText"
                label="?"
                :text="helpText"
                width-class="w-64"
                align="right"
            />
        </p>
        <p class="intel-value">{{ value }}</p>
        <p v-if="caption" class="mt-1 text-[11px] text-muted-foreground">
            {{ caption }}
        </p>
    </div>
</template>
