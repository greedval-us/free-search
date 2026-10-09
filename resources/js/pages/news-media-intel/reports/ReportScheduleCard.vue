<script setup lang="ts">
import { Pause, Play, Send, Trash2 } from 'lucide-vue-next';
import { computed, shallowRef } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { timezoneLabel } from '@/lib/report-timezones';
import type { ReportSchedule } from './types';

const props = defineProps<{ schedule: ReportSchedule; busy: boolean }>();
defineEmits<{
    change: [schedule: ReportSchedule];
    run: [schedule: ReportSchedule];
    remove: [schedule: ReportSchedule];
}>();
const { t, locale } = useI18n();
const confirmDelete = shallowRef(false);
const timezone = computed(() =>
    timezoneLabel(
        props.schedule.timezone,
        locale.value,
        t,
        props.schedule.nextRunAt
            ? new Date(props.schedule.nextRunAt)
            : new Date()
    )
);
const date = (value: string) =>
    new Date(value).toLocaleString(locale.value, {
        timeZone: props.schedule.timezone,
    });
</script>

<template>
    <article class="intel-panel-strong min-w-0 space-y-3">
        <div class="flex min-w-0 items-start justify-between gap-2">
            <h4 class="min-w-0 font-semibold [overflow-wrap:anywhere]">
                {{ schedule.name }}
            </h4>
            <span
                class="shrink-0 rounded-md border px-2 py-1 text-xs"
                :class="
                    schedule.enabled
                        ? 'border-primary/40 text-primary'
                        : 'text-muted-foreground'
                "
                >{{
                    t(
                        schedule.enabled
                            ? 'newsMediaReports.active'
                            : 'newsMediaReports.paused'
                    )
                }}</span
            >
        </div>
        <p class="text-sm [overflow-wrap:anywhere] text-muted-foreground">
            {{ schedule.queries.join(', ') }}
        </p>
        <p
            v-if="schedule.brand || schedule.competitors.length"
            class="text-xs [overflow-wrap:anywhere] text-muted-foreground"
        >
            {{
                [schedule.brand, ...schedule.competitors]
                    .filter(Boolean)
                    .join(' · ')
            }}
        </p>
        <p
            v-if="schedule.domain"
            class="text-xs [overflow-wrap:anywhere] text-muted-foreground"
        >
            {{ schedule.domain }}
        </p>
        <p class="text-xs text-muted-foreground">
            {{ t('newsMediaReports.searchPeriod') }}:
            {{
                t(
                    `newsMediaIntel.filters.${schedule.searchOptions.timeRange || 'allTime'}`
                )
            }}
        </p>
        <p class="text-xs text-muted-foreground">
            {{ t(`newsMediaReports.intervals.${schedule.interval}`) }} ·
            {{ schedule.sendTime }} · {{ timezone }}
        </p>
        <p v-if="schedule.enabled && schedule.nextRunAt" class="text-xs">
            {{ t('newsMediaReports.nextRun') }}:
            <time :datetime="schedule.nextRunAt">{{
                date(schedule.nextRunAt)
            }}</time>
        </p>
        <p class="text-xs text-muted-foreground">
            {{
                t(
                    schedule.sendToBot
                        ? 'newsMediaReports.deliveryBot'
                        : 'newsMediaReports.deliveryWebsite'
                )
            }}
        </p>
        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                class="intel-button-secondary"
                :disabled="busy"
                @click="$emit('change', schedule)"
            >
                <Pause
                    v-if="schedule.enabled"
                    class="size-4"
                    aria-hidden="true"
                />
                <Play v-else class="size-4" aria-hidden="true" />
                {{
                    t(
                        schedule.enabled
                            ? 'newsMediaReports.pause'
                            : 'newsMediaReports.resume'
                    )
                }}
            </button>
            <button
                type="button"
                class="intel-button-secondary"
                :disabled="busy"
                @click="$emit('run', schedule)"
            >
                <Send class="size-4" aria-hidden="true" />{{
                    t('newsMediaReports.runNow')
                }}
            </button>
            <button
                type="button"
                class="intel-button-secondary text-destructive"
                :disabled="busy"
                @click="confirmDelete = !confirmDelete"
            >
                <Trash2 class="size-4" aria-hidden="true" />{{
                    t('newsMediaReports.delete')
                }}
            </button>
        </div>
        <div
            v-if="confirmDelete"
            role="alert"
            class="space-y-2 rounded-lg border border-destructive/40 p-3"
        >
            <p class="text-sm">{{ t('newsMediaReports.confirmDelete') }}</p>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    class="intel-button-secondary text-destructive"
                    :disabled="busy"
                    @click="$emit('remove', schedule)"
                >
                    {{ t('newsMediaReports.delete') }}
                </button>
                <button
                    type="button"
                    class="intel-button-secondary"
                    :disabled="busy"
                    @click="confirmDelete = false"
                >
                    {{ t('newsMediaReports.cancel') }}
                </button>
            </div>
        </div>
    </article>
</template>
