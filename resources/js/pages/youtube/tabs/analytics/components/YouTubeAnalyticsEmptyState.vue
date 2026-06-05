<script setup lang="ts">
import { BarChart3, RefreshCw } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';

defineProps<{
    loading: boolean;
    disabled?: boolean;
}>();

defineEmits<{
    (event: 'refresh'): void;
}>();

const { t } = useI18n();
</script>

<template>
    <div
        class="flex min-h-[50vh] flex-col items-center justify-center rounded-2xl border border-dashed border-sidebar-border/80 bg-card/75 p-8 text-center shadow-[0_24px_80px_-42px_rgba(15,23,42,0.5)] backdrop-blur"
    >
        <BarChart3 class="mb-4 h-14 w-14 text-muted-foreground" />
        <h3 class="text-lg font-semibold">
            {{ t('youtube.analytics.empty.title') }}
        </h3>
        <p class="mt-2 max-w-2xl text-sm text-muted-foreground">
            {{ t('youtube.analytics.empty.description') }}
        </p>
        <button
            type="button"
            class="intel-button-primary mt-5"
            :disabled="loading || disabled"
            @click="$emit('refresh')"
        >
            <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
            {{
                loading
                    ? t('youtube.common.loading')
                    : t('youtube.analytics.refresh')
            }}
        </button>
    </div>
</template>
