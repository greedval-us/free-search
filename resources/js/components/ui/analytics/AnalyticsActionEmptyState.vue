<script setup lang="ts">
import { BarChart3, RefreshCw } from 'lucide-vue-next';

defineProps<{
    title: string;
    description: string;
    actionLabel: string;
    loadingLabel: string;
    loading?: boolean;
    disabled?: boolean;
}>();

defineEmits<{
    (event: 'action'): void;
}>();
</script>

<template>
    <div
        class="flex min-h-[50vh] flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/80 bg-card/70 p-8 text-center shadow-xl backdrop-blur"
    >
        <BarChart3 class="mb-4 h-14 w-14 text-muted-foreground" />
        <h3 class="text-lg font-semibold">{{ title }}</h3>
        <p class="mt-2 max-w-2xl text-sm text-muted-foreground">
            {{ description }}
        </p>
        <button
            type="button"
            class="mt-5 inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="loading || disabled"
            @click="$emit('action')"
        >
            <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
            {{ loading ? loadingLabel : actionLabel }}
        </button>
    </div>
</template>
