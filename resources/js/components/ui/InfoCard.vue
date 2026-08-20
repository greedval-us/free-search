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
    <section class="intel-section min-w-0" :class="bodyClass">
        <div class="mb-2 flex min-w-0 items-center gap-2">
            <h3 class="min-w-0 break-words font-semibold">{{ title }}</h3>
            <HelpTooltip
                v-if="helpLabel && helpText"
                :label="helpLabel"
                :text="helpText"
            />
        </div>
        <slot />
    </section>
</template>
