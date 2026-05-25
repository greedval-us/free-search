<script setup lang="ts">
import { computed, ref } from 'vue';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import SectionCard from '@/components/ui/SectionCard.vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps<{
    timeline: Array<{
        key: string;
        views: number;
        likes: number;
        comments: number;
    }>;
    fmt: (value: number) => string;
}>();

const { t } = useI18n();

const chartWidth = 920;
const chartHeight = 260;
const padding = { top: 20, right: 16, bottom: 40, left: 24 };
const innerWidth = chartWidth - padding.left - padding.right;
const innerHeight = chartHeight - padding.top - padding.bottom;

const series = computed(() => [
    {
        key: 'views',
        label: t('youtube.analytics.metrics.views'),
        color: '#38bdf8',
        values: props.timeline.map((b) => b.views),
    },
    {
        key: 'likes',
        label: t('youtube.analytics.metrics.likes'),
        color: '#22c55e',
        values: props.timeline.map((b) => b.likes),
    },
    {
        key: 'comments',
        label: t('youtube.analytics.metrics.comments'),
        color: '#f97316',
        values: props.timeline.map((b) => b.comments),
    },
]);

const chartMax = computed(() =>
    Math.max(1, ...series.value.flatMap((item) => item.values))
);
const hoveredIndex = ref<number | null>(null);

const xForIndex = (index: number) =>
    padding.left +
    (props.timeline.length > 1
        ? (innerWidth / (props.timeline.length - 1)) * index
        : 0);
const yForValue = (value: number) =>
    padding.top +
    innerHeight -
    (value / Math.max(1, chartMax.value)) * innerHeight;

const linePath = (values: number[]) => {
    if (values.length === 0) {
        return '';
    }

    const step = values.length > 1 ? innerWidth / (values.length - 1) : 0;

    return values
        .map((value, index) => {
            const x = padding.left + step * index;
            const y = yForValue(value);

            return `${index === 0 ? 'M' : 'L'} ${x.toFixed(2)} ${y.toFixed(2)}`;
        })
        .join(' ');
};

const ticks = computed(() =>
    [1, 0.75, 0.5, 0.25, 0].map((mark) => ({
        y: padding.top + innerHeight * (1 - mark),
        value: Math.round(chartMax.value * mark),
    }))
);

const hoverEntries = computed(() => {
    if (hoveredIndex.value === null) {
        return [];
    }

    const idx = hoveredIndex.value;

    return series.value.map((item) => ({
        key: item.key,
        label: item.label,
        color: item.color,
        value: item.values[idx] ?? 0,
    }));
});

const hoverLabel = computed(() =>
    hoveredIndex.value === null
        ? ''
        : (props.timeline[hoveredIndex.value]?.key ?? '')
);
const hoverX = computed(() =>
    hoveredIndex.value === null ? null : xForIndex(hoveredIndex.value)
);

const hoverCardWidth = 170;
const hoverCardHeight = computed(() => 42 + hoverEntries.value.length * 16);
const hoverCardX = computed(() => {
    if (hoverX.value === null) {
        return padding.left + 8;
    }

    const minX = padding.left + 8;
    const maxX = chartWidth - padding.right - hoverCardWidth;

    return Math.max(minX, Math.min(maxX, hoverX.value + 10));
});
const hoverCardY = padding.top + 8;

const onMove = (event: MouseEvent) => {
    if (props.timeline.length === 0) {
        hoveredIndex.value = null;

        return;
    }

    const svg = event.currentTarget as SVGSVGElement;
    const rect = svg.getBoundingClientRect();
    const ratio = chartWidth / Math.max(1, rect.width);
    const x = (event.clientX - rect.left) * ratio;
    const clampedX = Math.max(
        padding.left,
        Math.min(chartWidth - padding.right, x)
    );

    if (props.timeline.length <= 1) {
        hoveredIndex.value = 0;

        return;
    }

    const step = innerWidth / (props.timeline.length - 1);
    hoveredIndex.value = Math.max(
        0,
        Math.min(
            props.timeline.length - 1,
            Math.round((clampedX - padding.left) / step)
        )
    );
};

const clearHover = () => {
    hoveredIndex.value = null;
};
</script>

<template>
    <SectionCard :title="t('youtube.analytics.timeline')">
        <template #actions>
            <HelpTooltip
                :label="t('youtube.analytics.help.label')"
                :text="t('youtube.analytics.help.timeline')"
                width-class="w-72"
                align="right"
            />
        </template>

        <div
            class="overflow-hidden rounded-lg border border-border/70 bg-background/80 p-3"
        >
            <svg
                class="h-auto w-full"
                :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                role="img"
                aria-label="Timeline chart"
                @mousemove="onMove"
                @mouseleave="clearHover"
            >
                <line
                    v-for="tick in ticks"
                    :key="`tick-${tick.value}`"
                    :x1="padding.left"
                    :y1="tick.y"
                    :x2="chartWidth - padding.right"
                    :y2="tick.y"
                    stroke="rgb(71 85 105 / 0.35)"
                    stroke-width="1"
                />
                <line
                    v-if="hoverX !== null"
                    :x1="hoverX"
                    :x2="hoverX"
                    :y1="padding.top"
                    :y2="chartHeight - padding.bottom"
                    stroke="rgb(56 189 248 / 0.35)"
                    stroke-width="1"
                />

                <path
                    v-for="item in series"
                    :key="item.key"
                    :d="linePath(item.values)"
                    fill="none"
                    :stroke="item.color"
                    stroke-width="2.2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />

                <g v-if="hoverX !== null">
                    <circle
                        v-for="entry in hoverEntries"
                        :key="`dot-${entry.key}`"
                        :cx="hoverX"
                        :cy="yForValue(entry.value)"
                        r="3.5"
                        :fill="entry.color"
                        stroke="#0f172a"
                        stroke-width="1"
                    />
                </g>

                <g v-for="(row, idx) in props.timeline" :key="`x-${row.key}`">
                    <text
                        :x="xForIndex(idx)"
                        :y="chartHeight - 12"
                        text-anchor="middle"
                        fill="#94a3b8"
                        font-size="10"
                    >
                        {{ row.key.slice(5) }}
                    </text>
                </g>

                <g v-if="hoverX !== null">
                    <rect
                        :x="hoverCardX"
                        :y="hoverCardY"
                        :width="hoverCardWidth"
                        :height="hoverCardHeight"
                        rx="8"
                        fill="#0f172a"
                        fill-opacity="0.94"
                        stroke="rgb(100 116 139 / 0.45)"
                    />
                    <text
                        :x="hoverCardX + 10"
                        :y="hoverCardY + 14"
                        fill="#e2e8f0"
                        font-size="10"
                        font-weight="700"
                    >
                        {{ hoverLabel }}
                    </text>
                    <g
                        v-for="(entry, row) in hoverEntries"
                        :key="`tooltip-${entry.key}`"
                    >
                        <circle
                            :cx="hoverCardX + 11"
                            :cy="hoverCardY + 27 + row * 15"
                            r="3"
                            :fill="entry.color"
                        />
                        <text
                            :x="hoverCardX + 19"
                            :y="hoverCardY + 30 + row * 15"
                            fill="#cbd5e1"
                            font-size="10"
                        >
                            {{ entry.label }}: {{ props.fmt(entry.value) }}
                        </text>
                    </g>
                </g>
            </svg>

            <div
                class="mt-2 flex flex-wrap gap-3 text-xs text-muted-foreground"
            >
                <span
                    v-for="item in series"
                    :key="`legend-${item.key}`"
                    class="inline-flex items-center gap-1"
                >
                    <span
                        class="h-2 w-2 rounded-full"
                        :style="{ backgroundColor: item.color }"
                    />
                    {{ item.label }}
                </span>
            </div>
        </div>
    </SectionCard>
</template>
