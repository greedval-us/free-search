<script setup lang="ts">
import SeoAuditHint from './SeoAuditHint.vue';

defineProps<{ title: string; hint?: string }>();
</script>

<template>
    <div class="rounded-lg border border-border/70 bg-background/60 p-3">
        <div v-if="hint" class="mb-2 flex items-center gap-2">
            <p class="font-semibold">{{ title }}</p>
            <SeoAuditHint :text="hint" />
        </div>
        <p v-else class="mb-2 font-semibold">{{ title }}</p>
        <slot />
    </div>
</template>
