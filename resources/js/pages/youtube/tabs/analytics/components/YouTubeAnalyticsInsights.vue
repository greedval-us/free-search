<script setup lang="ts">
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import SectionCard from '@/components/ui/SectionCard.vue';
import { useI18n } from '@/composables/useI18n';

type Insight = { key: string; value: string };

const props = defineProps<{
    insights: Insight[];
    insightLabel: (key: string) => string;
}>();

const { t } = useI18n();
</script>

<template>
    <SectionCard :title="t('youtube.analytics.insights')">
        <template #actions>
            <HelpTooltip
                :label="t('youtube.analytics.help.label')"
                :text="t('youtube.analytics.help.insights')"
                width-class="w-72"
                align="right"
            />
        </template>

        <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="insight in props.insights"
                :key="insight.key"
                class="rounded-lg border border-border/80 bg-background/70 p-3"
            >
                <p class="text-xs text-muted-foreground">
                    {{ props.insightLabel(insight.key) }}
                </p>
                <p class="mt-1 line-clamp-2 text-sm font-semibold">
                    {{ insight.value }}
                </p>
            </div>
        </div>
    </SectionCard>
</template>
