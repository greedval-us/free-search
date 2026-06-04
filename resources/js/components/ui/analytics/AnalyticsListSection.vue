<script setup lang="ts">
import SectionCard from '@/components/ui/SectionCard.vue';

defineProps<{
    title: string;
    hasItems: boolean;
    emptyText: string;
    bodyClass?: string;
}>();
</script>

<template>
    <SectionCard :title="title">
        <div v-if="hasItems" :class="bodyClass ?? 'space-y-2'">
            <slot />
        </div>
        <p v-else class="text-xs text-muted-foreground">
            {{ emptyText }}
        </p>
    </SectionCard>
</template>
