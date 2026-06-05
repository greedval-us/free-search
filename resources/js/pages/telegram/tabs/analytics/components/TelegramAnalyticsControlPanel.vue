<script setup lang="ts">
import {
    BarChart3,
    ChevronDown,
    ChevronUp,
    Download,
    FileText,
    RefreshCw,
    Settings,
} from 'lucide-vue-next';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import { useI18n } from '@/composables/useI18n';
import type {
    AnalyticsPeriod,
    ScorePriority,
} from '../../../composables/useTelegramAnalytics';

defineProps<{
    periods: readonly AnalyticsPeriod[];
    priorities: readonly ScorePriority[];
    collapsed: boolean;
    chatUsername: string;
    keyword: string;
    periodDays: AnalyticsPeriod;
    dateFrom: string;
    dateTo: string;
    scorePriority: ScorePriority;
    dateLimits: {
        fromMin: string | null;
        fromMax: string | null;
        toMin: string | null;
        toMax: string | null;
    };
    loading: boolean;
    canLoadAnalytics: boolean;
    canUseReportActions: boolean;
}>();

const emit = defineEmits<{
    'update:collapsed': [value: boolean];
    'update:chatUsername': [value: string];
    'update:keyword': [value: string];
    'update:dateFrom': [value: string];
    'update:dateTo': [value: string];
    applyPreset: [value: AnalyticsPeriod];
    setPriority: [value: ScorePriority];
    load: [];
    openReport: [];
    downloadReport: [];
}>();

const { t } = useI18n();

const priorityLabel = (priority: ScorePriority) =>
    t(`telegram.analytics.priority.${priority}`);

const inputValue = (event: Event) => (event.target as HTMLInputElement).value;
</script>

<template>
    <div class="flex items-center justify-between gap-3">
        <div class="space-y-1">
            <div class="flex items-center gap-2 text-sm font-semibold">
                <BarChart3 class="h-4 w-4 text-primary" />
                <span>{{ t('telegram.analytics.title') }}</span>
                <HelpTooltip
                    :label="t('telegram.help.label')"
                    :text="t('telegram.analytics.help.overview')"
                />
            </div>
            <p class="text-xs text-muted-foreground">
                {{
                    collapsed
                        ? t('telegram.analytics.collapsed')
                        : t('telegram.analytics.subtitle')
                }}
            </p>
        </div>

        <button
            type="button"
            class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-input text-sm text-foreground transition hover:bg-accent"
            @click="emit('update:collapsed', !collapsed)"
        >
            <ChevronDown v-if="collapsed" class="h-4 w-4" />
            <ChevronUp v-else class="h-4 w-4" />
        </button>
    </div>

    <div v-if="!collapsed" class="mt-3 space-y-3">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-12">
            <label class="block min-w-0 xl:col-span-3">
                <span
                    class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >{{ t('telegram.analytics.filters.channel') }}</span
                >
                <input
                    :value="chatUsername"
                    type="text"
                    class="intel-input"
                    :placeholder="t('telegram.search.placeholderChannel')"
                    @input="emit('update:chatUsername', inputValue($event))"
                />
            </label>

            <label class="block min-w-0 xl:col-span-3">
                <span
                    class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >{{ t('telegram.search.keyword') }}</span
                >
                <input
                    :value="keyword"
                    type="text"
                    class="intel-input"
                    :placeholder="t('telegram.search.placeholderKeyword')"
                    @input="emit('update:keyword', inputValue($event))"
                />
            </label>

            <div class="min-w-0 xl:col-span-2">
                <span
                    class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >{{ t('telegram.analytics.filters.period') }}</span
                >
                <div
                    class="grid h-10 grid-cols-3 gap-1 rounded-xl border border-input bg-background p-1"
                >
                    <button
                        v-for="period in periods"
                        :key="period"
                        type="button"
                        class="cursor-pointer rounded-lg px-2 text-xs transition"
                        :class="
                            periodDays === period && !dateFrom && !dateTo
                                ? 'bg-primary/15 text-primary'
                                : 'text-foreground hover:bg-accent'
                        "
                        @click="emit('applyPreset', period)"
                    >
                        {{ period }}
                    </button>
                </div>
            </div>

            <label class="block min-w-0 xl:col-span-2">
                <span
                    class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >{{ t('telegram.analytics.filters.from') }}</span
                >
                <input
                    :value="dateFrom"
                    type="date"
                    :min="dateLimits.fromMin ?? undefined"
                    :max="dateLimits.fromMax ?? undefined"
                    class="intel-input"
                    @input="emit('update:dateFrom', inputValue($event))"
                />
            </label>

            <label class="block min-w-0 xl:col-span-2">
                <span
                    class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >{{ t('telegram.analytics.filters.to') }}</span
                >
                <input
                    :value="dateTo"
                    type="date"
                    :min="dateLimits.toMin ?? undefined"
                    :max="dateLimits.toMax ?? undefined"
                    class="intel-input"
                    @input="emit('update:dateTo', inputValue($event))"
                />
            </label>
        </div>

        <div
            class="flex flex-wrap items-end justify-between gap-3 rounded-xl border border-border/70 bg-background/60 p-2.5"
        >
            <div class="min-w-0 flex-1">
                <p
                    class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    {{ t('telegram.analytics.priority.title') }}
                </p>
                <div class="mt-1 flex flex-wrap gap-1">
                    <button
                        v-for="priority in priorities"
                        :key="priority"
                        type="button"
                        class="h-8 cursor-pointer rounded-lg border px-2.5 text-xs font-medium transition"
                        :class="
                            scorePriority === priority
                                ? 'border-primary/50 bg-primary/15 text-primary'
                                : 'border-input bg-background text-foreground hover:bg-accent'
                        "
                        @click="emit('setPriority', priority)"
                    >
                        {{ priorityLabel(priority) }}
                    </button>
                </div>
            </div>

            <div class="flex w-full flex-wrap justify-end gap-2 md:w-auto">
                <button
                    type="button"
                    class="intel-button-secondary px-3"
                    @click="emit('update:collapsed', true)"
                >
                    <Settings class="h-4 w-4" />
                    {{ t('telegram.analytics.hideSettings') }}
                </button>

                <button
                    type="button"
                    :disabled="loading || !canLoadAnalytics"
                    class="intel-button-secondary"
                    @click="emit('load')"
                >
                    <RefreshCw
                        class="h-4 w-4"
                        :class="{ 'animate-spin': loading }"
                    />
                    {{
                        loading
                            ? t('telegram.analytics.loading')
                            : t('telegram.analytics.refresh')
                    }}
                </button>

                <button
                    type="button"
                    :disabled="!canUseReportActions"
                    class="intel-button-primary"
                    @click="emit('openReport')"
                >
                    <FileText class="h-4 w-4" />
                    {{ t('telegram.analytics.report') }}
                </button>

                <button
                    type="button"
                    :disabled="!canUseReportActions"
                    class="intel-button-secondary"
                    @click="emit('downloadReport')"
                >
                    <Download class="h-4 w-4" />
                    {{ t('telegram.analytics.downloadReport') }}
                </button>
            </div>
        </div>
    </div>
</template>
