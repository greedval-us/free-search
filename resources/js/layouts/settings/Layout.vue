<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useI18n } from '@/composables/useI18n';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const { t } = useI18n();

const sidebarNavItems = computed<NavItem[]>(() => [
    {
        title: t('settings.profile'),
        href: editProfile(),
    },
    {
        title: t('settings.security'),
        href: editSecurity(),
    },
    {
        title: t('settings.notifications'),
        href: '/settings/notifications',
    },
    {
        title: t('settings.appearance'),
        href: editAppearance(),
    },
    {
        title: t('settings.billing'),
        href: '/settings/billing',
    },
]);

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div
        class="m-2 flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden rounded-xl border border-sidebar-border/80 bg-card/95 px-3 py-4 shadow-sm sm:m-3 sm:px-4 lg:m-4"
    >
        <div
            class="flex min-h-0 min-w-0 flex-1 flex-col lg:flex-row lg:gap-6 xl:gap-8"
        >
            <aside class="min-w-0 shrink-0 lg:w-52">
                <nav
                    class="intel-scroll -mx-1 flex min-w-0 gap-1 overflow-x-auto px-1 pb-2 lg:mx-0 lg:flex-col lg:overflow-visible lg:px-0 lg:pb-0"
                    :aria-label="t('settings.title')"
                >
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'shrink-0 justify-start text-muted-foreground hover:text-foreground lg:w-full',
                            {
                                'bg-primary/15 text-primary':
                                    isCurrentOrParentUrl(item.href),
                            },
                        ]"
                        as-child
                    >
                        <Link
                            :href="item.href"
                            :aria-current="
                                isCurrentOrParentUrl(item.href)
                                    ? 'page'
                                    : undefined
                            "
                        >
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-4 lg:hidden" />

            <div class="min-h-0 min-w-0 flex-1">
                <section
                    class="intel-scroll h-full min-h-0 w-full max-w-6xl space-y-6 overflow-y-auto pr-1 [scrollbar-gutter:stable]"
                >
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
