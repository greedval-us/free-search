<script setup lang="ts">
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

defineProps<{
    label: string;
    text: string;
    widthClass?: string;
    align?: 'left' | 'right';
}>();
</script>

<template>
    <TooltipProvider :delay-duration="100">
        <Tooltip>
            <TooltipTrigger as-child>
                <button
                    type="button"
                    class="inline-flex h-8 w-8 shrink-0 cursor-help items-center justify-center rounded-full border border-border text-xs font-semibold text-muted-foreground transition hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    :aria-label="label"
                >
                    ?
                </button>
            </TooltipTrigger>
            <TooltipContent
                side="bottom"
                :align="align === 'right' ? 'end' : 'start'"
                :collision-padding="12"
                :class="[
                    widthClass ?? 'w-64 sm:w-80',
                    'max-w-[calc(100vw-1.5rem)] text-left leading-relaxed break-words',
                ]"
            >
                {{ text }}
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
