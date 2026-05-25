<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import type { FioLookupResult } from '../types';
import FioClusterCard from './FioClusterCard.vue';
import FioMatchesList from './FioMatchesList.vue';
import FioSourceDiagnostics from './FioSourceDiagnostics.vue';
import FioSummaryGrid from './FioSummaryGrid.vue';

defineProps<{
    result: FioLookupResult;
}>();

const { t } = useI18n();
</script>

<template>
    <div class="intel-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
        <FioSummaryGrid :result="result" />
        <FioSourceDiagnostics :result="result" />

        <div class="grid gap-3 xl:grid-cols-2">
            <FioClusterCard
                :label="t('fio.lookup.regionClusters')"
                :clusters="result.clusters.regions"
                type="region"
            />
            <FioClusterCard
                :label="t('fio.lookup.ageClusters')"
                :clusters="result.clusters.ages"
                type="age"
            />
        </div>

        <FioMatchesList :matches="result.matches" />
    </div>
</template>
