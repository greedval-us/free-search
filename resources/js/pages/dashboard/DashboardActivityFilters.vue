<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { RotateCcw, Search } from 'lucide-vue-next';
import { computed } from 'vue';
import { dashboard as dashboardRoute } from '@/routes';
import type { DashboardFilters } from './types';
import { defaultDashboardFilters } from './types';
import { useDashboardLabels } from './useDashboardLabels';
const props = defineProps<{
    filters: DashboardFilters;
    availableModules: string[];
}>();
const { t, moduleLabel } = useDashboardLabels();
const filterForm = useForm({
    module_key: props.filters.module_key,
    query: props.filters.query,
    period: props.filters.period,
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
});

const filterActions = computed(() => [
    {
        key: 'apply',
        label: t('dashboard.filters.apply'),
        icon: Search,
        action: applyFilters,
    },
    {
        key: 'reset',
        label: t('dashboard.filters.reset'),
        icon: RotateCcw,
        action: resetFilters,
    },
]);

const applyFilters = (): void => {
    router.get(dashboardRoute.url(), filterForm.data(), {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const resetFilters = (): void => {
    Object.assign(filterForm, defaultDashboardFilters());
    applyFilters();
};
</script>

<template>
    <div
        class="mt-3 grid items-end gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5"
    >
        <select
            v-model="filterForm.module_key"
            class="intel-filter-control"
            :aria-label="t('dashboard.filters.moduleLabel')"
        >
            <option value="">
                {{ t('dashboard.filters.allModules') }}
            </option>
            <option
                v-for="module in availableModules"
                :key="`filter-${module}`"
                :value="module"
            >
                {{ moduleLabel(module) }}
            </option>
        </select>

        <input
            v-model="filterForm.query"
            type="text"
            class="intel-filter-control sm:col-span-2 lg:col-span-2 xl:col-span-1"
            :aria-label="t('dashboard.filters.queryLabel')"
            :placeholder="t('dashboard.filters.queryPlaceholder')"
        />

        <select
            v-model="filterForm.period"
            class="intel-filter-control"
            :aria-label="t('dashboard.filters.periodLabel')"
        >
            <option value="7d">
                {{ t('dashboard.filters.period7') }}
            </option>
            <option value="30d">
                {{ t('dashboard.filters.period30') }}
            </option>
            <option value="90d">
                {{ t('dashboard.filters.period90') }}
            </option>
        </select>

        <label class="min-w-0"
            ><span class="intel-label">{{
                t('dashboard.filters.dateFromLabel')
            }}</span
            ><input
                v-model="filterForm.date_from"
                type="date"
                class="intel-filter-control"
        /></label>

        <label class="min-w-0"
            ><span class="intel-label">{{
                t('dashboard.filters.dateToLabel')
            }}</span
            ><input
                v-model="filterForm.date_to"
                type="date"
                class="intel-filter-control"
        /></label>
    </div>

    <div class="mt-3 flex flex-wrap gap-2">
        <button
            v-for="button in filterActions"
            :key="button.key"
            type="button"
            class="intel-button-ghost min-h-11 justify-center rounded-lg text-sm"
            @click="button.action"
        >
            <component :is="button.icon" class="h-3.5 w-3.5" />
            {{ button.label }}
        </button>
    </div>
</template>
