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
        class="flex min-h-[50vh] flex-col items-center justify-center rounded-2xl border border-dashed border-sidebar-border/80 bg-card/75 p-8 text-center shadow-[0_24px_80px_-42px_rgba(15,23,42,0.5)] backdrop-blur"
    >
        <BarChart3 class="mb-4 h-14 w-14 text-muted-foreground" />
        <h3 class="text-lg font-semibold">{{ title }}</h3>
        <p class="mt-2 max-w-2xl text-sm text-muted-foreground">
            {{ description }}
        </p>
        <button
            type="button"
            class="intel-button-primary mt-5"
            :disabled="loading || disabled"
            @click="$emit('action')"
        >
            <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
            {{ loading ? loadingLabel : actionLabel }}
        </button>
    </div>
</template>
