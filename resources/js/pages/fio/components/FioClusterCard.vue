<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useFioLabels } from '../composables/useFioLabels';
import type { FioCluster } from '../types';

const props = defineProps<{
    clusters: FioCluster[];
    label: string;
    type: 'region' | 'age';
}>();

const { t } = useI18n();
const { ageBucketLabel, regionLabel } = useFioLabels();

const colorClass = computed(() =>
    props.type === 'region' ? 'bg-primary' : 'bg-emerald-500'
);

const formatClusterLabel = (key: string) =>
    props.type === 'region' ? regionLabel(key) : ageBucketLabel(key);
</script>

<template>
    <div class="intel-section">
        <p class="mb-2 font-semibold">{{ label }}</p>
        <div v-if="props.clusters.length === 0" class="text-muted-foreground">
            {{ t('fio.lookup.noClusters') }}
        </div>
        <div v-else class="space-y-2">
            <div
                v-for="cluster in props.clusters"
                :key="`${props.type}-${cluster.key}`"
            >
                <div class="mb-1 flex items-center justify-between gap-3">
                    <span>{{ formatClusterLabel(cluster.key) }}</span>
                    <span class="text-muted-foreground"
                        >{{ cluster.count }} ({{ cluster.percent }}%)</span
                    >
                </div>
                <div class="h-2 rounded bg-muted">
                    <div
                        class="h-2 rounded"
                        :class="colorClass"
                        :style="{ width: `${cluster.percent}%` }"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
