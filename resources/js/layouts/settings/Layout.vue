<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import WorkspaceToolbarControls from '@/components/WorkspaceToolbarControls.vue';
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
        class="mx-4 mt-4 mb-4 flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-sidebar-border/80 bg-card/70 px-4 py-6 shadow-xl backdrop-blur sm:mx-6 sm:mt-6"
    >
        <Heading
            :title="t('settings.title')"
            :description="t('settings.description')"
        />

        <div class="flex min-h-0 flex-1 flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-56">
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
                    />
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="min-h-0 min-w-0 flex-1">
                <section
                    class="intel-scroll max-h-[72vh] w-full max-w-6xl space-y-12 overflow-y-auto pr-1 [scrollbar-gutter:stable]"
                >
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
