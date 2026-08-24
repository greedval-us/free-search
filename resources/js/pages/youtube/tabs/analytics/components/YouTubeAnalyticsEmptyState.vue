<script setup lang="ts">
import AnalyticsActionEmptyState from '@/components/ui/analytics/AnalyticsActionEmptyState.vue';
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
    <AnalyticsActionEmptyState
        :title="t('youtube.analytics.empty.title')"
        :description="t('youtube.analytics.empty.description')"
        :action-label="t('youtube.analytics.refresh')"
        :loading-label="t('youtube.common.loading')"
        :loading="loading"
        :disabled="disabled"
        @action="$emit('refresh')"
    />
</template>
