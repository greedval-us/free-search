<script setup lang="ts">
import { ChevronDown, SlidersHorizontal } from 'lucide-vue-next';
import { useTemplateRef } from 'vue';

withDefaults(
    defineProps<{
        title: string;
        summary?: string;
        defaultOpen?: boolean;
    }>(),
    { summary: '', defaultOpen: false }
);
const open = defineModel<boolean>('open');
const details = useTemplateRef<HTMLDetailsElement>('details');
const revealInvalidField = () => {
    open.value = true;
    // Reveal synchronously so native form validation can focus a hidden field.
    if (details.value) {
        details.value.open = true;
    }
};
</script>

<template>
    <details
        ref="details"
        class="intel-settings-disclosure group/settings"
        :open="open ?? defaultOpen"
        @toggle="open = ($event.target as HTMLDetailsElement).open"
        @invalid.capture="revealInvalidField"
    >
        <summary
            class="intel-settings-summary"
            :aria-expanded="open ?? defaultOpen"
        >
            <SlidersHorizontal
                class="size-4 shrink-0 text-muted-foreground"
                aria-hidden="true"
            />
            <span class="min-w-0 flex-1 sm:flex sm:items-baseline sm:gap-3">
                <span class="block text-sm font-medium sm:shrink-0">{{
                    title
                }}</span>
                <span
                    v-if="summary"
                    class="block min-w-0 truncate text-xs text-muted-foreground"
                    :title="summary"
                    >{{ summary }}</span
                >
            </span>
            <ChevronDown
                class="ml-auto size-4 shrink-0 text-muted-foreground transition-transform group-open/settings:rotate-180 motion-reduce:transition-none"
                aria-hidden="true"
            />
        </summary>
        <div class="border-t border-border/60 p-3 sm:p-4">
            <slot />
        </div>
    </details>
</template>
