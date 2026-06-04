<script setup lang="ts">
import SectionCard from '@/components/ui/SectionCard.vue';

defineProps<{
    title: string;
    emptyText: string;
    items: Array<{
        key: string;
        label: string;
        icon?: object;
    }>;
}>();
</script>

<template>
    <SectionCard :title="title">
        <div v-if="items.length > 0" class="flex flex-wrap gap-2">
            <span
                v-for="item in items"
                :key="item.key"
                class="inline-flex items-center gap-1 rounded-full border border-input px-2 py-1 text-xs"
            >
                <component v-if="item.icon" :is="item.icon" class="h-3 w-3" />
                {{ item.label }}
            </span>
        </div>
        <p v-else class="text-xs text-muted-foreground">
            {{ emptyText }}
        </p>
    </SectionCard>
</template>
