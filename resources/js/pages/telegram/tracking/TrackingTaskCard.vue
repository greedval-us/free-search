<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from '@/composables/useI18n';
import type { TrackingAction, TrackingTask } from './types';

defineProps<{ task: TrackingTask; busy: boolean; selected: boolean }>();
defineEmits<{
    change: [task: TrackingTask, action: TrackingAction];
    show: [task: TrackingTask];
}>();
const { t, locale } = useI18n();
const confirmStop = ref(false);
const date = (value: string) => new Date(value).toLocaleString(locale.value);
</script>

<template>
    <article
        class="intel-panel-strong min-w-0 space-y-4 p-4"
        :class="{ 'ring-1 ring-primary': selected }"
    >
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div class="min-w-0">
                <h3 class="font-semibold break-words">{{ task.name }}</h3>
                <p class="text-sm break-words text-muted-foreground">
                    {{ t(`telegramTracking.modes.${task.mode}`) }}:
                    {{ task.query }}
                </p>
            </div>
            <span
                class="rounded-md border px-2 py-1 text-xs"
                :class="
                    task.status === 'active'
                        ? 'border-primary/40 text-primary'
                        : 'text-muted-foreground'
                "
                >{{ t(`telegramTracking.status.${task.status}`) }}</span
            >
        </div>
        <p
            v-if="task.pause_reason"
            class="text-sm text-amber-600 dark:text-amber-400"
        >
            {{ t(`telegramTracking.reasons.${task.pause_reason}`) }}
        </p>
        <dl class="grid gap-2 text-xs text-muted-foreground sm:grid-cols-2">
            <div>
                <dt>{{ t('telegramTracking.started') }}</dt>
                <dd>{{ date(task.started_at) }}</dd>
            </div>
            <div>
                <dt>
                    {{
                        t(
                            task.purge_at
                                ? 'telegramTracking.purge'
                                : 'telegramTracking.expires'
                        )
                    }}
                </dt>
                <dd>{{ date(task.purge_at ?? task.expires_at) }}</dd>
            </div>
        </dl>
        <ul class="space-y-2 text-sm">
            <li
                v-for="source in task.sources"
                :key="source.id"
                class="rounded-lg border border-border/60 p-3"
            >
                <div class="font-medium break-words">{{ source.title }}</div>
                <div class="text-xs text-muted-foreground">
                    {{ t('telegramTracking.checked') }}:
                    {{
                        source.checked_at
                            ? date(source.checked_at)
                            : t('telegramTracking.notYet')
                    }}
                </div>
                <div
                    v-if="task.status === 'active'"
                    class="text-xs text-muted-foreground"
                >
                    {{ t('telegramTracking.next') }}:
                    {{ date(source.next_check_at) }}
                </div>
                <p
                    v-if="source.collecting && task.status === 'active'"
                    class="mt-1 text-xs text-primary"
                >
                    {{ t('telegramTracking.collecting') }}
                </p>
                <p
                    v-if="source.overdue"
                    class="mt-1 text-xs text-amber-600 dark:text-amber-400"
                >
                    {{ t('telegramTracking.overdue') }}
                </p>
                <p
                    v-if="source.error_code"
                    class="mt-1 text-xs text-destructive"
                >
                    {{ t(`telegramTracking.errors.${source.error_code}`) }}
                </p>
            </li>
        </ul>
        <div class="flex flex-wrap gap-2">
            <button
                class="intel-button-primary"
                :disabled="busy"
                @click="$emit('show', task)"
            >
                {{
                    t('telegramTracking.messages', {
                        count: task.messages_count,
                    })
                }}
            </button>
            <a
                class="intel-button-secondary"
                :href="`/telegram/tracking/${task.id}/export/xlsx?locale=${locale}`"
                >XLSX</a
            >
            <a
                class="intel-button-secondary"
                :href="`/telegram/tracking/${task.id}/export/json?locale=${locale}`"
                >JSON</a
            >
        </div>
        <div
            v-if="task.status === 'active' || task.status === 'paused'"
            class="flex flex-wrap gap-2 border-t border-border/60 pt-3"
        >
            <button
                class="intel-button-secondary"
                :disabled="busy"
                @click="
                    $emit(
                        'change',
                        task,
                        task.status === 'active' ? 'pause' : 'resume'
                    )
                "
            >
                {{
                    t(
                        task.status === 'active'
                            ? 'telegramTracking.pause'
                            : 'telegramTracking.resume'
                    )
                }}
            </button>
            <button
                v-if="task.can_renew"
                class="intel-button-secondary"
                :disabled="busy"
                @click="$emit('change', task, 'renew')"
            >
                {{ t('telegramTracking.renew') }}
            </button>
            <button
                class="intel-button-secondary"
                :disabled="busy"
                :aria-pressed="task.notify_bot"
                @click="$emit('change', task, 'preferences')"
            >
                {{
                    t(
                        task.notify_bot
                            ? 'telegramTracking.botOn'
                            : 'telegramTracking.botOff'
                    )
                }}
            </button>
            <button
                class="intel-button-secondary text-destructive"
                :disabled="busy"
                @click="confirmStop = !confirmStop"
            >
                {{ t('telegramTracking.stop') }}
            </button>
        </div>
        <div
            v-if="
                confirmStop &&
                (task.status === 'active' || task.status === 'paused')
            "
            role="alert"
            class="space-y-2 rounded-lg border border-destructive/40 p-3"
        >
            <p class="text-sm">{{ t('telegramTracking.confirmStop') }}</p>
            <button
                class="intel-button-secondary text-destructive"
                :disabled="busy"
                @click="
                    $emit('change', task, 'stop');
                    confirmStop = false;
                "
            >
                {{ t('telegramTracking.stop') }}
            </button>
        </div>
    </article>
</template>
