<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    BookmarkPlus,
    Compass,
    Flame,
    History,
    Pin,
    RotateCcw,
    Search,
    Sparkles,
    Trash2,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted } from 'vue';
import KeyValueList from '@/components/ui/KeyValueList.vue';
import MetricCard from '@/components/ui/MetricCard.vue';
import { useI18n } from '@/composables/useI18n';
import { dashboard as dashboardRoute } from '@/routes';
import { toggle as toggleModulePin } from '@/routes/dashboard/module-pins';
import {
    destroy as destroySavedQuery,
    store as storeSavedQuery,
} from '@/routes/dashboard/saved-queries';
import DashboardModuleGrid from './dashboard/DashboardModuleGrid.vue';
import DashboardPlanCard from './dashboard/DashboardPlanCard.vue';
import TrackingCapacityCard from './dashboard/TrackingCapacityCard.vue';
import type { TrackingCapacity } from './telegram/tracking/types';

interface Summary {
    total_actions: number;
    actions_last_7_days: number;
    actions_last_30_days: number;
    active_days_last_30_days: number;
}

interface FavoriteModule {
    key: string;
    total: number;
}

interface ModuleCard {
    key: string;
    count: number;
    last_at: string | null;
    url: string;
    is_pinned: boolean;
}

interface ActivityFeedRow {
    request_log_id: number;
    module_key: string;
    query_preview: string;
    at: string | null;
    run_url: string | null;
}

interface SavedQueryRow {
    id: number;
    module_key: string;
    query_preview: string;
    run_url: string | null;
    last_used_at: string | null;
    created_at: string | null;
}

interface ChartRow {
    date: string;
    day: string;
    count: number;
}

interface DashboardFilters {
    module_key: string;
    query: string;
    period: string;
    date_from: string;
    date_to: string;
}

interface DashboardPayload {
    summary: Summary;
    favorite_module: FavoriteModule | null;
    modules: ModuleCard[];
    activity_feed: ActivityFeedRow[];
    chart: ChartRow[];
    saved_queries: SavedQueryRow[];
    pinned_modules: string[];
    filters: DashboardFilters;
    available_modules: string[];
}

const props = withDefaults(
    defineProps<{
        dashboard?: DashboardPayload;
        tracking?: TrackingCapacity;
    }>(),
    {
        dashboard: () => ({
            summary: {
                total_actions: 0,
                actions_last_7_days: 0,
                actions_last_30_days: 0,
                active_days_last_30_days: 0,
            },
            favorite_module: null,
            modules: [],
            activity_feed: [],
            chart: [],
            saved_queries: [],
            pinned_modules: [],
            filters: {
                module_key: '',
                query: '',
                period: '30d',
                date_from: '',
                date_to: '',
            },
            available_modules: [
                'bluesky',
                'site-intel',
                'telegram',
                'youtube',
                'shifr',
            ],
        }),
    }
);

const { t, locale } = useI18n();
const page = usePage();
const access = computed(() => page.props.auth.access);
const chartMax = computed(() =>
    Math.max(...props.dashboard.chart.map((x) => x.count), 1)
);
const BODY_SCROLL_LOCK_CLASS = 'dashboard-scroll-lock';

const summaryCards = computed(() => [
    {
        key: 'total',
        icon: Search,
        title: t('dashboard.summary.total'),
        value: props.dashboard.summary.total_actions,
    },
    {
        key: 'last7',
        icon: Flame,
        title: t('dashboard.summary.last7'),
        value: props.dashboard.summary.actions_last_7_days,
    },
    {
        key: 'last30',
        icon: BarChart3,
        title: t('dashboard.summary.last30'),
        value: props.dashboard.summary.actions_last_30_days,
    },
    {
        key: 'activeDays',
        icon: Compass,
        title: t('dashboard.summary.activeDays'),
        value: props.dashboard.summary.active_days_last_30_days,
    },
]);

const topDay = computed(() => {
    if (props.dashboard.chart.length === 0) {
        return null;
    }

    let current = props.dashboard.chart[0];

    for (const day of props.dashboard.chart) {
        if (day.count > current.count) {
            current = day;
        }
    }

    if (!current || current.count === 0) {
        return null;
    }

    return current;
});

const lastQuery = computed(() => {
    return props.dashboard.activity_feed[0] ?? null;
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
        value: lastQuery.value?.query_preview ?? t('dashboard.common.na'),
        valueClass: 'break-words',
    },
    {
        key: 'favorite',
        label: t('dashboard.insights.favorite'),
        value: props.dashboard.favorite_module
            ? moduleLabel(props.dashboard.favorite_module.key)
            : t('dashboard.common.na'),
    },
]);

const moduleLabel = (key: string): string => {
    const translationKey = `dashboard.modules.${key}`;
    const translated = t(translationKey);

    return translated === translationKey
        ? t('dashboard.modules.default')
        : translated;
};

const formatDateTime = (value: string | null): string => {
    if (!value) {
        return t('dashboard.common.na');
    }

    return new Date(value).toLocaleString(locale.value);
};

const weekDay = (value: string): string => {
    return new Date(value).toLocaleDateString(locale.value, {
        weekday: 'short',
    });
};

const filterForm = useForm({
    module_key: props.dashboard.filters.module_key,
    query: props.dashboard.filters.query,
    period: props.dashboard.filters.period,
    date_from: props.dashboard.filters.date_from,
    date_to: props.dashboard.filters.date_to,
});

const saveQueryForm = useForm({
    request_log_id: 0,
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
    filterForm.module_key = '';
    filterForm.query = '';
    filterForm.period = '30d';
    filterForm.date_from = '';
    filterForm.date_to = '';
    applyFilters();
};

const saveQuery = (requestLogId: number): void => {
    saveQueryForm.request_log_id = requestLogId;
    saveQueryForm.post(storeSavedQuery.url(), {
        preserveScroll: true,
        preserveState: true,
    });
};

const deleteSavedQuery = (savedQueryId: number): void => {
    router.delete(destroySavedQuery.url(savedQueryId), {
        preserveScroll: true,
        preserveState: true,
    });
};

const togglePin = (moduleKey: string): void => {
    router.post(
        toggleModulePin.url(),
        {
            module_key: moduleKey,
        },
        {
            preserveScroll: true,
            preserveState: true,
        }
    );
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
            class="intel-scroll min-h-0 flex-1 space-y-5 overflow-y-auto pb-[env(safe-area-inset-bottom)] [overflow-wrap:anywhere]"
        >
            <section class="py-2">
                <div
                    class="flex flex-col items-start justify-between gap-3 lg:flex-row"
                >
                    <div class="w-full min-w-0 flex-1 space-y-1">
                        <p class="intel-kicker">
                            {{ t('dashboard.header.kicker') }}
                        </p>
                        <h1
                            class="text-2xl font-semibold tracking-tight sm:text-3xl"
                        >
                            {{ t('dashboard.header.title') }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{ t('dashboard.header.subtitle') }}
                        </p>
                    </div>
                    <div
                        class="inline-flex max-w-full rounded-md border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1.5 text-xs font-medium text-emerald-700 dark:text-emerald-300"
                    >
                        <span class="inline-flex items-center gap-1.5">
                            <Sparkles class="h-3.5 w-3.5" />
                            {{ t('dashboard.header.badge') }}
                        </span>
                    </div>
                </div>
            </section>

            <DashboardModuleGrid
                :modules="dashboard.modules"
                :available-modules="dashboard.available_modules"
            />

            <section class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                <MetricCard
                    v-for="card in summaryCards"
                    :key="card.key"
                    :title="card.title"
                    :value="card.value"
                    :icon="card.icon"
                    prominent
                    class="workspace-card"
                />
            </section>

            <section>
                <article class="intel-panel">
                    <h2 class="intel-section-heading">
                        {{ t('dashboard.sections.insights') }}
                    </h2>
                    <KeyValueList class="mt-3" :items="insightItems" />
                </article>
            </section>

            <section class="grid min-h-0 gap-3 lg:grid-cols-2 2xl:grid-cols-3">
                <article class="intel-panel min-h-0">
                    <div class="flex items-center justify-between">
                        <h2 class="intel-section-heading">
                            {{ t('dashboard.sections.favorite') }}
                        </h2>
                    </div>
                    <div
                        v-if="dashboard.favorite_module"
                        class="intel-list-item mt-3"
                    >
                        <p class="text-base font-semibold">
                            {{ moduleLabel(dashboard.favorite_module.key) }}
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ t('dashboard.favorite.usedPrefix') }}
                            {{ dashboard.favorite_module.total }}
                            {{ t('dashboard.favorite.usedSuffix') }}
                        </p>
                    </div>
                    <p v-else class="mt-3 text-sm text-muted-foreground">
                        {{ t('dashboard.favorite.empty') }}
                    </p>

                    <h3 class="intel-kicker mt-4">
                        {{ t('dashboard.sections.topModules') }}
                    </h3>
                    <ul
                        class="intel-scroll mt-2 space-y-2 md:max-h-72 md:overflow-y-auto md:pr-1"
                    >
                        <li
                            v-for="module in dashboard.modules"
                            :key="`${module.key}-${module.count}`"
                            class="intel-list-item"
                        >
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <p class="font-medium">
                                    {{ moduleLabel(module.key) }}
                                </p>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-muted-foreground hover:bg-background/70 hover:text-foreground"
                                        :aria-label="
                                            module.is_pinned
                                                ? t(
                                                      'dashboard.modules.unpinAria'
                                                  )
                                                : t('dashboard.modules.pinAria')
                                        "
                                        @click="togglePin(module.key)"
                                    >
                                        <Pin
                                            class="h-3.5 w-3.5"
                                            :class="
                                                module.is_pinned
                                                    ? 'text-primary'
                                                    : ''
                                            "
                                        />
                                    </button>
                                    <span class="intel-badge-count text-xs">
                                        {{ module.count }}
                                    </span>
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ t('dashboard.modules.lastUsed') }}:
                                {{ formatDateTime(module.last_at) }}
                            </p>
                            <Link
                                :href="module.url"
                                class="mt-2 inline-block text-xs text-primary hover:underline"
                            >
                                {{ t('dashboard.modules.open') }}
                            </Link>
                        </li>
                        <li
                            v-if="dashboard.modules.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            {{ t('dashboard.modules.empty') }}
                        </li>
                    </ul>
                </article>

                <article class="intel-panel min-h-0">
                    <h2 class="intel-section-heading">
                        {{ t('dashboard.sections.weekly') }}
                    </h2>
                    <div class="intel-scroll mt-4 overflow-x-auto pb-1">
                        <div class="grid grid-cols-7 gap-1 sm:gap-2">
                            <div
                                v-for="point in dashboard.chart"
                                :key="point.date"
                                class="flex flex-col items-center gap-2"
                            >
                                <div
                                    class="flex h-24 w-full items-end rounded-md bg-background/40 p-1"
                                >
                                    <div
                                        class="w-full rounded bg-cyan-400/80 transition-all"
                                        :style="{
                                            height: `${Math.max((point.count / chartMax) * 100, point.count > 0 ? 8 : 2)}%`,
                                        }"
                                    />
                                </div>
                                <p class="text-[11px] text-muted-foreground">
                                    {{ weekDay(point.date) }}
                                </p>
                                <p class="text-xs font-medium">
                                    {{ point.count }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <p
                        v-if="dashboard.chart.length === 0"
                        class="mt-4 text-sm text-muted-foreground"
                    >
                        {{ t('dashboard.weekly.empty') }}
                    </p>
                </article>

                <article class="intel-panel min-h-0">
                    <h2 class="intel-section-heading">
                        {{ t('dashboard.sections.savedQueries') }}
                    </h2>
                    <ul
                        class="intel-scroll mt-3 space-y-2 md:max-h-[26rem] md:overflow-y-auto md:pr-1"
                    >
                        <li
                            v-for="saved in dashboard.saved_queries"
                            :key="`saved-${saved.id}`"
                            class="intel-list-item"
                        >
                            <p class="font-medium">
                                {{ moduleLabel(saved.module_key) }}
                            </p>
                            <p class="mt-1 text-sm break-words">
                                {{ saved.query_preview }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground/90">
                                {{
                                    formatDateTime(
                                        saved.last_used_at ?? saved.created_at
                                    )
                                }}
                            </p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <Link
                                    v-if="saved.run_url"
                                    :href="saved.run_url"
                                    class="inline-flex min-h-11 items-center text-sm text-primary hover:underline"
                                >
                                    {{ t('dashboard.saved.run') }}
                                </Link>
                                <button
                                    type="button"
                                    class="inline-flex min-h-11 items-center gap-1.5 px-2 text-sm text-destructive hover:opacity-80"
                                    @click="deleteSavedQuery(saved.id)"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                    {{ t('dashboard.saved.delete') }}
                                </button>
                            </div>
                        </li>
                        <li
                            v-if="dashboard.saved_queries.length === 0"
                            class="intel-empty-row"
                        >
                            <History class="h-4 w-4" />
                            {{ t('dashboard.saved.empty') }}
                        </li>
                    </ul>
                </article>
            </section>

            <section class="intel-panel min-h-0">
                <h2 class="intel-section-heading">
                    {{ t('dashboard.sections.recentQueries') }}
                </h2>

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
                            v-for="module in dashboard.available_modules"
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

                <div class="mt-2 flex flex-wrap gap-2">
                    <button
                        v-for="button in filterActions"
                        :key="button.key"
                        type="button"
                        class="intel-button-ghost min-h-11 justify-center rounded-xl"
                        @click="button.action"
                    >
                        <component :is="button.icon" class="h-3.5 w-3.5" />
                        {{ button.label }}
                    </button>
                </div>

                <ul
                    class="intel-scroll mt-3 space-y-2 md:max-h-[26rem] md:overflow-y-auto md:pr-1"
                >
                    <li
                        v-for="row in dashboard.activity_feed"
                        :key="`${row.request_log_id}-${row.module_key}-${row.at}`"
                        class="intel-list-item"
                    >
                        <p class="font-medium">
                            {{ moduleLabel(row.module_key) }}
                        </p>
                        <p class="mt-1 text-sm break-words">
                            {{ row.query_preview }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground/90">
                            {{ formatDateTime(row.at) }}
                        </p>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <Link
                                v-if="row.run_url"
                                :href="row.run_url"
                                class="inline-flex min-h-11 items-center text-sm text-primary hover:underline"
                            >
                                {{ t('dashboard.recent.runAgain') }}
                            </Link>
                            <button
                                type="button"
                                class="inline-flex min-h-11 items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                                @click="saveQuery(row.request_log_id)"
                            >
                                <BookmarkPlus class="h-3.5 w-3.5" />
                                {{ t('dashboard.recent.save') }}
                            </button>
                        </div>
                    </li>
                    <li
                        v-if="dashboard.activity_feed.length === 0"
                        class="intel-empty-row"
                    >
                        <History class="h-4 w-4" />
                        {{ t('dashboard.recent.empty') }}
                    </li>
                </ul>
            </section>
            <TrackingCapacityCard v-if="tracking" :capacity="tracking" />
            <DashboardPlanCard :access="access" />
        </div>
    </div>
</template>
