<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import WorkspaceToolbarControls from '@/components/WorkspaceToolbarControls.vue';
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
        class="m-2 flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden rounded-2xl border border-sidebar-border/80 bg-card/70 px-3 py-4 shadow-xl backdrop-blur sm:m-4 sm:px-4 sm:py-6 lg:m-6"
    >
        <Heading
            :title="t('settings.title')"
            :description="t('settings.description')"
        />

        <div
            class="flex min-h-0 min-w-0 flex-1 flex-col lg:flex-row lg:gap-8 xl:gap-12"
        >
            <aside class="w-full shrink-0 lg:w-56">
                <nav
                    class="flex flex-col space-y-1 space-x-0"
                    :aria-label="t('settings.title')"
                >
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start text-muted-foreground hover:text-foreground',
                            {
                                'bg-primary/15 text-primary':
                                    isCurrentOrParentUrl(item.href),
                            },
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>
                    </Button>

                    <WorkspaceToolbarControls
                        container-class="mt-2 flex items-center gap-2"
                        button-class="group relative h-10 w-10 rounded-md border border-input bg-background text-foreground transition hover:bg-accent hover:text-accent-foreground"
                        locale-button-size="default"
                        locale-button-class="w-full justify-start"
                        :show-notifications="false"
                    />
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="min-h-0 min-w-0 flex-1">
                <section
                    class="intel-scroll h-full min-h-0 w-full max-w-6xl space-y-8 overflow-y-auto pr-1 [scrollbar-gutter:stable] sm:space-y-12"
                >
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
