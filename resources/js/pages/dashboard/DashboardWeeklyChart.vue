<script setup lang="ts">
import { computed } from 'vue';
import type { ChartRow } from './types';
import { useDashboardLabels } from './useDashboardLabels';
const props = defineProps<{ chart: ChartRow[] }>();
const { t, weekDay } = useDashboardLabels();
const chartMax = computed(() =>
    Math.max(...props.chart.map((point) => point.count), 1)
);
</script>

<template>
    <article class="intel-panel min-h-0">
        <h2 class="intel-section-heading">
            {{ t('dashboard.sections.weekly') }}
        </h2>
        <div class="intel-scroll mt-4 overflow-x-auto pb-1">
            <div class="grid grid-cols-7 gap-2 sm:gap-3">
                <div
                    v-for="point in chart"
                    :key="point.date"
                    class="flex flex-col items-center gap-2"
                >
                    <div
                        class="flex h-28 w-full items-end rounded-md bg-muted/45 p-1"
                    >
                        <div
                            class="w-full rounded bg-primary/80 motion-safe:transition-[height]"
                            :style="{
                                height: `${Math.max((point.count / chartMax) * 100, point.count > 0 ? 8 : 2)}%`,
                            }"
                        />
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ weekDay(point.date) }}
                    </p>
                    <p class="text-sm font-medium tabular-nums">
                        {{ point.count }}
                    </p>
                </div>
            </div>
        </div>
        <p v-if="chart.length === 0" class="mt-4 text-sm text-muted-foreground">
            {{ t('dashboard.weekly.empty') }}
        </p>
    </article>
</template>
