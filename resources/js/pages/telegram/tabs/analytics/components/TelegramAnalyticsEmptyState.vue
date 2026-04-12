<script setup lang="ts">
import { BarChart3, RefreshCw } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';

defineProps<{
    loading: boolean;
}>();

defineEmits<{
    (event: 'refresh'): void;
}>();

const { t } = useI18n();
</script>

<template>
    <div
        class="flex min-h-[50vh] flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/80 bg-card/70 p-8 text-center shadow-xl backdrop-blur"
    >
        <BarChart3 class="mb-4 h-14 w-14 text-muted-foreground" />
        <h3 class="text-lg font-semibold">{{ t('telegram.analytics.empty.title') }}</h3>
        <p class="mt-2 max-w-2xl text-sm text-muted-foreground">
            {{ t('telegram.analytics.empty.description') }}
        </p>
        <button
            type="button"
            class="mt-5 inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="loading"
            @click="$emit('refresh')"
        >
            <RefreshCw class="h-4 w-4" />
            {{ t('telegram.analytics.refresh') }}
        </button>
    </div>
</template>

