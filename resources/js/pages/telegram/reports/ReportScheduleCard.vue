<script setup lang="ts">
import { Pause, Play, Send, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { timezoneLabel } from './timezones';
import type { ReportSchedule } from './types';

const props = defineProps<{ schedule: ReportSchedule; busy: boolean }>();
defineEmits<{
    change: [schedule: ReportSchedule];
    run: [schedule: ReportSchedule];
    remove: [schedule: ReportSchedule];
}>();
const { t, locale } = useI18n();
const confirmDelete = ref(false);
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
                            ? 'telegramReports.active'
                            : 'telegramReports.paused'
                    )
                }}</span
            >
        </div>
        <p class="text-sm [overflow-wrap:anywhere] text-muted-foreground">
            {{ schedule.groups.map((group) => `@${group}`).join(', ') }}
        </p>
        <p class="text-xs text-muted-foreground">
            {{ t(`telegramReports.intervals.${schedule.interval}`) }} ·
            {{ schedule.sendTime }} · {{ timezone }}
        </p>
        <p v-if="schedule.enabled && schedule.nextRunAt" class="text-xs">
            {{ t('telegramReports.nextRun') }}:
            <time :datetime="schedule.nextRunAt">{{
                date(schedule.nextRunAt)
            }}</time>
        </p>
        <p class="text-xs text-muted-foreground">
            {{
                t(
                    schedule.sendToBot
                        ? 'telegramReports.deliveryBot'
                        : 'telegramReports.deliveryWebsite'
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
                            ? 'telegramReports.pause'
                            : 'telegramReports.resume'
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
                    t('telegramReports.runNow')
                }}
            </button>
            <button
                type="button"
                class="intel-button-secondary text-destructive"
                :disabled="busy"
                @click="confirmDelete = !confirmDelete"
            >
                <Trash2 class="size-4" aria-hidden="true" />{{
                    t('telegramReports.delete')
                }}
            </button>
        </div>
        <div
            v-if="confirmDelete"
            role="alert"
            class="space-y-2 rounded-lg border border-destructive/40 p-3"
        >
            <p class="text-sm">{{ t('telegramReports.confirmDelete') }}</p>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    class="intel-button-secondary text-destructive"
                    :disabled="busy"
                    @click="$emit('remove', schedule)"
                >
                    {{ t('telegramReports.delete') }}
                </button>
                <button
                    type="button"
                    class="intel-button-secondary"
                    :disabled="busy"
                    @click="confirmDelete = false"
                >
                    {{ t('telegramReports.cancel') }}
                </button>
            </div>
        </div>
    </article>
</template>
