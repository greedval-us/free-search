<script setup lang="ts">
import HelpTooltip from '@/components/ui/HelpTooltip.vue';

import type {
    ChartBar,
    Tone,
} from '../../composables/useSiteIntelAnalyticsViewModel';

const props = withDefaults(
    defineProps<{
        title: string;
        items: ChartBar[];
        valueSuffix?: string;
        hint?: string;
        helpLabel?: string;
    }>(),
    {
        valueSuffix: '',
        hint: '',
        helpLabel: 'What is this data?',
    }
);

const barToneClass = (tone: Tone) => {
    if (tone === 'emerald') {
        return 'bg-emerald-400/80';
    }

    if (tone === 'amber') {
        return 'bg-amber-400/80';
    }

    if (tone === 'rose') {
        return 'bg-rose-400/80';
    }

    if (tone === 'slate') {
        return 'bg-slate-400/80';
    }

    return 'bg-sky-400/80';
};
</script>

<template>
    <div
        class="rounded-lg border border-border/70 bg-background/60 p-3 text-xs"
    >
        <div class="mb-2 flex items-center gap-2">
            <p class="font-semibold">{{ props.title }}</p>
            <HelpTooltip
                v-if="props.hint"
                :label="props.helpLabel"
                :text="props.hint"
            />
        </div>
        <ul class="space-y-2">
            <li v-for="item in props.items" :key="item.label">
                <div
                    class="mb-1 flex items-center justify-between text-[11px] text-muted-foreground"
                >
                    <span>{{ item.label }}</span>
                    <span>{{ item.value }}{{ props.valueSuffix }}</span>
                </div>
                <div class="h-2 rounded bg-muted/50">
                    <div
                        class="h-2 rounded transition-all duration-300"
                        :class="barToneClass(item.tone)"
                        :style="{ width: `${item.percent}%` }"
                    />
                </div>
            </li>
        </ul>
    </div>
</template>
