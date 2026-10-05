<script setup lang="ts">
import { computed, useId } from 'vue';
import { useI18n } from '@/composables/useI18n';
import ParserStatusBadge from './ParserStatusBadge.vue';
import { boundedProgress, parserProgressStatus } from './presentation';

const props = withDefaults(
    defineProps<{
        title: string;
        stageLabel: string;
        stage?: string;
        progress: number;
        stats: Array<{ label: string; value: number | string }>;
        statsGridClass?: string;
        active?: boolean;
    }>(),
    {
        statsGridClass: 'md:grid-cols-2',
        active: false,
    }
);

const { t } = useI18n();
const headingId = useId();
const progress = computed(() => boundedProgress(props.progress));
const status = computed(() => parserProgressStatus(props.stage, props.active));
const percentage = computed(() => Math.round(progress.value));
</script>

<template>
    <section
        class="intel-panel-strong transition-colors duration-200 motion-reduce:transition-none"
        :class="active ? 'border-primary/40 ring-1 ring-primary/10' : ''"
        :aria-labelledby="headingId"
    >
        <div class="flex min-w-0 flex-wrap items-center justify-between gap-2">
            <h3
                :id="headingId"
                class="min-w-0 text-sm font-semibold break-words"
            >
                {{ title }}
            </h3>
            <ParserStatusBadge :status="status" />
        </div>

        <div class="mt-3 flex min-w-0 items-baseline justify-between gap-3">
            <p
                class="intel-caption min-w-0 break-words"
                role="status"
                aria-live="polite"
                aria-atomic="true"
            >
                {{ stageLabel }}
            </p>
            <span
                class="shrink-0 text-lg font-semibold tracking-tight tabular-nums"
                >{{ percentage
                }}<span class="ml-0.5 text-xs font-medium text-muted-foreground"
                    >%</span
                ></span
            >
        </div>
        <div
            class="mt-2 h-1.5 overflow-hidden rounded-full bg-muted"
            role="progressbar"
            :aria-label="title"
            :aria-valuemin="0"
            :aria-valuemax="100"
            :aria-valuenow="progress"
            :aria-valuetext="
                t('parser.progress.value', {
                    progress: percentage,
                    stage: stageLabel,
                })
            "
        >
            <div
                class="h-full rounded-full transition-[width] duration-500 ease-out motion-reduce:transition-none"
                :class="
                    status === 'failed'
                        ? 'bg-destructive'
                        : status === 'stopped'
                          ? 'bg-amber-500'
                          : status === 'completed'
                            ? 'bg-emerald-500'
                            : 'bg-primary'
                "
                :style="{ width: `${progress}%` }"
            />
        </div>

        <dl class="mt-3 grid gap-2" :class="statsGridClass">
            <div
                v-for="stat in stats"
                :key="stat.label"
                class="intel-section min-w-0"
            >
                <dt class="text-xs leading-5 break-words text-muted-foreground">
                    {{ stat.label }}
                </dt>
                <dd
                    class="mt-0.5 text-lg font-semibold tracking-tight break-words tabular-nums"
                >
                    {{ stat.value }}
                </dd>
            </div>
        </dl>
    </section>
</template>
