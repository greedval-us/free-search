<script setup lang="ts">
import { BarChart3, Compass, Flame, Search } from 'lucide-vue-next';
import { computed } from 'vue';
import KeyValueList from '@/components/ui/KeyValueList.vue';
import MetricCard from '@/components/ui/MetricCard.vue';
import type { ChartRow, FavoriteModule, Summary } from './types';
import { useDashboardLabels } from './useDashboardLabels';

const props = defineProps<{
    summary: Summary;
    chart: ChartRow[];
    favorite: FavoriteModule | null;
    lastQuery: string | null;
}>();
const { t, moduleLabel, weekDay } = useDashboardLabels();
const summaryCards = computed(() => [
    {
        key: 'total',
        icon: Search,
        title: t('dashboard.summary.total'),
        value: props.summary.total_actions,
    },
    {
        key: 'last7',
        icon: Flame,
        title: t('dashboard.summary.last7'),
        value: props.summary.actions_last_7_days,
    },
    {
        key: 'last30',
        icon: BarChart3,
        title: t('dashboard.summary.last30'),
        value: props.summary.actions_last_30_days,
    },
    {
        key: 'activeDays',
        icon: Compass,
        title: t('dashboard.summary.activeDays'),
        value: props.summary.active_days_last_30_days,
    },
]);

const topDay = computed(() => {
    if (props.chart.length === 0) {
        return null;
    }

    let current = props.chart[0];

    for (const day of props.chart) {
        if (day.count > current.count) {
            current = day;
        }
    }

    if (!current || current.count === 0) {
        return null;
    }

    return current;
});

const insightItems = computed(() => [
    {
        key: 'topDay',
        label: t('dashboard.insights.topDay'),
        value: topDay.value
            ? `${weekDay(topDay.value.date)} (${topDay.value.count})`
            : t('dashboard.common.na'),
    },
    {
        key: 'lastQuery',
        label: t('dashboard.insights.lastQuery'),
        value: props.lastQuery ?? t('dashboard.common.na'),
        valueClass: 'break-words',
    },
    {
        key: 'favorite',
        label: t('dashboard.insights.favorite'),
        value: props.favorite
            ? moduleLabel(props.favorite.key)
            : t('dashboard.common.na'),
    },
]);
</script>

<template>
    <section
        class="grid grid-cols-2 overflow-hidden rounded-xl border border-border/75 bg-card sm:grid-cols-4"
    >
        <MetricCard
            v-for="card in summaryCards"
            :key="card.key"
            :title="card.title"
            :value="card.value"
            :icon="card.icon"
            prominent
            class="rounded-none! border-0! bg-transparent! p-4! shadow-none! [&_.intel-title]:text-sm"
        />
    </section>

    <section class="border-y border-border/75 py-4">
        <article class="grid gap-3 lg:grid-cols-[10rem_minmax(0,1fr)]">
            <h2 class="intel-section-heading">
                {{ t('dashboard.sections.insights') }}
            </h2>
            <KeyValueList
                class="grid space-y-0! gap-x-6 gap-y-3 md:grid-cols-3 [&_dd]:mt-1 [&>div]:block"
                :items="insightItems"
            />
        </article>
    </section>
</template>
