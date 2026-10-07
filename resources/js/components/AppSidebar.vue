<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
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
    useSidebar,
} from '@/components/ui/sidebar';
import { useI18n } from '@/composables/useI18n';
import {
    buildFooterNavItems,
    buildMainNavItems,
} from '@/lib/navigation/modules';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const { t } = useI18n();
const { setOpenMobile } = useSidebar();

const mainNavItems = computed<NavItem[]>(() => buildMainNavItems(t));
const footerNavItems = computed<NavItem[]>(() => buildFooterNavItems(t));
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader
            class="px-3 pt-3 pb-4 group-data-[collapsible=icon]:px-0"
        >
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        class="h-14 rounded-xl px-3 group-data-[collapsible=icon]:mx-auto group-data-[collapsible=icon]:size-11! group-data-[collapsible=icon]:p-1.5!"
                    >
                        <Link :href="dashboard()" @click="setOpenMobile(false)">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="gap-5">
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter
            class="workspace-sidebar-footer gap-3 border-t border-sidebar-border/70 px-3 py-3"
        >
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
</template>
