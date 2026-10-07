<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
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
        <AppSidebar />
        <AppContent
            variant="sidebar"
            class="flex min-h-0 min-w-0 flex-1 overflow-hidden text-sm leading-6"
        >
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <div
                class="workspace-enter flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden"
            >
                <slot />
            </div>
        </AppContent>
    </AppShell>
</template>
