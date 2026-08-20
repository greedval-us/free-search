<script setup lang="ts">
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import { useI18n } from '@/composables/useI18n';

defineProps<{ text: string }>();

const { t } = useI18n();
</script>

<template>
    <HelpTooltip :label="t('shifr.help.label')" :text="text" />
</template>
