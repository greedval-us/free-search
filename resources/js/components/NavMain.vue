<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useI18n } from '@/composables/useI18n';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();
const { t } = useI18n();
const { setOpenMobile } = useSidebar();
</script>

<template>
    <SidebarGroup class="px-2 py-0 group-data-[collapsible=icon]:px-0">
        <SidebarGroupLabel
            class="mb-1 px-3 text-xs font-medium text-muted-foreground"
            >{{ t('navigation.platform') }}</SidebarGroupLabel
        >
        <SidebarMenu class="gap-1 group-data-[collapsible=icon]:items-center">
            <SidebarMenuItem
                v-for="item in items"
                :key="item.title"
                class="group-data-[collapsible=icon]:w-11"
            >
                <SidebarMenuButton
                    class="h-11 gap-3 rounded-lg px-3 text-sm font-medium text-muted-foreground transition-colors group-data-[collapsible=icon]:size-11! group-data-[collapsible=icon]:p-3! hover:bg-card/80 hover:text-foreground data-[active=true]:bg-primary/10 data-[active=true]:font-semibold data-[active=true]:text-primary data-[active=true]:ring-1 data-[active=true]:ring-primary/15"
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                >
                    <Link
                        :href="item.href"
                        :aria-current="
                            isCurrentUrl(item.href) ? 'page' : undefined
                        "
                        @click="setOpenMobile(false)"
                    >
                        <component :is="item.icon" />
                        <span class="block truncate">{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
