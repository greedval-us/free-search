<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
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
import { useI18n } from '@/composables/useI18n';
import { dashboard as dashboardRoute } from '@/routes';

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
            available_modules: ['username', 'fio', 'site-intel', 'email-intel', 'telegram'],
        }),
    }
);

const { t, locale } = useI18n();
const chartMax = Math.max(...props.dashboard.chart.map((x) => x.count), 1);
const BODY_SCROLL_LOCK_CLASS = 'dashboard-scroll-lock';

const quickActions = computed(() => {
    if (props.dashboard.modules.length > 0) {
        return props.dashboard.modules.slice(0, 4);
    }

    return [
        { key: 'username', count: 0, last_at: null, url: '/username', is_pinned: false },
        { key: 'fio', count: 0, last_at: null, url: '/fio', is_pinned: false },
        { key: 'site-intel', count: 0, last_at: null, url: '/site-intel', is_pinned: false },
        { key: 'email-intel', count: 0, last_at: null, url: '/email-intel', is_pinned: false },
    ] as ModuleCard[];
});

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

const moduleLabel = (key: string): string => {
    const translationKey = `dashboard.modules.${key}`;
    const translated = t(translationKey);

    return translated === translationKey ? t('dashboard.modules.default') : translated;
};

const formatDateTime = (value: string | null): string => {
    if (!value) {
        return t('dashboard.common.na');
    }

    return new Date(value).toLocaleString(locale.value);
};

const weekDay = (value: string): string => {
    return new Date(value).toLocaleDateString(locale.value, { weekday: 'short' });
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

const applyFilters = (): void => {
    router.get('/dashboard', filterForm.data(), {
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
    saveQueryForm.post('/dashboard/saved-queries', {
        preserveScroll: true,
        preserveState: true,
    });
};

const deleteSavedQuery = (savedQueryId: number): void => {
    router.delete(`/dashboard/saved-queries/${savedQueryId}`, {
        preserveScroll: true,
        preserveState: true,
    });
};

const togglePin = (moduleKey: string): void => {
    router.post('/dashboard/module-pins/toggle', {
        module_key: moduleKey,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
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

    <div class="mx-auto flex h-full min-h-0 w-full max-w-[1500px] flex-1 flex-col overflow-hidden rounded-xl p-3 sm:p-4">
        <div class="telegram-scroll min-h-0 flex-1 space-y-4 overflow-y-auto overflow-x-hidden pr-1 [scrollbar-gutter:stable_both-edges]">
        <section class="relative overflow-hidden rounded-2xl border border-sidebar-border/70 bg-card/75 p-4 shadow-xl backdrop-blur sm:p-5">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(56,189,248,0.14),transparent_45%),radial-gradient(circle_at_bottom_left,rgba(16,185,129,0.1),transparent_42%)]" />
            <div class="relative flex flex-wrap items-center justify-between gap-4">
                <div class="space-y-1">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">{{ t('dashboard.header.kicker') }}</p>
                    <h1 class="text-xl font-semibold sm:text-2xl">{{ t('dashboard.header.title') }}</h1>
                    <p class="text-sm text-muted-foreground">{{ t('dashboard.header.subtitle') }}</p>
                </div>
                <div class="w-full rounded-full border border-emerald-500/40 bg-emerald-500/10 px-3 py-1.5 text-xs font-medium text-emerald-300 sm:w-auto">
                    <span class="inline-flex items-center gap-1.5">
                        <Sparkles class="h-3.5 w-3.5" />
                        {{ t('dashboard.header.badge') }}
                    </span>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 xl:grid-cols-4">
            <article class="rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur transition hover:-translate-y-0.5 hover:border-primary/40">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <Search class="h-4 w-4" />
                    <span class="text-xs uppercase tracking-wide">{{ t('dashboard.summary.total') }}</span>
                </div>
                <p class="mt-3 text-2xl font-semibold sm:text-3xl">{{ dashboard.summary.total_actions }}</p>
            </article>

            <article class="rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur transition hover:-translate-y-0.5 hover:border-primary/40">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <Flame class="h-4 w-4" />
                    <span class="text-xs uppercase tracking-wide">{{ t('dashboard.summary.last7') }}</span>
                </div>
                <p class="mt-3 text-2xl font-semibold sm:text-3xl">{{ dashboard.summary.actions_last_7_days }}</p>
            </article>

            <article class="rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur transition hover:-translate-y-0.5 hover:border-primary/40">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <BarChart3 class="h-4 w-4" />
                    <span class="text-xs uppercase tracking-wide">{{ t('dashboard.summary.last30') }}</span>
                </div>
                <p class="mt-3 text-2xl font-semibold sm:text-3xl">{{ dashboard.summary.actions_last_30_days }}</p>
            </article>

            <article class="rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur transition hover:-translate-y-0.5 hover:border-primary/40">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <Compass class="h-4 w-4" />
                    <span class="text-xs uppercase tracking-wide">{{ t('dashboard.summary.activeDays') }}</span>
                </div>
                <p class="mt-3 text-2xl font-semibold sm:text-3xl">{{ dashboard.summary.active_days_last_30_days }}</p>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            <article class="rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">{{ t('dashboard.sections.quickActions') }}</h2>
                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <Link
                        v-for="action in quickActions"
                        :key="`quick-${action.key}`"
                        :href="action.url"
                        class="group flex items-center justify-between gap-3 rounded-lg border border-sidebar-border/70 bg-background/40 px-3 py-2 text-sm font-medium transition hover:border-primary/40 hover:bg-background/65"
                    >
                        <span class="truncate">{{ moduleLabel(action.key) }}</span>
                        <span class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] text-muted-foreground transition group-hover:text-foreground">
                            {{ action.count }}
                        </span>
                    </Link>
                </div>
            </article>

            <article class="rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">{{ t('dashboard.sections.insights') }}</h2>
                <div class="mt-3 space-y-2 text-sm">
                    <p>
                        <span class="text-muted-foreground">{{ t('dashboard.insights.topDay') }}:</span>
                        <span class="ml-1 font-medium">
                            {{ topDay ? `${weekDay(topDay.date)} (${topDay.count})` : t('dashboard.common.na') }}
                        </span>
                    </p>
                    <p>
                        <span class="text-muted-foreground">{{ t('dashboard.insights.lastQuery') }}:</span>
                        <span class="ml-1 font-medium break-words">
                            {{ lastQuery ? lastQuery.query_preview : t('dashboard.common.na') }}
                        </span>
                    </p>
                    <p>
                        <span class="text-muted-foreground">{{ t('dashboard.insights.favorite') }}:</span>
                        <span class="ml-1 font-medium">
                            {{ dashboard.favorite_module ? moduleLabel(dashboard.favorite_module.key) : t('dashboard.common.na') }}
                        </span>
                    </p>
                </div>
            </article>
        </section>

        <section class="grid min-h-0 gap-4 lg:grid-cols-2 2xl:grid-cols-3">
            <article class="min-h-0 rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">{{ t('dashboard.sections.favorite') }}</h2>
                </div>
                <div v-if="dashboard.favorite_module" class="mt-3 rounded-lg border border-sidebar-border/70 bg-background/40 p-3">
                    <p class="text-base font-semibold">{{ moduleLabel(dashboard.favorite_module.key) }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ t('dashboard.favorite.usedPrefix') }} {{ dashboard.favorite_module.total }} {{ t('dashboard.favorite.usedSuffix') }}
                    </p>
                </div>
                <p v-else class="mt-3 text-sm text-muted-foreground">{{ t('dashboard.favorite.empty') }}</p>

                <h3 class="mt-4 text-xs uppercase tracking-wide text-muted-foreground">{{ t('dashboard.sections.topModules') }}</h3>
                <ul class="telegram-scroll mt-2 max-h-56 space-y-2 overflow-y-auto pr-1 sm:max-h-72">
                    <li
                        v-for="module in dashboard.modules"
                        :key="`${module.key}-${module.count}`"
                        class="rounded-lg border border-sidebar-border/70 bg-background/40 p-3"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-medium">{{ moduleLabel(module.key) }}</p>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded p-1 text-muted-foreground hover:bg-background/70 hover:text-foreground"
                                    @click="togglePin(module.key)"
                                >
                                    <Pin class="h-3.5 w-3.5" :class="module.is_pinned ? 'text-cyan-300' : ''" />
                                </button>
                                <span class="rounded-full bg-primary/15 px-2 py-0.5 text-xs">{{ module.count }}</span>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ t('dashboard.modules.lastUsed') }}: {{ formatDateTime(module.last_at) }}
                        </p>
                        <Link :href="module.url" class="mt-2 inline-block text-xs text-cyan-300 hover:underline">
                            {{ t('dashboard.modules.open') }}
                        </Link>
                    </li>
                    <li v-if="dashboard.modules.length === 0" class="text-sm text-muted-foreground">{{ t('dashboard.modules.empty') }}</li>
                </ul>
            </article>

            <article class="min-h-0 rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur lg:col-span-2 2xl:col-span-1">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">{{ t('dashboard.sections.weekly') }}</h2>
                <div class="telegram-scroll mt-4 overflow-x-auto pb-1">
                    <div class="grid min-w-[420px] grid-cols-7 gap-2">
                        <div v-for="point in dashboard.chart" :key="point.date" class="flex flex-col items-center gap-2">
                            <div class="flex h-24 w-full items-end rounded-md bg-background/40 p-1">
                                <div
                                    class="w-full rounded bg-cyan-400/80 transition-all"
                                    :style="{ height: `${Math.max((point.count / chartMax) * 100, point.count > 0 ? 8 : 2)}%` }"
                                />
                            </div>
                            <p class="text-[11px] text-muted-foreground">{{ weekDay(point.date) }}</p>
                            <p class="text-xs font-medium">{{ point.count }}</p>
                        </div>
                    </div>
                </div>
                <p v-if="dashboard.chart.length === 0" class="mt-4 text-sm text-muted-foreground">{{ t('dashboard.weekly.empty') }}</p>
            </article>

            <article class="min-h-0 rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">{{ t('dashboard.sections.savedQueries') }}</h2>
                <ul class="telegram-scroll mt-3 max-h-64 space-y-2 overflow-y-auto pr-1 sm:max-h-[26rem]">
                    <li
                        v-for="saved in dashboard.saved_queries"
                        :key="`saved-${saved.id}`"
                        class="rounded-lg border border-sidebar-border/70 bg-background/40 p-3"
                    >
                        <p class="font-medium">{{ moduleLabel(saved.module_key) }}</p>
                        <p class="mt-1 break-words text-sm">{{ saved.query_preview }}</p>
                        <p class="mt-1 text-xs text-muted-foreground/90">{{ formatDateTime(saved.last_used_at ?? saved.created_at) }}</p>
                        <div class="mt-2 flex items-center gap-2">
                            <Link v-if="saved.run_url" :href="saved.run_url" class="text-xs text-cyan-300 hover:underline">
                                {{ t('dashboard.saved.run') }}
                            </Link>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 text-xs text-rose-300 hover:text-rose-200"
                                @click="deleteSavedQuery(saved.id)"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                                {{ t('dashboard.saved.delete') }}
                            </button>
                        </div>
                    </li>
                    <li
                        v-if="dashboard.saved_queries.length === 0"
                        class="flex items-center gap-2 rounded-lg border border-dashed border-sidebar-border/80 bg-background/30 p-3 text-sm text-muted-foreground"
                    >
                        <History class="h-4 w-4" />
                        {{ t('dashboard.saved.empty') }}
                    </li>
                </ul>
            </article>
        </section>

        <section class="min-h-0 rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">{{ t('dashboard.sections.recentQueries') }}</h2>

            <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                <select
                    v-model="filterForm.module_key"
                    class="h-9 rounded-md border border-input bg-background px-2 text-sm outline-none transition focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                >
                    <option value="">{{ t('dashboard.filters.allModules') }}</option>
                    <option v-for="module in dashboard.available_modules" :key="`filter-${module}`" :value="module">
                        {{ moduleLabel(module) }}
                    </option>
                </select>

                <input
                    v-model="filterForm.query"
                    type="text"
                    class="h-9 rounded-md border border-input bg-background px-2 text-sm outline-none transition focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                    :placeholder="t('dashboard.filters.queryPlaceholder')"
                />

                <select
                    v-model="filterForm.period"
                    class="h-9 rounded-md border border-input bg-background px-2 text-sm outline-none transition focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                >
                    <option value="7d">{{ t('dashboard.filters.period7') }}</option>
                    <option value="30d">{{ t('dashboard.filters.period30') }}</option>
                    <option value="90d">{{ t('dashboard.filters.period90') }}</option>
                </select>

                <input
                    v-model="filterForm.date_from"
                    type="date"
                    class="h-9 rounded-md border border-input bg-background px-2 text-sm outline-none transition focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                />

                <input
                    v-model="filterForm.date_to"
                    type="date"
                    class="h-9 rounded-md border border-input bg-background px-2 text-sm outline-none transition focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                />
            </div>

            <div class="mt-2 flex flex-wrap gap-2">
                <button
                    type="button"
                    class="inline-flex h-8 items-center gap-1 rounded-md border border-sidebar-border/70 bg-background/50 px-3 py-1.5 text-xs transition hover:border-primary/40 hover:bg-background/80"
                    @click="applyFilters"
                >
                    <Search class="h-3.5 w-3.5" />
                    {{ t('dashboard.filters.apply') }}
                </button>
                <button
                    type="button"
                    class="inline-flex h-8 items-center gap-1 rounded-md border border-sidebar-border/70 bg-background/50 px-3 py-1.5 text-xs transition hover:border-primary/40 hover:bg-background/80"
                    @click="resetFilters"
                >
                    <RotateCcw class="h-3.5 w-3.5" />
                    {{ t('dashboard.filters.reset') }}
                </button>
            </div>

            <ul class="telegram-scroll mt-3 max-h-[26rem] space-y-2 overflow-y-auto pr-1">
                <li
                    v-for="row in dashboard.activity_feed"
                    :key="`${row.request_log_id}-${row.module_key}-${row.at}`"
                    class="rounded-lg border border-sidebar-border/70 bg-background/40 p-3"
                >
                    <p class="font-medium">{{ moduleLabel(row.module_key) }}</p>
                    <p class="mt-1 break-words text-sm">{{ row.query_preview }}</p>
                    <p class="mt-1 text-xs text-muted-foreground/90">{{ formatDateTime(row.at) }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <Link v-if="row.run_url" :href="row.run_url" class="text-xs text-cyan-300 hover:underline">
                            {{ t('dashboard.recent.runAgain') }}
                        </Link>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                            @click="saveQuery(row.request_log_id)"
                        >
                            <BookmarkPlus class="h-3.5 w-3.5" />
                            {{ t('dashboard.recent.save') }}
                        </button>
                    </div>
                </li>
                <li v-if="dashboard.activity_feed.length === 0" class="flex items-center gap-2 rounded-lg border border-dashed border-sidebar-border/80 bg-background/30 p-3 text-sm text-muted-foreground">
                    <History class="h-4 w-4" />
                    {{ t('dashboard.recent.empty') }}
                </li>
            </ul>
        </section>
        </div>
    </div>
</template>
