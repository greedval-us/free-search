<script setup lang="ts">
import HelpTooltip from '@/components/ui/HelpTooltip.vue';

defineProps<{
    title: string;
    helpLabel?: string;
    helpText?: string;
    bodyClass?: string;
}>();
</script>

<template>
    <section class="intel-section" :class="bodyClass">
        <div class="mb-2 flex items-center gap-2">
            <p class="font-semibold">{{ title }}</p>
            <HelpTooltip
                v-if="helpLabel && helpText"
                :label="helpLabel"
                :text="helpText"
            />
        </div>
        <slot />
    </section>
</template>
