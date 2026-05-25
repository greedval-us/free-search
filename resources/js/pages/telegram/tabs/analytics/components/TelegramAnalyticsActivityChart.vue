<script setup lang="ts">
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import { useI18n } from '@/composables/useI18n';
import type { TelegramAnalyticsSummary } from '../../../types';

type ActivitySeriesKey = 'messages' | 'views' | 'interactions';
type TimelineBucket = TelegramAnalyticsSummary['summary']['timeline'][number];
type ChartPadding = {
    top: number;
    right: number;
    bottom: number;
    left: number;
};
type ActivitySeries = {
    key: ActivitySeriesKey;
    label: string;
    color: string;
    values: number[];
};
type Dot = {
    cx: number;
    cy: number;
    value: number;
};

defineProps<{
    totalMessages: number;
    timeline: TimelineBucket[];
    chartWidth: number;
    chartHeight: number;
    padding: ChartPadding;
    chartInnerWidth: number;
    chartInnerHeight: number;
    hoveredIndex: number | null;
    hoveredBucket: TimelineBucket | null;
    hoverEntries: Array<{
        key: ActivitySeriesKey;
        label: string;
        color: string;
        value: number;
    }>;
    hoverCardWidth: number;
    hoverCardHeight: number;
    hoverCardX: number;
    hoverCardY: number;
    yTicks: Array<{
        y: number;
        value: number;
    }>;
    trendSeries: ActivitySeries[];
    displayedTrendSeries: ActivitySeries[];
    visibleSeries: Record<ActivitySeriesKey, boolean>;
    points: (values: number[]) => string;
    pointDots: (values: number[]) => Dot[];
    hoverZone: (index: number) => { x: number; width: number };
    xForIndex: (index: number) => number;
    toggleSeries: (key: ActivitySeriesKey) => void;
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
                    {{ t('telegram.analytics.charts.activity') }}
                </h3>
                <p class="text-xs text-muted-foreground">
                    {{ t('telegram.analytics.charts.activityHint') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <HelpTooltip
                    :label="t('telegram.analytics.help.label')"
                    :text="t('telegram.analytics.help.activity')"
                    width-class="w-64"
                    align="right"
                />
                <span
                    class="rounded-full border border-border px-2 py-1 text-xs text-muted-foreground"
                >
                    {{ totalMessages }}
                    {{ t('telegram.analytics.stats.messagesShort') }}
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
                :aria-label="t('telegram.analytics.charts.activity')"
            >
                <g stroke="currentColor" stroke-opacity="0.08">
                    <line
                        v-for="tick in yTicks"
                        :key="`grid-${tick.value}-${tick.y}`"
                        :x1="padding.left"
                        :y1="tick.y"
                        :x2="chartWidth - padding.right"
                        :y2="tick.y"
                    />
                    <line
                        :x1="padding.left"
                        :y1="padding.top"
                        :x2="padding.left"
                        :y2="chartHeight - padding.bottom"
                    />
                </g>

                <g
                    v-if="timeline.length > 0"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path
                        v-for="series in displayedTrendSeries"
                        :key="series.key"
                        :d="points(series.values)"
                        fill="none"
                        :stroke="series.color"
                        stroke-width="3"
                    />
                    <g
                        v-for="series in displayedTrendSeries"
                        :key="`${series.key}-dots`"
                    >
                        <circle
                            v-for="dot in pointDots(series.values)"
                            :key="`${series.key}-${dot.value}-${dot.cx}`"
                            :cx="dot.cx"
                            :cy="dot.cy"
                            r="3.5"
                            :fill="series.color"
                        />
                    </g>
                </g>

                <g v-if="timeline.length > 0">
                    <rect
                        v-for="(bucket, index) in timeline"
                        :key="`hover-zone-${bucket.key}`"
                        :x="hoverZone(index).x"
                        :y="padding.top"
                        :width="hoverZone(index).width"
                        :height="chartInnerHeight"
                        fill="transparent"
                        @mouseenter="emit('update:hoveredIndex', index)"
                        @mouseleave="emit('update:hoveredIndex', null)"
                    />
                </g>

                <g v-if="hoveredBucket && hoveredIndex !== null">
                    <line
                        :x1="xForIndex(hoveredIndex)"
                        :y1="padding.top"
                        :x2="xForIndex(hoveredIndex)"
                        :y2="chartHeight - padding.bottom"
                        stroke="#38bdf8"
                        stroke-opacity="0.55"
                        stroke-dasharray="4 4"
                    />

                    <rect
                        :x="hoverCardX"
                        :y="hoverCardY"
                        :width="hoverCardWidth"
                        :height="hoverCardHeight"
                        rx="8"
                        fill="#0f172a"
                        fill-opacity="0.94"
                    />

                    <text
                        :x="hoverCardX + 10"
                        :y="hoverCardY + 16"
                        fill="#e2e8f0"
                        class="text-[11px] font-semibold"
                    >
                        {{ hoveredBucket.label }}
                    </text>

                    <g
                        v-for="(entry, row) in hoverEntries"
                        :key="`tooltip-${entry.key}`"
                    >
                        <circle
                            :cx="hoverCardX + 12"
                            :cy="hoverCardY + 29 + row * 16"
                            r="3"
                            :fill="entry.color"
                        />
                        <text
                            :x="hoverCardX + 20"
                            :y="hoverCardY + 32 + row * 16"
                            fill="#cbd5e1"
                            class="text-[10px]"
                        >
                            {{ entry.label }}:
                            {{ formatNumber(entry.value) }}
                        </text>
                    </g>
                </g>

                <g v-if="timeline.length > 0">
                    <text
                        v-for="(bucket, index) in timeline"
                        :key="bucket.key"
                        :x="
                            padding.left +
                            (timeline.length > 1
                                ? (chartInnerWidth / (timeline.length - 1)) *
                                  index
                                : 0)
                        "
                        :y="chartHeight - 12"
                        text-anchor="middle"
                        class="fill-muted-foreground text-[11px]"
                    >
                        {{ bucket.label }}
                    </text>
                </g>

                <g>
                    <text
                        v-for="tick in yTicks"
                        :key="`tick-${tick.value}-${tick.y}`"
                        :x="chartWidth - padding.right + 2"
                        :y="tick.y + 4"
                        class="fill-muted-foreground text-[10px]"
                    >
                        {{ formatNumber(tick.value) }}
                    </text>
                </g>
            </svg>
        </div>

        <div class="mt-4 grid gap-2 md:grid-cols-3">
            <button
                v-for="series in trendSeries"
                :key="series.key"
                type="button"
                class="rounded-lg border px-3 py-2 text-left transition"
                :class="
                    visibleSeries[series.key]
                        ? 'cursor-pointer border-border/70 bg-background/80 hover:bg-accent/50'
                        : 'cursor-pointer border-border/40 bg-background/40 opacity-55 hover:opacity-80'
                "
                @click="toggleSeries(series.key)"
            >
                <p
                    class="text-[11px] tracking-wide text-muted-foreground uppercase"
                >
                    {{ series.label }}
                </p>
                <p class="mt-1 text-sm font-semibold">
                    {{
                        formatNumber(
                            series.values.reduce((acc, value) => acc + value, 0)
                        )
                    }}
                </p>
            </button>
        </div>
    </article>
</template>
