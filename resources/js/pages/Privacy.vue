<script setup lang="ts">
import { computed } from 'vue';
import LegalDocument from '@/components/public/LegalDocument.vue';
import { useI18n } from '@/composables/useI18n';
import en from '@/locales/en/privacy.json';
import ru from '@/locales/ru/privacy.json';

const props = defineProps<{ contactEmail: string | null }>();
const { locale, t } = useI18n();

const content = computed(() => (locale.value === 'ru' ? ru : en));
const sectionKeys = Object.keys(en.sectionLabels) as Array<
    keyof typeof en.sectionLabels
>;
const sections = computed(() =>
    sectionKeys.map((key, index) => ({
        title: `${index + 1}. ${content.value.sectionLabels[key]}`,
        items:
            key === 'contacts' && props.contactEmail
                ? [
                      t('privacy.contactEmail', { email: props.contactEmail }),
                      ...content.value.contacts,
                  ]
                : content.value[key],
    }))
);
</script>

<template>
    <LegalDocument page="privacy" :content="content" :sections="sections" />
</template>
