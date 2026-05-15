<script setup lang="ts">
defineProps<{
    error?: string | null;
}>();
</script>

<template>
    <div class="mt-3 flex flex-wrap items-end gap-3">
        <slot />
    </div>

    <p v-if="error" class="mt-3 text-sm text-destructive">{{ error }}</p>
</template>
