<script setup lang="ts">
import PublicFeatureCards from '@/components/public/PublicFeatureCards.vue';
import SeoHead from '@/components/SeoHead.vue';
import { useI18n } from '@/composables/useI18n';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { PublicSiteProps } from '@/types/publicSite';
defineProps<PublicSiteProps>();
const { t } = useI18n();
</script>

<template>
    <SeoHead
        :title="t('publicSite.catalogTitle')"
        :description="t('publicSite.catalogDescription')"
        path="/features"
    />
    <PublicLayout :can-register="canRegister">
        <section class="public-page-intro">
            <p class="public-eyebrow">{{ t('publicSite.catalog') }}</p>
            <h1>{{ t('publicSite.catalogTitle') }}</h1>
            <p class="public-lead">{{ t('publicSite.catalogDescription') }}</p>
            <p class="public-note">{{ t('publicSite.freeNote') }}</p>
        </section>
        <section
            class="public-section public-catalog"
            :aria-label="t('publicSite.catalog')"
        >
            <PublicFeatureCards :features="features" heading-tag="h2" />
        </section>
    </PublicLayout>
</template>
