<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Sparkles } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { dashboard as dashboardRoute } from '@/routes';
import { toggle as toggleModulePin } from '@/routes/dashboard/module-pins';
import {
    destroy as destroySavedQuery,
    store as storeSavedQuery,
} from '@/routes/dashboard/saved-queries';
import DashboardActivityFilters from './dashboard/DashboardActivityFilters.vue';
import DashboardModuleActivity from './dashboard/DashboardModuleActivity.vue';
import DashboardModuleGrid from './dashboard/DashboardModuleGrid.vue';
import DashboardPlanCard from './dashboard/DashboardPlanCard.vue';
import DashboardQueryList from './dashboard/DashboardQueryList.vue';
import DashboardSummary from './dashboard/DashboardSummary.vue';
import DashboardWeeklyChart from './dashboard/DashboardWeeklyChart.vue';
import TrackingCapacityCard from './dashboard/TrackingCapacityCard.vue';
import type { DashboardPayload } from './dashboard/types';
import { emptyDashboard } from './dashboard/types';
import type { TrackingCapacity } from './telegram/tracking/types';

withDefaults(
    defineProps<{
        dashboard?: DashboardPayload;
        tracking?: TrackingCapacity;
    }>(),
    { dashboard: emptyDashboard }
);
const { t } = useI18n();
const page = usePage();
const access = computed(() => page.props.auth.access);
const BODY_SCROLL_LOCK_CLASS = 'dashboard-scroll-lock';

const saveQueryForm = useForm({
    request_log_id: 0,
});

const visitOptions = { preserveScroll: true, preserveState: true };
const saveQuery = (requestLogId: number): void => {
    saveQueryForm.request_log_id = requestLogId;
    saveQueryForm.post(storeSavedQuery.url(), visitOptions);
};
const deleteSavedQuery = (savedQueryId: number): void => {
    router.delete(destroySavedQuery.url(savedQueryId), visitOptions);
};
const togglePin = (moduleKey: string): void => {
    router.post(toggleModulePin.url(), { module_key: moduleKey }, visitOptions);
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                titleKey: 'navigation.dashboard',
                href: dashboardRoute(),
            },
        ],
    },
});

onMounted(() => {
    if (typeof document !== 'undefined') {
        document.body.classList.add(BODY_SCROLL_LOCK_CLASS);
    }
});

onBeforeUnmount(() => {
    if (typeof document !== 'undefined') {
        document.body.classList.remove(BODY_SCROLL_LOCK_CLASS);
    }
});
</script>

<template>
    <Head :title="t('dashboard.meta.title')" />

    <div
        class="mx-auto flex h-full min-h-0 w-full max-w-[1500px] flex-1 flex-col overflow-hidden p-3 sm:p-5 lg:p-6"
    >
        <div
            class="intel-scroll min-h-0 flex-1 space-y-6 overflow-y-auto pb-[env(safe-area-inset-bottom)] text-sm leading-6 [overflow-wrap:anywhere]"
        >
            <section class="py-2">
                <div
                    class="flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center"
                >
                    <div class="w-full min-w-0 flex-1 space-y-2">
                        <p class="intel-kicker">
                            {{ t('dashboard.header.kicker') }}
                        </p>
                        <h1
                            class="text-3xl leading-tight font-semibold tracking-tight sm:text-4xl"
                        >
                            {{ t('dashboard.header.title') }}
                        </h1>
                        <p
                            class="max-w-3xl text-base leading-relaxed text-muted-foreground"
                        >
                            {{ t('dashboard.header.subtitle') }}
                        </p>
                    </div>
                    <div
                        class="inline-flex max-w-full shrink-0 rounded-full border border-primary/15 bg-primary/5 px-3 py-1.5 text-sm font-medium text-primary"
                    >
                        <span class="inline-flex items-center gap-1.5">
                            <Sparkles class="size-4 shrink-0" />
                            {{ t('dashboard.header.badge') }}
                        </span>
                    </div>
                </div>
            </section>

            <DashboardModuleGrid
                :modules="dashboard.modules"
                :available-modules="dashboard.available_modules"
            />
            <DashboardSummary
                :summary="dashboard.summary"
                :chart="dashboard.chart"
                :favorite="dashboard.favorite_module"
                :last-query="dashboard.activity_feed[0]?.query_preview ?? null"
            />

            <section class="grid min-h-0 gap-4 lg:grid-cols-2 2xl:grid-cols-3">
                <DashboardModuleActivity
                    :favorite="dashboard.favorite_module"
                    :modules="dashboard.modules"
                    @toggle-pin="togglePin"
                />
                <DashboardWeeklyChart :chart="dashboard.chart" />
                <article
                    class="intel-panel min-h-0 lg:col-span-2 2xl:col-span-1"
                >
                    <h2 class="intel-section-heading">
                        {{ t('dashboard.sections.savedQueries') }}
                    </h2>
                    <DashboardQueryList
                        mode="saved"
                        :items="dashboard.saved_queries"
                        @action="deleteSavedQuery"
                    />
                </article>
            </section>

            <section class="intel-panel min-h-0">
                <h2 class="intel-section-heading">
                    {{ t('dashboard.sections.recentQueries') }}
                </h2>

                <DashboardActivityFilters
                    :filters="dashboard.filters"
                    :available-modules="dashboard.available_modules"
                />
                <DashboardQueryList
                    mode="recent"
                    :items="dashboard.activity_feed"
                    @action="saveQuery"
                />
            </section>
            <TrackingCapacityCard v-if="tracking" :capacity="tracking" />
            <DashboardPlanCard :access="access" />
        </div>
    </div>
</template>
