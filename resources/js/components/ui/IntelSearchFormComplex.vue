<script setup lang="ts">
defineProps<{
    error?: string | null;
}>();
</script>

<template>
    <div
        class="mt-3 grid min-w-0 grid-cols-1 items-end gap-3 sm:flex sm:flex-wrap"
    >
        <slot />
    </div>

    <p v-if="error" class="mt-3 break-words text-sm text-destructive">
        {{ error }}
    </p>
</template>
