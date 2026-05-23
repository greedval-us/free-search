<script setup lang="ts">
import HelpTooltip from '@/components/ui/HelpTooltip.vue'

defineProps<{
    title: string;
    value: string | number;
    helpText?: string;
}>();
</script>

<template>
    <div class="intel-surface">
        <p class="intel-title inline-flex items-center gap-1">
            <span>{{ title }}</span>
            <HelpTooltip v-if="helpText" label="?" :text="helpText" width-class="w-64" align="right" />
        </p>
        <p class="intel-value">{{ value }}</p>
    </div>
</template>
