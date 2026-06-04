<script setup lang="ts">
import MetricCard from '@/components/ui/MetricCard.vue';

defineProps<{
    items: Array<{
        title: string;
        value: string | number;
        helpText?: string;
    }>;
    gridClass?: string;
}>();
</script>

<template>
    <div class="grid gap-2" :class="gridClass">
        <MetricCard
            v-for="item in items"
            :key="item.title"
            :title="item.title"
            :value="item.value"
            :help-text="item.helpText"
        />
    </div>
</template>
