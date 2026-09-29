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
        class="flex min-h-16 min-w-0 shrink-0 items-center justify-between gap-2 border-b border-border/60 bg-card/85 px-3 py-2 sm:px-5"
    >
        <div class="flex min-w-0 items-center gap-2">
            <SidebarTrigger
                class="size-11 shrink-0 rounded-xl border border-border/70 bg-background"
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

        <WorkspaceToolbarControls />
    </header>
</template>
