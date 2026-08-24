<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import WorkspaceToolbarControls from '@/components/WorkspaceToolbarControls.vue';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    }
);
</script>

<template>
    <header
        class="flex h-14 min-w-0 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 bg-background/95 px-3 shadow-sm transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 sm:px-4"
    >
        <div class="flex min-w-0 items-center gap-2">
            <SidebarTrigger
                class="shrink-0 rounded-full border border-sidebar-border bg-background shadow-sm"
            />
            <div
                v-if="breadcrumbs && breadcrumbs.length > 0"
                class="hidden min-w-0 sm:block"
            >
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>

        <WorkspaceToolbarControls />
    </header>
</template>
