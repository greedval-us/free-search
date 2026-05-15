<script setup lang="ts">
defineProps<{
    open?: boolean;
    visible?: boolean;
    withDivider?: boolean;
    contentClass?: string;
}>();
</script>

<template>
    <div
        v-if="open ?? visible ?? true"
        class="mt-3 grid gap-3 pt-3"
        :class="[withDivider === false ? '' : 'border-t border-border/60', contentClass]"
    >
        <slot />
    </div>
</template>
