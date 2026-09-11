<script setup lang="ts">
import { ChevronRight } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';
import type { TrackingTask } from './types';

defineProps<{ task: TrackingTask; busy: boolean; selected: boolean }>();
defineEmits<{ show: [task: TrackingTask] }>();
const { t } = useI18n();
</script>

<template>
    <button
        :id="`tracking-task-${task.id}`"
        type="button"
        class="intel-panel-strong w-full min-w-0 cursor-pointer space-y-3 text-left transition-colors hover:border-primary/50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-wait"
        :class="{
            'border-primary/60 bg-primary/5 ring-1 ring-primary/30': selected,
        }"
        :disabled="busy"
        :aria-pressed="selected"
        @click="$emit('show', task)"
    >
        <span class="flex min-w-0 items-start justify-between gap-2">
            <span class="min-w-0 font-semibold [overflow-wrap:anywhere]">{{
                task.name
            }}</span>
            <ChevronRight
                class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                aria-hidden="true"
            />
        </span>
        <span
            class="line-clamp-2 block text-sm [overflow-wrap:anywhere] text-muted-foreground"
            >{{ task.query }}</span
        >
        <span class="flex flex-wrap items-center justify-between gap-2 text-xs">
            <span
                class="rounded-md border px-2 py-1"
                :class="
                    task.status === 'active'
                        ? 'border-primary/40 text-primary'
                        : 'text-muted-foreground'
                "
                >{{ t(`telegramTracking.status.${task.status}`) }}</span
            >
            <span class="text-muted-foreground">{{
                t('telegramTracking.messages', { count: task.messages_count })
            }}</span>
        </span>
    </button>
</template>
