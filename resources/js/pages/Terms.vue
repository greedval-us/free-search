<script setup lang="ts">
import { computed } from 'vue';
import LegalDocument from '@/components/public/LegalDocument.vue';
import { useLocale } from '@/composables/useLocale';
import en from '@/locales/en/terms.json';
import ru from '@/locales/ru/terms.json';

const { locale } = useLocale();
const content = computed(() => (locale.value === 'ru' ? ru : en));
const sections = computed(() => Object.values(content.value.sections));
</script>

<template>
    <LegalDocument page="terms" :content="content" :sections="sections" />
</template>
