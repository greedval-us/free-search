<script setup lang="ts">
import { BarChart3 } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();
</script>

<template>
    <section class="flex min-h-0 flex-1 flex-col items-center justify-center rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
        <div class="text-center">
            <BarChart3 class="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
            <h3 class="text-lg font-semibold">{{ t('telegram.analytics.comingSoon') }}</h3>
            <p class="mt-2 text-sm text-muted-foreground">{{ t('telegram.analytics.description') }}</p>
        </div>
    </section>
</template>
