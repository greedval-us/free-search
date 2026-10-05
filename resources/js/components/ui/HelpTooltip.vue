<script setup lang="ts">
import { ref } from 'vue';
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
const open = ref(false);
</script>

<template>
    <TooltipProvider :delay-duration="100">
        <Tooltip v-model:open="open" disable-closing-trigger>
            <TooltipTrigger as-child>
                <button
                    type="button"
                    class="inline-flex size-10 shrink-0 cursor-help items-center justify-center rounded-full border border-border text-sm font-semibold text-muted-foreground transition-colors hover:border-primary/40 hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none sm:size-8"
                    :aria-label="label"
                    @click="open = !open"
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
