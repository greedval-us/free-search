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
        <button
            type="button"
            class="inline-flex h-8 w-8 cursor-help items-center justify-center rounded-full border border-border text-xs font-semibold text-muted-foreground transition hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            :aria-label="label"
            :title="label"
        >
            ?
        </button>
        <span
            class="pointer-events-none absolute top-9 z-20 hidden max-w-[calc(100vw-1.5rem)] rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-focus-within:block group-hover:block"
            :class="[
                widthClass ?? 'w-64 sm:w-80',
                align === 'right' ? 'left-auto right-0' : 'left-0 right-auto',
            ]"
            role="tooltip"
        >
            {{ text }}
        </span>
    </span>
</template>
