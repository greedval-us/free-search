<script setup lang="ts">
import { Bell, Clock3 } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import {
    resolveNotificationBody,
    resolveNotificationTitle,
} from '@/lib/notifications';
import { cn } from '@/lib/utils';
import type { AppNotification } from '@/types';

const props = defineProps<{
    notification: AppNotification;
    compact?: boolean;
}>();

const { t, locale } = useI18n();
const title = computed(() => resolveNotificationTitle(props.notification, t));
const body = computed(() => resolveNotificationBody(props.notification, t));
const isUnread = computed(() => !props.notification.read_at);

const formatNotificationDate = (value: string | null) => {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat(locale.value, {
        dateStyle: props.compact ? 'short' : 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const formattedDate = computed(() =>
    formatNotificationDate(props.notification.created_at)
);

const cardClass = computed(() =>
    cn(
        'rounded-2xl border text-left transition-all duration-500 ease-out',
        props.compact ? 'block p-3' : 'bg-background/45 p-5 shadow-lg',
        props.notification.read_at
            ? 'border-slate-200/70 bg-white/70 hover:border-cyan-200 hover:bg-cyan-50/60 dark:border-slate-800 dark:bg-slate-900/65 dark:hover:border-cyan-800 dark:hover:bg-slate-900'
            : 'border-cyan-200/80 bg-cyan-50/80 shadow-[0_12px_30px_-24px_rgba(8,145,178,0.8)] hover:border-cyan-300 dark:border-cyan-900/60 dark:bg-cyan-950/20'
    )
);
</script>

<template>
    <div :class="cardClass">
        <div
            :class="
                compact
                    ? 'mb-1 flex items-start justify-between gap-3'
                    : 'flex flex-wrap items-start justify-between gap-3'
            "
        >
            <div :class="compact ? '' : 'min-w-0 flex-1 space-y-2'">
                <div class="flex items-center gap-2">
                    <div
                        v-if="!compact"
                        class="rounded-full border border-sidebar-border/70 bg-background/70 p-2"
                    >
                        <Bell class="h-4 w-4 text-cyan-300" />
                    </div>

                    <div class="flex min-w-0 items-center gap-2">
                        <p
                            :class="
                                compact
                                    ? 'text-sm font-medium text-foreground'
                                    : 'truncate text-base font-semibold'
                            "
                        >
                            {{ title }}
                        </p>
                        <span
                            v-if="isUnread"
                            class="inline-flex h-2 w-2 shrink-0 rounded-full bg-cyan-500 transition-all duration-300 ease-out"
                            :class="
                                compact ? 'mt-1' : 'h-2.5 w-2.5 bg-cyan-400'
                            "
                        />
                    </div>
                </div>

                <p v-if="body" class="text-sm leading-6 text-muted-foreground">
                    {{ body }}
                </p>
            </div>

            <p
                v-if="formattedDate"
                :class="
                    compact
                        ? 'mt-2 text-xs text-slate-500'
                        : 'flex items-center gap-2 text-xs text-muted-foreground'
                "
            >
                <Clock3 v-if="!compact" class="h-3.5 w-3.5" />
                {{ formattedDate }}
            </p>
        </div>

        <slot />
    </div>
</template>
