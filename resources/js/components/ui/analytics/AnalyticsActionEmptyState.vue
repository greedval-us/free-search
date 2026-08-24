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
        class="flex min-h-72 flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/80 bg-card/80 p-5 text-center sm:p-6"
        role="status"
    >
        <BarChart3 class="mb-3 h-10 w-10 text-muted-foreground" />
        <h3 class="break-words text-lg font-semibold">{{ title }}</h3>
        <p class="mt-2 max-w-2xl break-words text-sm text-muted-foreground">
            {{ description }}
        </p>
        <button
            type="button"
            class="intel-button-primary mt-4"
            :disabled="loading || disabled"
            @click="$emit('action')"
        >
            <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
            {{ loading ? loadingLabel : actionLabel }}
        </button>
    </div>
</template>
