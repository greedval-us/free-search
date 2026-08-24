<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { BellOff } from 'lucide-vue-next';
import { computed } from 'vue';
import NotificationListItem from '@/components/NotificationListItem.vue';
import SettingsHero from '@/components/settings/SettingsHero.vue';
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

    <div class="max-w-5xl space-y-5">
        <SettingsHero
            :badge="t('settings.notificationsPage.badge')"
            :title="t('settings.notificationsPage.heroTitle')"
            :description="t('settings.notificationsPage.heroText')"
        >
            <template #summary>
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
            </template>
        </SettingsHero>

        <section
            v-if="notifications.length === 0"
            class="rounded-xl border border-dashed border-sidebar-border/70 bg-background/30 p-5 text-center sm:p-6"
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
