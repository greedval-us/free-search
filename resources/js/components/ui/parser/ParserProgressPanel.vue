<script setup lang="ts">
withDefaults(
    defineProps<{
        title: string;
        stageLabel: string;
        progress: number;
        stats: Array<{ label: string; value: number | string }>;
        statsGridClass?: string;
    }>(),
    {
        statsGridClass: 'md:grid-cols-2',
    }
);
</script>

<template>
    <section class="intel-panel-strong">
        <div class="flex min-w-0 flex-wrap items-center justify-between gap-2">
            <h3 class="min-w-0 break-words text-base font-semibold">
                {{ title }}
            </h3>
            <span
                class="max-w-full break-words rounded-full border border-border px-2.5 py-1 text-xs text-muted-foreground"
            >
                {{ stageLabel }}
            </span>
        </div>

        <div class="mt-3 h-3 overflow-hidden rounded-full bg-muted">
            <div
                class="h-full bg-primary transition-[width]"
                :style="{ width: `${Math.max(0, Math.min(100, progress))}%` }"
            />
        </div>

        <div class="mt-3 grid gap-3" :class="statsGridClass">
            <article
                v-for="stat in stats"
                :key="stat.label"
                class="rounded-lg border border-border/70 bg-background/70 p-3"
            >
                <p class="text-xs text-muted-foreground">{{ stat.label }}</p>
                <p class="mt-1 text-xl font-semibold">{{ stat.value }}</p>
            </article>
        </div>
    </section>
</template>
