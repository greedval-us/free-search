<script setup lang="ts">
import EmptyState from '@/components/ui/EmptyState.vue';

defineProps<{
    title: string;
    shownLabel: string;
    totalShown: number;
    loading: boolean;
    hasResult: boolean;
    hasMatches: boolean;
    loadingText: string;
    emptyText: string;
    noMatchesText: string;
}>();
</script>

<template>
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h2 class="min-w-0 break-words text-sm font-semibold">{{ title }}</h2>
        <p class="shrink-0 text-xs text-muted-foreground">
            {{ shownLabel }}: {{ totalShown }}
        </p>
    </div>

    <div
        class="intel-scroll min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain pr-1"
    >
        <EmptyState v-if="loading" :text="loadingText" />
        <EmptyState v-else-if="!hasResult" :text="emptyText" />
        <EmptyState v-else-if="!hasMatches" :text="noMatchesText" />
        <div v-else class="space-y-4">
            <slot />
        </div>
    </div>
</template>
