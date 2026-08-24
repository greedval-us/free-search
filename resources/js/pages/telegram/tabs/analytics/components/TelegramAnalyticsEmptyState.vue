<script setup lang="ts">
import AnalyticsActionEmptyState from '@/components/ui/analytics/AnalyticsActionEmptyState.vue';
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
    <AnalyticsActionEmptyState
        :title="t('telegram.analytics.empty.title')"
        :description="t('telegram.analytics.empty.description')"
        :action-label="t('telegram.analytics.refresh')"
        :loading-label="t('telegram.analytics.loading')"
        :loading="loading"
        @action="$emit('refresh')"
    />
</template>
