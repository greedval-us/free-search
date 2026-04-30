<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useI18n } from '@/composables/useI18n';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const { locale, setLocale, t } = useI18n();

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
]);

const { isCurrentOrParentUrl } = useCurrentUrl();
const page = usePage();

const isAuthenticated = computed(() => Boolean(page.props.auth?.user));

const toggleLocale = () => {
    setLocale(locale.value === 'ru' ? 'en' : 'ru');
};
</script>

<template>
    <div class="mx-4 mt-4 rounded-2xl border border-sidebar-border/80 bg-card/70 px-4 py-6 shadow-xl backdrop-blur sm:mx-6 sm:mt-6">
        <Heading
            :title="t('settings.title')"
            :description="t('settings.description')"
        />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
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
                            { 'bg-primary/15 text-primary': isCurrentOrParentUrl(item.href) },
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>
                    </Button>

                    <Button
                        v-if="isAuthenticated"
                        type="button"
                        variant="outline"
                        class="mt-2 w-full justify-start"
                        @click="toggleLocale"
                    >
                        {{ t('common.language') }}: {{ locale.toUpperCase() }}
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
