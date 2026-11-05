<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';

defineProps<{ text: string }>();
const { t } = useI18n();
</script>

<template>
    <span class="group relative inline-flex">
        <span
            class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            :aria-label="t('siteIntel.help.label')"
            role="button"
            tabindex="0"
            >?</span
        >
        <span
            class="pointer-events-none absolute top-6 left-0 z-20 hidden w-[min(20rem,calc(100vw-2rem))] rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-focus-within:block group-hover:block"
            >{{ text }}</span
        >
    </span>
</template>
