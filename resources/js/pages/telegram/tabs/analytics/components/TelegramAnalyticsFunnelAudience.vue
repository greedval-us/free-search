<script setup lang="ts">
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import { useI18n } from '@/composables/useI18n';
import type { TelegramAnalyticsSummary } from '../../../types';

type FunnelStage =
    TelegramAnalyticsSummary['summary']['funnel']['stages'][number];
type Audience = TelegramAnalyticsSummary['summary']['audience'];

defineProps<{
    funnelStages: FunnelStage[];
    audience: Audience | null;
    audienceCards: Array<{
        label: string;
        value: string | number;
    }>;
}>();

const { t } = useI18n();

const formatNumber = (value: number | null | undefined) => {
    const numeric = Number(value);

    return new Intl.NumberFormat().format(
        Number.isFinite(numeric) ? numeric : 0
    );
};

const funnelMax = (stages: FunnelStage[]) =>
    Math.max(1, ...stages.map((stage) => stage.value));

const funnelWidth = (value: number, stages: FunnelStage[]): string =>
    `${Math.max(4, (value / funnelMax(stages)) * 100)}%`;

const funnelStageLabel = (key: FunnelStage['key']): string => {
    const map: Record<FunnelStage['key'], string> = {
        messages: t('telegram.analytics.charts.funnelMessages'),
        views: t('telegram.analytics.charts.funnelViewed'),
        interactions: t('telegram.analytics.charts.funnelInteracted'),
        reactions: t('telegram.analytics.charts.funnelReacted'),
    };

    return map[key] ?? key;
};
</script>

<template>
    <div class="grid gap-4 xl:grid-cols-2">
        <article class="intel-panel-strong">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold">
                        {{ t('telegram.analytics.charts.funnel') }}
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        {{ t('telegram.analytics.charts.funnelHint') }}
                    </p>
                </div>
                <HelpTooltip
                    :label="t('telegram.analytics.help.label')"
                    :text="t('telegram.analytics.help.funnel')"
                    width-class="w-64"
                    align="right"
                />
            </div>

            <div class="mt-4 space-y-3">
                <article
                    v-for="(stage, index) in funnelStages"
                    :key="`funnel-${stage.key}`"
                    class="rounded-lg border border-border/70 bg-background/70 p-3"
                >
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-semibold">
                            {{ funnelStageLabel(stage.key) }}
                        </p>
                        <span class="text-xs font-semibold">{{
                            formatNumber(stage.value)
                        }}</span>
                    </div>
                    <div class="mt-2 h-2 rounded-full bg-muted">
                        <div
                            class="h-2 rounded-full bg-primary"
                            :style="{
                                width: funnelWidth(stage.value, funnelStages),
                            }"
                        />
                    </div>
                    <div
                        class="mt-2 flex flex-wrap gap-2 text-[11px] text-muted-foreground"
                    >
                        <span>
                            {{ t('telegram.analytics.charts.fromPrevious') }}:
                            {{ formatNumber(stage.value) }} /
                            {{
                                formatNumber(
                                    index === 0
                                        ? stage.value
                                        : (funnelStages[index - 1]?.value ?? 0)
                                )
                            }}
                            ({{ stage.conversionFromPrevious }}%)
                        </span>
                        <span>
                            {{ t('telegram.analytics.charts.fromStart') }}:
                            {{ formatNumber(stage.value) }} /
                            {{ formatNumber(funnelStages[0]?.value ?? 0) }}
                            ({{ stage.conversionFromStart }}%)
                        </span>
                    </div>
                </article>
            </div>
        </article>

        <article class="intel-panel-strong">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold">
                        {{ t('telegram.analytics.charts.audience') }}
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        {{ t('telegram.analytics.charts.audienceHint') }}
                    </p>
                </div>
                <HelpTooltip
                    :label="t('telegram.analytics.help.label')"
                    :text="t('telegram.analytics.help.audience')"
                    width-class="w-64"
                    align="right"
                />
            </div>

            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                <article
                    v-for="card in audienceCards"
                    :key="`audience-${card.label}`"
                    class="rounded-lg border border-border/70 bg-background/70 p-3"
                >
                    <p
                        class="text-[11px] tracking-wide text-muted-foreground uppercase"
                    >
                        {{ card.label }}
                    </p>
                    <p class="mt-1 text-base font-semibold">
                        {{ card.value }}
                    </p>
                </article>
            </div>

            <div
                v-if="audience?.mostActiveHours?.length"
                class="mt-4 rounded-lg border border-border/70 bg-background/70 p-3"
            >
                <p class="text-xs font-semibold">
                    {{ t('telegram.analytics.charts.mostActiveHours') }}
                </p>
                <div class="mt-2 grid gap-2 sm:grid-cols-3">
                    <article
                        v-for="hour in audience.mostActiveHours"
                        :key="`hour-${hour.hour}`"
                        class="rounded-md border border-border/70 bg-background/80 p-2 text-xs"
                    >
                        <p class="font-semibold">{{ hour.label }}</p>
                        <p class="mt-1 text-muted-foreground">
                            {{ t('telegram.analytics.stats.messages') }}:
                            {{ formatNumber(hour.messages) }}
                        </p>
                    </article>
                </div>
            </div>
        </article>
    </div>
</template>
