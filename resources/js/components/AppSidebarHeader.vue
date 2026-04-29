<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useI18n } from '@/composables/useI18n';
import type { BreadcrumbItem } from '@/types';

const { locale, setLocale, t } = useI18n();

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const toggleLocale = () => {
    setLocale(locale.value === 'ru' ? 'en' : 'ru');
};
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 bg-background/80 px-6 backdrop-blur transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex min-w-0 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <Button
            type="button"
            variant="outline"
            size="sm"
            class="h-8 px-2.5 text-xs"
            @click="toggleLocale"
        >
            {{ t('common.language') }}: {{ locale.toUpperCase() }}
        </Button>
    </header>
</template>
