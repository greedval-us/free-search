<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
    bodyClass?: string;
}>();
</script>

<template>
    <section class="intel-section min-w-0" :class="bodyClass">
        <h3 class="mb-2 break-words font-semibold">{{ title }}</h3>
        <p v-if="description" class="intel-caption mb-2 break-words">
            {{ description }}
        </p>
        <slot />
    </section>
</template>
