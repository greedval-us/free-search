<script setup lang="ts">
import { computed } from 'vue';
import InfoCard from '@/components/ui/InfoCard.vue';
import { useI18n } from '@/composables/useI18n';
const props = defineProps<{
    timeline: Array<{ date: string; mentions: number }>;
    undated?: number;
}>();
const { t } = useI18n();
const points = computed(() => props.timeline.slice(-30));
const maximum = computed(() =>
    Math.max(...points.value.map((point) => point.mentions), 1)
);
</script>
<template>
    <InfoCard :title="t('newsMediaIntel.timeline.title')">
        <p class="mb-3 text-xs leading-5 text-muted-foreground">
            {{ t('newsMediaIntel.help.timeline') }}
        </p>
        <p v-if="undated" class="mb-3 text-xs text-muted-foreground">
            {{ t('newsMediaIntel.timeline.undated', { count: undated }) }}
        </p>
        <p v-if="!points.length" class="intel-empty">
            {{ t('newsMediaIntel.timeline.none') }}
        </p>
        <div v-else class="space-y-2">
            <div
                v-for="point in points"
                :key="point.date"
                class="grid grid-cols-[6rem_minmax(0,1fr)_2rem] items-center gap-2 text-xs"
            >
                <span class="text-muted-foreground">{{ point.date }}</span>
                <div class="h-2 rounded-full bg-muted">
                    <div
                        class="h-full rounded-full bg-primary/70"
                        :style="{
                            width: `${(point.mentions / maximum) * 100}%`,
                        }"
                    />
                </div>
                <span class="text-right tabular-nums">{{
                    point.mentions
                }}</span>
            </div>
        </div>
    </InfoCard>
</template>
