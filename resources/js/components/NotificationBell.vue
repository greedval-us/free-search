<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useI18n } from '@/composables/useI18n';
import { cn } from '@/lib/utils';
import type { AppNotification } from '@/types';

type Props = {
    buttonClass?: string;
    menuClass?: string;
};

const props = withDefaults(defineProps<Props>(), {
    buttonClass: 'group relative h-9 w-9 cursor-pointer',
    menuClass: 'w-[22rem] rounded-3xl p-2',
});

const page = usePage();
const { t } = useI18n();
const notifications = computed(() => page.props.auth.notifications);
const hasUnreadNotifications = computed(
    () => (notifications.value?.unreadCount ?? 0) > 0,
);
const notificationItems = computed(() => notifications.value?.items ?? []);

const markAllNotificationsRead = () => {
    if (!hasUnreadNotifications.value) {
        return;
    }

    router.post(
        '/notifications/read-all',
        {},
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

const unreadNotificationsLabel = computed(() =>
    t('navigation.notificationsUnread').replace(
        '{count}',
        String(notifications.value?.unreadCount ?? 0),
    ),
);

const formatNotificationDate = (value: string | null) => {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date(value));
};

const notificationCardStyles = (notification: AppNotification) =>
    cn(
        'block rounded-2xl border p-3 text-left transition',
        notification.read_at
            ? 'border-slate-200/70 bg-white/70 hover:border-cyan-200 hover:bg-cyan-50/60 dark:border-slate-800 dark:bg-slate-900/65 dark:hover:border-cyan-800 dark:hover:bg-slate-900'
            : 'border-cyan-200/80 bg-cyan-50/80 shadow-[0_12px_30px_-24px_rgba(8,145,178,0.8)] hover:border-cyan-300 dark:border-cyan-900/60 dark:bg-cyan-950/20',
    );
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger :as-child="true">
            <Button variant="ghost" size="icon" :class="buttonClass">
                <Bell class="size-5 opacity-80 group-hover:opacity-100" />
                <span
                    v-if="hasUnreadNotifications"
                    class="absolute top-1.5 right-1.5 inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400 ring-2 ring-background"
                />
                <span class="sr-only">{{ t('navigation.notifications') }}</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" :class="menuClass">
            <div class="flex items-center justify-between gap-4 px-3 py-2">
                <div>
                    <p class="text-sm font-semibold text-foreground">
                        {{ t('navigation.notifications') }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{
                            hasUnreadNotifications
                                ? unreadNotificationsLabel
                                : t('navigation.notificationsEmpty')
                        }}
                    </p>
                </div>

                <Button
                    v-if="hasUnreadNotifications"
                    variant="ghost"
                    size="sm"
                    class="h-8 rounded-full px-3 text-xs"
                    @click="markAllNotificationsRead"
                >
                    {{ t('navigation.markAllNotificationsRead') }}
                </Button>
            </div>

            <div class="max-h-[26rem] space-y-2 overflow-y-auto p-2">
                <div
                    v-if="notificationItems.length === 0"
                    class="rounded-2xl border border-dashed border-slate-300/70 bg-slate-50/80 p-4 text-sm text-muted-foreground dark:border-slate-800 dark:bg-slate-950/40"
                >
                    {{ t('navigation.notificationsPlaceholder') }}
                </div>

                <template
                    v-for="notification in notificationItems"
                    :key="notification.id"
                >
                    <component
                        :is="notification.url ? Link : 'div'"
                        v-bind="notification.url ? { href: notification.url } : {}"
                        :class="notificationCardStyles(notification)"
                    >
                        <div class="mb-1 flex items-start justify-between gap-3">
                            <p class="text-sm font-medium text-foreground">
                                {{ notification.title }}
                            </p>
                            <span
                                v-if="!notification.read_at"
                                class="mt-1 inline-flex h-2 w-2 shrink-0 rounded-full bg-cyan-500"
                            />
                        </div>
                        <p
                            v-if="notification.body"
                            class="text-sm leading-6 text-muted-foreground"
                        >
                            {{ notification.body }}
                        </p>
                        <p
                            v-if="formatNotificationDate(notification.created_at)"
                            class="mt-2 text-xs text-slate-500"
                        >
                            {{ formatNotificationDate(notification.created_at) }}
                        </p>
                    </component>
                </template>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
