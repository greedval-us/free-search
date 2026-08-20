<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { BellOff } from 'lucide-vue-next';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import NotificationListItem from '@/components/NotificationListItem.vue';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/composables/useI18n';
import { useNotifications } from '@/composables/useNotifications';
import type { AppNotification } from '@/types';

const props = defineProps<{
    notifications: AppNotification[];
    periodStart: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Notifications',
                titleKey: 'settings.notificationsPage.title',
                href: '/settings/notifications',
            },
        ],
    },
});

const { t, locale } = useI18n();
const { items: notifications, markRead: markNotificationRead } =
    useNotifications({
        notifications: () => props.notifications,
    });

const formattedPeriodStart = computed(() =>
    new Intl.DateTimeFormat(locale.value, {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(props.periodStart))
);
</script>

<template>
    <Head :title="t('settings.notificationsPage.title')" />

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('settings.notificationsPage.heading')"
            :description="t('settings.notificationsPage.description')"
        />

        <section
            class="overflow-hidden rounded-2xl border border-sidebar-border/70 bg-background/40 shadow-xl"
        >
            <div
                class="border-b border-sidebar-border/70 bg-[radial-gradient(circle_at_top_right,rgba(56,189,248,0.16),transparent_36%),radial-gradient(circle_at_bottom_left,rgba(16,185,129,0.14),transparent_32%)] p-4 sm:p-5"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0 flex-1 space-y-2">
                        <p
                            class="text-xs tracking-[0.2em] text-primary uppercase"
                        >
                            {{ t('settings.notificationsPage.badge') }}
                        </p>
                        <h2 class="text-2xl font-semibold sm:text-3xl">
                            {{ t('settings.notificationsPage.heroTitle') }}
                        </h2>
                        <p
                            class="max-w-2xl text-sm leading-6 text-muted-foreground"
                        >
                            {{ t('settings.notificationsPage.heroText') }}
                        </p>
                    </div>

                    <div
                        class="w-full rounded-2xl border border-primary/20 bg-background/70 p-4 text-left sm:w-auto sm:text-right"
                    >
                        <p
                            class="text-xs tracking-wide text-muted-foreground uppercase"
                        >
                            {{ t('settings.notificationsPage.period') }}
                        </p>
                        <p class="mt-2 text-2xl font-semibold">
                            {{ notifications.length }}
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ t('settings.notificationsPage.since') }}:
                            {{ formattedPeriodStart }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section
            v-if="notifications.length === 0"
            class="rounded-2xl border border-dashed border-sidebar-border/70 bg-background/30 p-5 text-center sm:p-8"
        >
            <div class="mx-auto flex max-w-xl flex-col items-center gap-4">
                <div
                    class="rounded-full border border-sidebar-border/70 bg-background/70 p-4"
                >
                    <BellOff class="h-6 w-6 text-muted-foreground" />
                </div>

                <div class="space-y-2">
                    <h3 class="text-lg font-semibold">
                        {{ t('settings.notificationsPage.emptyTitle') }}
                    </h3>
                    <p class="text-sm leading-6 text-muted-foreground">
                        {{ t('settings.notificationsPage.emptyDescription') }}
                    </p>
                </div>
            </div>
        </section>

        <section v-else class="space-y-3">
            <article
                v-for="notification in notifications"
                :key="notification.id"
                class="space-y-4"
            >
                <NotificationListItem :notification="notification" />

                <div
                    v-if="notification.url"
                    class="mt-4 grid sm:flex sm:justify-end"
                >
                    <Button as-child variant="outline" class="rounded-xl">
                        <button
                            type="button"
                            @click.stop="
                                markNotificationRead(
                                    notification,
                                    notification.url
                                )
                            "
                        >
                            {{ t('settings.notificationsPage.openLink') }}
                        </button>
                    </Button>
                </div>
            </article>
        </section>
    </div>
</template>
