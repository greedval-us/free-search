<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell, BookOpen, Folder, Menu, Search } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet/index';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useI18n } from '@/composables/useI18n';
import { getInitials } from '@/composables/useInitials';
import { buildHeaderNavItems } from '@/lib/navigation/modules';
import { cn } from '@/lib/utils';
import { toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { AppNotification, BreadcrumbItem, NavItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);
const { isCurrentUrl, whenCurrentUrl } = useCurrentUrl();
const { t } = useI18n();
const notifications = computed(() => auth.value.notifications);
const hasUnreadNotifications = computed(
    () => notifications.value.unreadCount > 0,
);

const activeItemStyles =
    'bg-cyan-500/15 text-cyan-700 shadow-[0_10px_30px_-20px_rgba(8,145,178,0.95)] dark:text-cyan-100';

const mainNavItems = computed<NavItem[]>(() => buildHeaderNavItems(t));

const rightNavItems = computed<NavItem[]>(() => [
    {
        title: t('navigation.repository'),
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: t('navigation.documentation'),
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
]);

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
        String(notifications.value.unreadCount),
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
    <div>
        <div class="app-header-shell">
            <div class="app-header-panel">
                <!-- Mobile Menu -->
                <div class="lg:hidden">
                    <Sheet>
                        <SheetTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="mr-2 h-9 w-9"
                            >
                                <Menu class="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent
                            side="left"
                            class="w-[300px] rounded-r-3xl border-sidebar-border bg-card/95 p-6 text-foreground shadow-2xl"
                        >
                            <SheetTitle class="sr-only">{{
                                t('navigation.menu')
                            }}</SheetTitle>
                            <SheetHeader class="flex justify-start text-left">
                                <AppLogoIcon
                                    class="size-6 fill-current text-primary"
                                />
                            </SheetHeader>
                            <div
                                class="flex h-full flex-1 flex-col justify-between space-y-4 py-6"
                            >
                                <nav class="-mx-3 space-y-1">
                                    <Link
                                        v-for="item in mainNavItems"
                                        :key="item.title"
                                        :href="item.href"
                                        class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                                        :class="
                                            whenCurrentUrl(
                                                item.href,
                                                activeItemStyles
                                            )
                                        "
                                    >
                                        <component
                                            v-if="item.icon"
                                            :is="item.icon"
                                            class="h-5 w-5"
                                        />
                                        {{ item.title }}
                                    </Link>
                                </nav>
                                <div class="flex flex-col space-y-4">
                                    <a
                                        v-for="item in rightNavItems"
                                        :key="item.title"
                                        :href="toUrl(item.href)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex items-center space-x-2 text-sm font-medium text-muted-foreground hover:text-foreground"
                                    >
                                        <component
                                            v-if="item.icon"
                                            :is="item.icon"
                                            class="h-5 w-5"
                                        />
                                        <span>{{ item.title }}</span>
                                    </a>
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link :href="dashboard()" class="flex items-center gap-x-3">
                    <AppLogo />
                    <span class="app-header-brand-chip">{{
                        t('common.workspace')
                    }}</span>
                </Link>

                <!-- Desktop Menu -->
                <div class="hidden h-full lg:flex lg:flex-1">
                    <NavigationMenu
                        class="ml-8 flex h-full items-stretch xl:ml-10"
                    >
                        <NavigationMenuList
                            class="app-header-surface app-header-nav"
                        >
                            <NavigationMenuItem
                                v-for="(item, index) in mainNavItems"
                                :key="index"
                                class="relative flex h-full items-center"
                            >
                                <Link
                                    :class="[
                                        navigationMenuTriggerStyle(),
                                        whenCurrentUrl(
                                            item.href,
                                            activeItemStyles
                                        ),
                                        'h-9 cursor-pointer rounded-full px-3.5',
                                    ]"
                                    :href="item.href"
                                >
                                    <component
                                        v-if="item.icon"
                                        :is="item.icon"
                                        class="mr-2 h-4 w-4"
                                    />
                                    {{ item.title }}
                                </Link>
                                <div
                                    v-if="isCurrentUrl(item.href)"
                                    class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-primary"
                                ></div>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <div class="ml-auto flex items-center space-x-2">
                    <div class="app-header-actions">
                        <Button
                            variant="ghost"
                            size="icon"
                            class="group h-9 w-9 cursor-pointer"
                        >
                            <Search
                                class="size-5 opacity-80 group-hover:opacity-100"
                            />
                        </Button>

                        <DropdownMenu>
                            <DropdownMenuTrigger :as-child="true">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="group relative h-9 w-9 cursor-pointer"
                                >
                                    <Bell
                                        class="size-5 opacity-80 group-hover:opacity-100"
                                    />
                                    <span
                                        v-if="hasUnreadNotifications"
                                        class="absolute top-1.5 right-1.5 inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400 ring-2 ring-background"
                                    />
                                    <span class="sr-only">{{
                                        t('navigation.notifications')
                                    }}</span>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                align="end"
                                class="w-[22rem] rounded-3xl p-2"
                            >
                                <div
                                    class="flex items-center justify-between gap-4 px-3 py-2"
                                >
                                    <div>
                                        <p
                                            class="text-sm font-semibold text-foreground"
                                        >
                                            {{ t('navigation.notifications') }}
                                        </p>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{
                                                hasUnreadNotifications
                                                    ? unreadNotificationsLabel
                                                    : t(
                                                          'navigation.notificationsEmpty',
                                                      )
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
                                        {{
                                            t(
                                                'navigation.markAllNotificationsRead',
                                            )
                                        }}
                                    </Button>
                                </div>

                                <div class="max-h-[26rem] space-y-2 overflow-y-auto p-2">
                                    <div
                                        v-if="notifications.items.length === 0"
                                        class="rounded-2xl border border-dashed border-slate-300/70 bg-slate-50/80 p-4 text-sm text-muted-foreground dark:border-slate-800 dark:bg-slate-950/40"
                                    >
                                        {{
                                            t(
                                                'navigation.notificationsPlaceholder',
                                            )
                                        }}
                                    </div>

                                    <template
                                        v-for="notification in notifications.items"
                                        :key="notification.id"
                                    >
                                        <Link
                                            v-if="notification.url"
                                            :href="notification.url"
                                            :class="
                                                notificationCardStyles(
                                                    notification,
                                                )
                                            "
                                        >
                                            <div
                                                class="mb-1 flex items-start justify-between gap-3"
                                            >
                                                <p
                                                    class="text-sm font-medium text-foreground"
                                                >
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
                                                v-if="
                                                    formatNotificationDate(
                                                        notification.created_at,
                                                    )
                                                "
                                                class="mt-2 text-xs text-slate-500"
                                            >
                                                {{
                                                    formatNotificationDate(
                                                        notification.created_at,
                                                    )
                                                }}
                                            </p>
                                        </Link>

                                        <div
                                            v-else
                                            :class="
                                                notificationCardStyles(
                                                    notification,
                                                )
                                            "
                                        >
                                            <div
                                                class="mb-1 flex items-start justify-between gap-3"
                                            >
                                                <p
                                                    class="text-sm font-medium text-foreground"
                                                >
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
                                                v-if="
                                                    formatNotificationDate(
                                                        notification.created_at,
                                                    )
                                                "
                                                class="mt-2 text-xs text-slate-500"
                                            >
                                                {{
                                                    formatNotificationDate(
                                                        notification.created_at,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </template>
                                </div>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <div class="hidden space-x-1 lg:flex">
                            <template
                                v-for="item in rightNavItems"
                                :key="item.title"
                            >
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                as-child
                                                class="group h-9 w-9 cursor-pointer"
                                            >
                                                <a
                                                    :href="toUrl(item.href)"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >
                                                    <span class="sr-only">{{
                                                        item.title
                                                    }}</span>
                                                    <component
                                                        :is="item.icon"
                                                        class="size-5 opacity-80 group-hover:opacity-100"
                                                    />
                                                </a>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{{ item.title }}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </template>
                        </div>
                    </div>

                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="app-header-surface relative size-10 w-auto p-1 focus-within:ring-2 focus-within:ring-primary"
                            >
                                <Avatar
                                    class="size-8 overflow-hidden rounded-full"
                                >
                                    <AvatarImage
                                        v-if="auth.user.avatar"
                                        :src="auth.user.avatar"
                                        :alt="auth.user.name"
                                    />
                                    <AvatarFallback
                                        class="rounded-lg bg-primary/15 font-semibold text-primary"
                                    >
                                        {{ getInitials(auth.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <div v-if="props.breadcrumbs.length > 1" class="app-breadcrumb-shell">
            <div
                class="mx-auto flex h-12 w-full items-center justify-start px-4 text-muted-foreground md:max-w-7xl"
            >
                <Breadcrumbs :breadcrumbs="props.breadcrumbs" />
            </div>
        </div>
    </div>
</template>
