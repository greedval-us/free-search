<script setup lang="ts">
import MetricCard from '@/components/ui/MetricCard.vue';
import { useI18n } from '@/composables/useI18n';
import { useFioLabels } from '../composables/useFioLabels';
import type { FioLookupResult } from '../types';

const props = defineProps<{
    result: FioLookupResult;
}>();

const { t } = useI18n();
const { formatDateTime, regionLabel } = useFioLabels();
</script>

<template>
    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
        <MetricCard
            :title="t('fio.lookup.checkedAt')"
            :value="formatDateTime(props.result.checkedAt)"
        />
        <MetricCard
            :title="t('fio.lookup.matches')"
            :value="props.result.summary.matches"
        />
        <MetricCard
            :title="t('fio.lookup.domains')"
            :value="props.result.summary.domains"
        />
        <MetricCard
            :title="t('fio.lookup.topRegion')"
            :value="regionLabel(props.result.summary.topRegion)"
        />
        <MetricCard
            :title="t('fio.lookup.medianAge')"
            :value="props.result.summary.medianAge ?? '-'"
        />
        <MetricCard
            :title="t('fio.lookup.averageConfidence')"
            :value="`${props.result.summary.averageConfidence}%`"
        />
    </div>
</template>
