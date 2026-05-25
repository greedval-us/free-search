<script setup lang="ts">
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import { useI18n } from '@/composables/useI18n';
import type { TelegramAnalyticsSummary } from '../../../types';

type OpinionLeader =
    TelegramAnalyticsSummary['summary']['opinionLeaders'][number];
type ChartPadding = {
    top: number;
    right: number;
    bottom: number;
    left: number;
};
type LeaderSeries = {
    key: string;
    label: string;
    color: string;
    values: number[];
};
type LeaderDay = {
    dayKey: string;
    dayLabel: string;
};

defineProps<{
    leaders: OpinionLeader[];
    chartWidth: number;
    chartHeight: number;
    chartPadding: ChartPadding;
    chartInnerWidth: number;
    chartInnerHeight: number;
    hoveredIndex: number | null;
    visibleSeries: Record<string, boolean>;
    leaderX: (index: number) => number;
    dayAxis: LeaderDay[];
    series: LeaderSeries[];
    displayedSeries: LeaderSeries[];
    hoverEntries: Array<{
        key: string;
        label: string;
        color: string;
        value: number;
    }>;
    hoverCardWidth: number;
    hoverCardHeight: number;
    hoverCardX: number;
    hoverDayLabel: string;
    hoverX: number;
    points: (values: number[]) => string;
    dots: (values: number[]) => Array<{ x: number; y: number; value: number }>;
    toggleSeries: (key: string) => void;
}>();

const emit = defineEmits<{
    'update:hoveredIndex': [value: number | null];
}>();

const { t } = useI18n();

const formatNumber = (value: number | null | undefined) => {
    const numeric = Number(value);

    return new Intl.NumberFormat().format(
        Number.isFinite(numeric) ? numeric : 0
    );
};
</script>

<template>
    <article class="intel-panel-strong">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold">
                    {{ t('telegram.analytics.charts.opinionLeaders') }}
                </h3>
                <p class="text-xs text-muted-foreground">
                    {{ t('telegram.analytics.charts.opinionLeadersHint') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <HelpTooltip
                    :label="t('telegram.analytics.help.label')"
                    :text="t('telegram.analytics.help.opinionLeaders')"
                    width-class="w-64"
                    align="right"
                />
                <span
                    class="rounded-full border border-border px-2 py-1 text-xs text-muted-foreground"
                >
                    {{ leaders.length }}
                </span>
            </div>
        </div>

        <div
            class="mt-4 overflow-hidden rounded-lg border border-border/70 bg-background/80 p-3"
        >
            <svg
                viewBox="0 0 920 280"
                class="h-auto w-full"
                role="img"
                :aria-label="t('telegram.analytics.charts.opinionLeaders')"
            >
                <g stroke="currentColor" stroke-opacity="0.08">
                    <line
                        :x1="chartPadding.left"
                        :y1="chartPadding.top"
                        :x2="chartPadding.left"
                        :y2="chartHeight - chartPadding.bottom"
                    />
                    <line
                        :x1="chartPadding.left"
                        :y1="chartHeight - chartPadding.bottom"
                        :x2="chartWidth - chartPadding.right"
                        :y2="chartHeight - chartPadding.bottom"
                    />
                </g>

                <g stroke-linecap="round" stroke-linejoin="round">
                    <path
                        v-for="item in displayedSeries"
                        :key="item.key"
                        :d="points(item.values)"
                        fill="none"
                        :stroke="item.color"
                        stroke-width="3"
                    />
                    <g
                        v-for="item in displayedSeries"
                        :key="`${item.key}-dots`"
                    >
                        <circle
                            v-for="(dot, index) in dots(item.values)"
                            :key="`${item.key}-${index}`"
                            :cx="dot.x"
                            :cy="dot.y"
                            r="4"
                            :fill="item.color"
                        />
                    </g>
                </g>

                <g>
                    <rect
                        v-for="(day, index) in dayAxis"
                        :key="`leader-zone-${day.dayKey}`"
                        :x="
                            leaderX(index) -
                            (dayAxis.length > 1
                                ? chartInnerWidth / (dayAxis.length - 1) / 2
                                : chartInnerWidth / 2)
                        "
                        :y="chartPadding.top"
                        :width="
                            dayAxis.length > 1
                                ? chartInnerWidth / (dayAxis.length - 1)
                                : chartInnerWidth
                        "
                        :height="chartInnerHeight"
                        fill="transparent"
                        @mouseenter="emit('update:hoveredIndex', index)"
                        @mouseleave="emit('update:hoveredIndex', null)"
                    />
                </g>

                <g v-if="hoveredIndex !== null">
                    <line
                        :x1="hoverX"
                        :y1="chartPadding.top"
                        :x2="hoverX"
                        :y2="chartHeight - chartPadding.bottom"
                        stroke="#38bdf8"
                        stroke-opacity="0.55"
                        stroke-dasharray="4 4"
                    />
                    <rect
                        :x="hoverCardX"
                        :y="chartPadding.top + 10"
                        :width="hoverCardWidth"
                        :height="hoverCardHeight"
                        rx="8"
                        fill="#0f172a"
                        fill-opacity="0.94"
                    />
                    <text
                        :x="hoverCardX + 10"
                        :y="chartPadding.top + 26"
                        fill="#e2e8f0"
                        class="text-[11px] font-semibold"
                    >
                        {{ hoverDayLabel }}
                    </text>
                    <g
                        v-for="(entry, row) in hoverEntries"
                        :key="`leader-tooltip-${entry.key}`"
                    >
                        <circle
                            :cx="hoverCardX + 12"
                            :cy="chartPadding.top + 39 + row * 16"
                            r="3"
                            :fill="entry.color"
                        />
                        <text
                            :x="hoverCardX + 20"
                            :y="chartPadding.top + 42 + row * 16"
                            fill="#cbd5e1"
                            class="text-[10px]"
                        >
                            {{ entry.label }}:
                            {{ formatNumber(entry.value) }}
                        </text>
                    </g>
                </g>

                <g>
                    <text
                        v-for="(day, index) in dayAxis"
                        :key="`leader-label-${day.dayKey}`"
                        :x="leaderX(index)"
                        :y="chartHeight - 12"
                        text-anchor="middle"
                        class="fill-muted-foreground text-[11px]"
                    >
                        {{ day.dayLabel }}
                    </text>
                </g>
            </svg>
        </div>

        <div class="mt-4 grid gap-2 md:grid-cols-2 xl:grid-cols-3">
            <button
                v-for="item in series"
                :key="`leader-legend-${item.key}`"
                type="button"
                class="cursor-pointer rounded-md border px-3 py-2 text-left text-xs transition"
                :class="
                    visibleSeries[item.key] === false
                        ? 'border-border/70 bg-background/40 text-muted-foreground'
                        : 'border-border/70 bg-background/70 text-foreground hover:bg-accent/60'
                "
                @click="toggleSeries(item.key)"
            >
                <span class="inline-flex items-center gap-2 font-medium">
                    <span
                        class="inline-block h-2.5 w-2.5 rounded-full"
                        :style="{
                            backgroundColor: item.color,
                            opacity:
                                visibleSeries[item.key] === false ? 0.35 : 1,
                        }"
                    />
                    {{ item.label }}
                </span>
            </button>
        </div>
    </article>
</template>
