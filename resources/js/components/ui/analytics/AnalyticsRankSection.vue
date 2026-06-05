<script setup lang="ts">
import SectionCard from '@/components/ui/SectionCard.vue';

defineProps<{
    title: string;
    emptyText: string;
    items: Array<{
        key: string;
        title: string;
        subtitle?: string;
        caption?: string;
        value: string | number;
        link?: string | null;
        linkLabel?: string;
    }>;
}>();
</script>

<template>
    <SectionCard :title="title">
        <div v-if="items.length > 0" class="space-y-2">
            <div
                v-for="item in items"
                :key="item.key"
                class="rounded-md border border-border/70 bg-background/70 p-3 text-xs"
            >
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="truncate font-medium text-foreground">
                            {{ item.title }}
                        </div>
                        <div
                            v-if="item.subtitle"
                            class="truncate text-muted-foreground"
                        >
                            {{ item.subtitle }}
                        </div>
                        <div
                            v-if="item.caption"
                            class="mt-1 text-muted-foreground"
                        >
                            {{ item.caption }}
                        </div>
                        <a
                            v-if="item.link && item.linkLabel"
                            :href="item.link"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-1 inline-flex items-center gap-1 text-primary hover:underline"
                        >
                            {{ item.linkLabel }}
                        </a>
                    </div>
                    <div class="text-right">
                        <div class="font-semibold text-foreground">
                            {{ item.value }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <p v-else class="text-xs text-muted-foreground">
            {{ emptyText }}
        </p>
    </SectionCard>
</template>
