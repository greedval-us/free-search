<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Bell, BellOff, Clock3 } from 'lucide-vue-next';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/composables/useI18n';
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

const formattedPeriodStart = computed(() =>
    new Intl.DateTimeFormat(locale.value, {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(props.periodStart))
);

const formatNotificationDate = (value: string | null) => {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat(locale.value, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};
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
                class="border-b border-sidebar-border/70 bg-[radial-gradient(circle_at_top_right,rgba(56,189,248,0.16),transparent_36%),radial-gradient(circle_at_bottom_left,rgba(16,185,129,0.14),transparent_32%)] p-5"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-2">
                        <p
                            class="text-xs tracking-[0.2em] text-cyan-200 uppercase"
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
                        class="rounded-2xl border border-cyan-400/20 bg-slate-950/35 p-4 text-right"
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
            class="rounded-2xl border border-dashed border-sidebar-border/70 bg-background/30 p-8 text-center"
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
                class="rounded-2xl border border-sidebar-border/70 bg-background/45 p-5 shadow-lg transition"
                :class="
                    notification.read_at
                        ? ''
                        : 'border-cyan-400/30 bg-cyan-500/6'
                "
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 flex-1 space-y-2">
                        <div class="flex items-center gap-2">
                            <div
                                class="rounded-full border border-sidebar-border/70 bg-background/70 p-2"
                            >
                                <Bell class="h-4 w-4 text-cyan-300" />
                            </div>

                            <div class="flex min-w-0 items-center gap-2">
                                <h3 class="truncate text-base font-semibold">
                                    {{ notification.title }}
                                </h3>
                                <span
                                    v-if="!notification.read_at"
                                    class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-400"
                                />
                            </div>
                        </div>

                        <p
                            v-if="notification.body"
                            class="text-sm leading-6 text-muted-foreground"
                        >
                            {{ notification.body }}
                        </p>
                    </div>

                    <div
                        class="flex items-center gap-2 text-xs text-muted-foreground"
                    >
                        <Clock3 class="h-4 w-4" />
                        {{ formatNotificationDate(notification.created_at) }}
                    </div>
                </div>

                <div v-if="notification.url" class="mt-4 flex justify-end">
                    <Button as-child variant="outline" class="rounded-xl">
                        <Link :href="notification.url">
                            {{ t('settings.notificationsPage.openLink') }}
                        </Link>
                    </Button>
                </div>
            </article>
        </section>
    </div>
</template>
