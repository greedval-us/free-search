<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import UniversalPlaceholderPanel from '@/components/UniversalPlaceholderPanel.vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps<{
    placeholder: {
        context: 'generic' | 'checkout';
        plan: '' | 'free' | 'plus' | 'pro';
        back: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Placeholder',
                titleKey: 'settings.placeholderPage.title',
                href: '/settings/placeholder',
            },
        ],
    },
});

const { t } = useI18n();

const replaceToken = (
    template: string,
    token: string,
    value: string
): string => template.replace(`:${token}`, value);

const planLabel = computed(() => {
    if (!props.placeholder.plan) {
        return t('settings.placeholderPage.genericPlan');
    }

    return t(`settings.billingPage.plans.${props.placeholder.plan}.name`);
});

const panelContent = computed(() => {
    if (props.placeholder.context === 'checkout') {
        return {
            badge: t('settings.placeholderPage.checkout.badge'),
            title: replaceToken(
                t('settings.placeholderPage.checkout.title'),
                'plan',
                planLabel.value
            ),
            description: t('settings.placeholderPage.checkout.description'),
            bullets: [
                t('settings.placeholderPage.checkout.bullets.first'),
                t('settings.placeholderPage.checkout.bullets.second'),
                t('settings.placeholderPage.checkout.bullets.third'),
            ],
            note: t('settings.placeholderPage.checkout.note'),
        };
    }

    return {
        badge: t('settings.placeholderPage.generic.badge'),
        title: t('settings.placeholderPage.generic.title'),
        description: t('settings.placeholderPage.generic.description'),
        bullets: [
            t('settings.placeholderPage.generic.bullets.first'),
            t('settings.placeholderPage.generic.bullets.second'),
            t('settings.placeholderPage.generic.bullets.third'),
        ],
        note: t('settings.placeholderPage.generic.note'),
    };
});
</script>

<template>
    <Head :title="t('settings.placeholderPage.title')" />

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('settings.placeholderPage.heading')"
            :description="t('settings.placeholderPage.description')"
        />

        <UniversalPlaceholderPanel
            :badge="panelContent.badge"
            :title="panelContent.title"
            :description="panelContent.description"
            :bullets="panelContent.bullets"
            :note="panelContent.note"
            :secondary-label="t('settings.placeholderPage.back')"
            :secondary-href="placeholder.back"
        />
    </div>
</template>
