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
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="t('settings.appearancePage.title')" />

    <h1 class="sr-only">{{ t('settings.appearancePage.title') }}</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('settings.appearancePage.heading')"
            :description="t('settings.appearancePage.headingDescription')"
        />
        <AppearanceTabs />
    </div>
</template>
