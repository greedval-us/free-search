<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';

defineProps<{ page: number; hasMore: boolean; busy: boolean }>();
defineEmits<{ change: [page: number] }>();
const { t } = useI18n();
</script>

<template>
    <div class="flex gap-2">
        <button
            v-if="page > 1"
            type="button"
            class="intel-button-secondary"
            :disabled="busy"
            @click="$emit('change', page - 1)"
        >
            {{ t('telegramTracking.previous') }}
        </button>
        <button
            v-if="hasMore"
            type="button"
            class="intel-button-secondary"
            :disabled="busy"
            @click="$emit('change', page + 1)"
        >
            {{ t('telegramTracking.nextPage') }}
        </button>
    </div>
</template>
