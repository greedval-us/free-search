<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { useI18n } from '@/composables/useI18n';
import { edit } from '@/routes/appearance';

const { t } = useI18n();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Appearance settings',
                titleKey: 'settings.appearancePage.title',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="t('settings.appearancePage.title')" />

    <div class="max-w-2xl space-y-5">
        <Heading
            variant="small"
            as="h1"
            :title="t('settings.appearancePage.heading')"
            :description="t('settings.appearancePage.headingDescription')"
        />
        <AppearanceTabs />
    </div>
</template>
