<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import WorkspaceToolbarControls from '@/components/WorkspaceToolbarControls.vue';
import { useI18n } from '@/composables/useI18n';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    }
);
const { t } = useI18n();
const breadcrumbLabel = (item: BreadcrumbItem): string =>
    item.titleKey ? t(item.titleKey) : item.title;
</script>

<template>
    <header
        class="flex min-h-16 min-w-0 shrink-0 items-center justify-between gap-3 border-b border-border/70 bg-card px-3 py-2 sm:px-5 lg:px-6"
    >
        <div class="flex min-w-0 items-center gap-3">
            <SidebarTrigger
                class="size-11 shrink-0 rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground"
            />
            <div
                v-if="breadcrumbs && breadcrumbs.length > 0"
                class="hidden min-w-0 sm:block"
            >
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
            <span
                v-if="breadcrumbs.length"
                class="truncate text-sm font-semibold sm:hidden"
            >
                {{ breadcrumbLabel(breadcrumbs[breadcrumbs.length - 1]) }}
            </span>
        </div>

        <WorkspaceToolbarControls
            button-class="group relative h-11 w-11 rounded-lg text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring"
            locale-button-class="h-11 rounded-lg border-border bg-card px-3 text-sm font-medium"
        />
    </header>
</template>
