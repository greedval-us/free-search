<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <div class="relative">
            <AppSidebar />
            <SidebarTrigger
                class="absolute top-4 -right-3 z-30 rounded-full border border-sidebar-border bg-background shadow-sm"
            />
        </div>
        <AppContent
            variant="sidebar"
            class="flex min-h-0 flex-1 overflow-hidden"
        >
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <slot />
            </div>
        </AppContent>
    </AppShell>
</template>
