<script setup lang="ts">
defineProps<{ text: string }>();
</script>

<template>
    <span class="group relative inline-flex">
        <span
            class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
            >?</span
        >
        <span
            class="pointer-events-none absolute top-6 left-0 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block"
        >
            {{ text }}
        </span>
    </span>
</template>
