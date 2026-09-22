<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PublicAction from '@/components/public/PublicAction.vue';
import PublicFeatureCards from '@/components/public/PublicFeatureCards.vue';
import PublicWorkflow from '@/components/public/PublicWorkflow.vue';
import SeoHead from '@/components/SeoHead.vue';
import { useI18n } from '@/composables/useI18n';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { PublicSiteProps } from '@/types/publicSite';

defineProps<PublicSiteProps>();
const { t } = useI18n();
const scenarios = [
    { key: 'monitor', feature: 'telegram-tracking' },
    { key: 'research', feature: 'youtube' },
    { key: 'check', feature: 'site-intel' },
];
</script>

<template>
    <SeoHead
        :title="t('publicSite.homeTitle')"
        :description="t('publicSite.homeDescription')"
        path="/"
    />
    <PublicLayout :can-register="canRegister">
        <section class="public-hero">
            <div>
                <p class="public-eyebrow">
                    <span class="public-status-dot" aria-hidden="true"></span
                    >{{ t('publicSite.beta') }}
                </p>
                <h1>
                    {{ t('publicSite.heroTitle') }}
                    <span>{{ t('publicSite.heroAccent') }}</span>
                </h1>
                <p class="public-lead">{{ t('publicSite.heroText') }}</p>
                <div class="public-actions">
                    <PublicAction :can-register="canRegister" /><Link
                        href="/features"
                        class="public-button public-button-secondary"
                        >{{ t('publicSite.explore') }}</Link
                    >
                </div>
                <p class="public-note">{{ t('publicSite.freeNote') }}</p>
            </div>
            <PublicWorkflow />
        </section>
        <section class="public-section" aria-labelledby="scenarios-heading">
            <div class="public-section-heading">
                <p class="public-eyebrow">
                    {{ t('publicSite.scenariosLabel') }}
                </p>
                <h2 id="scenarios-heading">
                    {{ t('publicSite.scenariosTitle') }}
                </h2>
            </div>
            <div class="public-scenarios">
                <article
                    v-for="(scenario, index) in scenarios"
                    :key="scenario.key"
                >
                    <span class="public-number" aria-hidden="true"
                        >0{{ index + 1 }}</span
                    >
                    <h3>
                        {{ t(`publicSite.scenarios.${scenario.key}.title`) }}
                    </h3>
                    <p>{{ t(`publicSite.scenarios.${scenario.key}.text`) }}</p>
                    <Link
                        :href="
                            features.find(
                                (feature) => feature.slug === scenario.feature
                            )?.url ?? '/features'
                        "
                        class="public-text-link"
                        >{{ t('publicSite.details') }} &rarr;</Link
                    >
                </article>
            </div>
        </section>
        <section class="public-section" aria-labelledby="features-heading">
            <div class="public-section-heading">
                <p class="public-eyebrow">{{ t('publicSite.catalog') }}</p>
                <h2 id="features-heading">
                    {{ t('publicSite.catalogTitle') }}
                </h2>
                <p>{{ t('publicSite.catalogDescription') }}</p>
            </div>
            <PublicFeatureCards :features="features" />
        </section>
        <section
            class="public-section public-two-column"
            aria-labelledby="expectations-heading"
        >
            <div>
                <p class="public-eyebrow">
                    {{ t('publicSite.expectationsLabel') }}
                </p>
                <h2 id="expectations-heading">
                    {{ t('publicSite.expectationsTitle') }}
                </h2>
                <p class="public-lead">
                    {{ t('publicSite.expectationsText') }}
                </p>
            </div>
            <div class="public-faq">
                <details
                    v-for="question in ['access', 'coverage', 'data']"
                    :key="question"
                >
                    <summary>
                        {{ t(`publicSite.faq.${question}.question`) }}
                    </summary>
                    <p>{{ t(`publicSite.faq.${question}.answer`) }}</p>
                </details>
            </div>
        </section>
        <section class="public-cta">
            <div>
                <p class="public-eyebrow">{{ t('publicSite.nextStep') }}</p>
                <h2>{{ t('publicSite.ctaTitle') }}</h2>
                <p>{{ t('publicSite.ctaText') }}</p>
            </div>
            <PublicAction :can-register="canRegister" />
        </section>
    </PublicLayout>
</template>
