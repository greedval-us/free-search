<script setup lang="ts">
defineProps<{
    label: string;
    text: string;
    widthClass?: string;
    align?: 'left' | 'right';
}>();
</script>

<template>
    <span class="group relative inline-flex">
        <span
            class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
            :aria-label="label"
        >
            ?
        </span>
        <span
            class="pointer-events-none absolute left-0 top-6 z-20 hidden rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block"
            :class="[
                widthClass ?? 'w-80',
                align === 'right' ? 'left-auto right-0' : 'left-0 right-auto',
            ]"
        >
            {{ text }}
        </span>
    </span>
</template>
