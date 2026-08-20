<script setup lang="ts">
import { computed } from 'vue';

type RiskLevel = 'unknown' | 'low' | 'medium' | 'high';

const props = defineProps<{
    label: string;
    level: RiskLevel;
    score?: number | null;
}>();

const badgeClass = computed(() => {
    if (props.level === 'low') {
        return 'border-emerald-500/40 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (props.level === 'medium') {
        return 'border-amber-500/40 bg-amber-500/10 text-amber-700 dark:text-amber-300';
    }

    if (props.level === 'high') {
        return 'border-rose-500/40 bg-rose-500/10 text-rose-700 dark:text-rose-300';
    }

    return 'border-slate-500/40 bg-slate-500/10 text-slate-700 dark:text-slate-300';
});
</script>

<template>
    <div class="min-w-0 rounded-lg border p-3" :class="badgeClass">
        <p class="text-xs">{{ label }}</p>
        <p class="mt-1 break-words text-sm font-semibold">
            <slot />
            <span v-if="score !== undefined && score !== null"> ({{ score }}/100)</span>
        </p>
    </div>
</template>
