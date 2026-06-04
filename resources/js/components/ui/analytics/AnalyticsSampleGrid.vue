<script setup lang="ts">
import SectionCard from '@/components/ui/SectionCard.vue';

defineProps<{
    title: string;
    items: Array<{
        label: string;
        value: string | number;
    }>;
    gridClass?: string;
}>();
</script>

<template>
    <SectionCard :title="title">
        <div class="grid gap-2" :class="gridClass ?? 'md:grid-cols-2 xl:grid-cols-4'">
            <div
                v-for="item in items"
                :key="item.label"
                class="rounded-md border border-border/70 bg-background/70 p-3 text-xs"
            >
                <div class="text-muted-foreground">
                    {{ item.label }}
                </div>
                <div class="mt-1 font-semibold text-foreground">
                    {{ item.value }}
                </div>
            </div>
        </div>
    </SectionCard>
</template>
