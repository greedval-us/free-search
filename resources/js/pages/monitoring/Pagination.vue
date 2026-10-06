<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/composables/useI18n';
import type { Pagination } from './types';
defineProps<{ page: Pagination<unknown> }>();
const { t } = useI18n();
</script>
<template>
    <nav
        v-if="page.last_page > 1"
        class="flex flex-wrap items-center gap-3"
        :aria-label="t('monitoring.pagination')"
    >
        <Button v-if="page.prev_page_url" as-child variant="outline"
            ><Link :href="page.prev_page_url">{{
                t('monitoring.previous')
            }}</Link></Button
        >
        <span class="text-sm text-muted-foreground"
            >{{ page.current_page }} / {{ page.last_page }} ·
            {{ page.total }}</span
        >
        <Button v-if="page.next_page_url" as-child variant="outline"
            ><Link :href="page.next_page_url">{{
                t('monitoring.next')
            }}</Link></Button
        >
    </nav>
</template>
