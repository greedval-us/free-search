<script setup lang="ts">
import { ExternalLink } from 'lucide-vue-next';

type HashtagStat = {
    label: string;
    value: number | string;
};

type HashtagHistoryPoint = {
    day: string;
    uses: number;
    accounts: number;
};

defineProps<{
    name: string;
    url?: string | null;
    openLabel: string;
    stats: HashtagStat[];
    history?: HashtagHistoryPoint[];
    usesLabel: string;
    accountsLabel: string;
}>();
</script>

<template>
    <div class="space-y-3">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-base font-semibold">#{{ name }}</h2>
            </div>
            <a
                v-if="url"
                :href="url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1 rounded-md border border-input px-2 py-1 text-xs text-primary hover:bg-accent"
            >
                <ExternalLink class="h-3 w-3" />
                {{ openLabel }}
            </a>
        </div>

        <div v-if="stats.length > 0" class="flex flex-wrap gap-2">
            <span
                v-for="stat in stats"
                :key="`${name}-${stat.label}`"
                class="intel-pill"
            >
                {{ stat.label }}: {{ stat.value }}
            </span>
        </div>

        <div
            v-if="history && history.length > 0"
            class="grid gap-2 md:grid-cols-2 xl:grid-cols-4"
        >
            <div
                v-for="point in history"
                :key="`${name}-${point.day}`"
                class="intel-stat"
            >
                <div class="font-medium text-foreground">
                    {{ point.day }}
                </div>
                <div class="mt-1 text-muted-foreground">
                    {{ usesLabel }}: {{ point.uses }}
                </div>
                <div class="text-muted-foreground">
                    {{ accountsLabel }}: {{ point.accounts }}
                </div>
            </div>
        </div>
    </div>
</template>
