<script setup lang="ts">
import SectionCard from '@/components/ui/SectionCard.vue';

defineProps<{
    title: string;
    items: Array<{
        key: string;
        label: string;
        value: string | number;
    }>;
    emptyText: string;
    points: Array<{
        day: string;
        values: Array<{
            key: string;
            value: string | number;
        }>;
    }>;
}>();
</script>

<template>
    <SectionCard :title="title">
        <div v-if="points.length > 0" class="space-y-2">
            <div
                v-for="point in points"
                :key="point.day"
                class="rounded-md border border-border/70 bg-background/70 p-3 text-xs"
            >
                <div class="font-medium text-foreground">
                    {{ point.day }}
                </div>
                <div
                    class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-muted-foreground"
                >
                    <span
                        v-for="metric in items"
                        :key="`${point.day}-${metric.key}`"
                    >
                        {{ metric.label }}:
                        {{
                            point.values.find((value) => value.key === metric.key)
                                ?.value ?? '-'
                        }}
                    </span>
                </div>
            </div>
        </div>
        <p v-else class="text-xs text-muted-foreground">
            {{ emptyText }}
        </p>
    </SectionCard>
</template>
