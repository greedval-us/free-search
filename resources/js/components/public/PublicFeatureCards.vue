<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';
import type { PublicFeature } from '@/types/publicSite';
withDefaults(
    defineProps<{ features: PublicFeature[]; headingTag?: 'h2' | 'h3' }>(),
    { headingTag: 'h3' }
);
const { t } = useI18n();
</script>

<template>
    <div class="public-feature-grid">
        <Link
            v-for="feature in features"
            :key="feature.slug"
            :href="feature.url"
            class="public-feature-card"
        >
            <component :is="headingTag">{{
                t(`publicSite.features.${feature.slug}.title`)
            }}</component>
            <p>{{ t(`publicSite.features.${feature.slug}.summary`) }}</p>
            <span class="public-text-link"
                >{{ t('publicSite.details') }}
                <ArrowRight :size="16" aria-hidden="true"
            /></span>
        </Link>
    </div>
</template>
