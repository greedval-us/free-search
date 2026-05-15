<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
    bodyClass?: string;
}>();
</script>

<template>
    <div class="intel-section" :class="bodyClass">
        <p class="mb-2 font-semibold">{{ title }}</p>
        <p v-if="description" class="mb-2 intel-caption">{{ description }}</p>
        <slot />
    </div>
</template>
