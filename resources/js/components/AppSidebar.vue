<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    AtSign,
    BookOpenText,
    Building2,
    FileSearch,
    Fingerprint,
    Globe,
    LayoutGrid,
    MailSearch,
    Newspaper,
    Radar,
    Send,
    User,
    Youtube,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useI18n } from '@/composables/useI18n';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const { t, locale } = useI18n();

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: t('navigation.dashboard'),
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: t('navigation.telegram'),
        href: '/telegram',
        icon: Send,
    },
    {
        title: t('navigation.youtube'),
        href: '/youtube',
        icon: Youtube,
    },
    {
        title: t('navigation.username'),
        href: '/username',
        icon: AtSign,
    },
    {
        title: t('navigation.siteIntel'),
        href: '/site-intel',
        icon: Radar,
    },
    {
        title: t('navigation.fio'),
        href: '/fio',
        icon: User,
    },
    {
        title: t('navigation.companyIntel'),
        href: '/company-intel',
        icon: Building2,
    },
    {
        title: t('navigation.documentIntel'),
        href: '/document-intel',
        icon: FileSearch,
    },
    {
        title: t('navigation.emailIntel'),
        href: '/email-intel',
        icon: MailSearch,
    },
    {
        title: t('navigation.newsMediaIntel'),
        href: '/news-media-intel',
        icon: Newspaper,
    },
    {
        title: t('navigation.domainInfraIntel'),
        href: '/domain-infra-intel',
        icon: Globe,
    },
    {
        title: t('navigation.shifr'),
        href: '/shifr',
        icon: Fingerprint,
    },
]);

const footerNavItems = computed<NavItem[]>(() => [
    {
        title: locale.value === 'ru' ? 'Вики модулей' : 'Modules Wiki',
        href: '/wiki/modules',
        icon: BookOpenText,
    },
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
