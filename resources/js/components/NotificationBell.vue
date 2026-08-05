<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import { computed } from 'vue';
import NotificationListItem from '@/components/NotificationListItem.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useI18n } from '@/composables/useI18n';
import { useNotifications } from '@/composables/useNotifications';

type Props = {
    buttonClass?: string;
    menuClass?: string;
};

withDefaults(defineProps<Props>(), {
    buttonClass: 'group relative h-9 w-9 cursor-pointer',
    menuClass: 'w-[22rem] rounded-3xl p-2',
});

const page = usePage();
const { t } = useI18n();
const notifications = computed(() => page.props.auth.notifications);
const {
    items: notificationItems,
    unreadCount: unreadNotificationsCount,
    hasUnread: hasUnreadNotifications,
    markAllRead: markAllNotificationsRead,
    markRead: markNotificationRead,
} = useNotifications({
    notifications,
});

const unreadNotificationsLabel = computed(() =>
    t('navigation.notificationsUnread').replace(
        '{count}',
        String(unreadNotificationsCount.value)
    )
);

const unreadNotificationItems = computed(() =>
    notificationItems.value.filter(
        (notification) => notification.read_at === null
    )
);
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger :as-child="true">
            <Button variant="ghost" size="icon" :class="buttonClass">
                <Bell class="size-5 opacity-80 group-hover:opacity-100" />
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="translate-y-0.5 scale-75 opacity-0"
                    enter-to-class="translate-y-0 scale-100 opacity-100"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="translate-y-0 scale-100 opacity-100"
                    leave-to-class="-translate-y-0.5 scale-75 opacity-0"
                >
                    <span
                        v-if="hasUnreadNotifications"
                        class="absolute top-1.5 right-1.5 inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400 ring-2 ring-background"
                    />
                </Transition>
                <span class="sr-only">{{ t('navigation.notifications') }}</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" :class="menuClass">
            <div class="flex items-center justify-between gap-4 px-3 py-2">
                <div>
                    <p class="text-sm font-semibold text-foreground">
                        {{ t('navigation.notifications') }}
                    </p>
                    <Transition
                        mode="out-in"
                        enter-active-class="transition-all duration-250 ease-out"
                        enter-from-class="translate-y-1 opacity-0"
                        enter-to-class="translate-y-0 opacity-100"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="translate-y-0 opacity-100"
                        leave-to-class="-translate-y-1 opacity-0"
                    >
                        <p
                            :key="
                                hasUnreadNotifications
                                    ? `unread-${unreadNotificationsCount}`
                                    : 'empty'
                            "
                            class="text-xs text-muted-foreground"
                        >
                            {{
                                hasUnreadNotifications
                                    ? unreadNotificationsLabel
                                    : t('navigation.notificationsEmpty')
                            }}
                        </p>
                    </Transition>
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
                    v-if="unreadNotificationItems.length === 0"
                    class="rounded-2xl border border-dashed border-slate-300/70 bg-slate-50/80 p-4 text-sm text-muted-foreground dark:border-slate-800 dark:bg-slate-950/40"
                >
                    {{ t('navigation.notificationsPlaceholder') }}
                </div>

                <template
                    v-for="notification in unreadNotificationItems"
                    :key="notification.id"
                >
                    <NotificationListItem
                        v-if="notification.url"
                        :notification="notification"
                        compact
                        @click="
                            markNotificationRead(notification, notification.url)
                        "
                    />

                    <NotificationListItem
                        v-else
                        :notification="notification"
                        compact
                        @click="markNotificationRead(notification)"
                    />
                </template>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
