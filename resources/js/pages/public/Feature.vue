<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PublicAction from '@/components/public/PublicAction.vue';
import PublicFeatureCards from '@/components/public/PublicFeatureCards.vue';
import SeoHead from '@/components/SeoHead.vue';
import { useI18n } from '@/composables/useI18n';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { PublicFeature, PublicSiteProps } from '@/types/publicSite';
const props = defineProps<
    PublicSiteProps & { feature: PublicFeature; policy: Record<string, number> }
>();
const { t } = useI18n();
const content = (key: string) =>
    t(`publicSite.features.${props.feature.slug}.${key}`, props.policy);
</script>

<template>
    <SeoHead
        :title="content('title')"
        :description="content('summary')"
        :path="feature.url"
    />
    <PublicLayout :can-register="canRegister">
        <section class="public-page-intro">
            <nav
                class="public-breadcrumb"
                :aria-label="t('publicSite.breadcrumb')"
            >
                <Link href="/features">{{ t('publicSite.catalog') }}</Link
                ><span aria-hidden="true">/</span
                ><span aria-current="page">{{ content('title') }}</span>
            </nav>
            <p class="public-eyebrow">{{ t('publicSite.beta') }}</p>
            <h1>{{ content('title') }}</h1>
            <p class="public-lead">{{ content('summary') }}</p>
            <div class="public-actions">
                <PublicAction
                    :can-register="canRegister"
                    :workspace-url="feature.workspaceUrl"
                /><a
                    href="#how-it-works"
                    class="public-button public-button-secondary"
                    >{{ t('publicSite.how') }}</a
                >
            </div>
            <p class="public-note">{{ t('publicSite.accountNote') }}</p>
        </section>
        <section class="public-example">
            <p class="public-eyebrow">{{ t('publicSite.example') }}</p>
            <h2>{{ content('example') }}</h2>
            <p>{{ content('audience') }}</p>
        </section>
        <section class="public-section public-two-column">
            <article>
                <p class="public-eyebrow">{{ t('publicSite.inputLabel') }}</p>
                <h2>{{ t('publicSite.inputTitle') }}</h2>
                <p class="public-body">{{ content('input') }}</p>
            </article>
            <article>
                <p class="public-eyebrow">{{ t('publicSite.resultLabel') }}</p>
                <h2>{{ t('publicSite.resultTitle') }}</h2>
                <p class="public-body">{{ content('result') }}</p>
            </article>
        </section>
        <section
            id="how-it-works"
            class="public-section"
            aria-labelledby="steps-heading"
        >
            <h2 id="steps-heading">{{ t('publicSite.how') }}</h2>
            <ol class="public-steps">
                <li v-for="step in [1, 2, 3]" :key="step">
                    <span class="public-number" aria-hidden="true"
                        >0{{ step }}</span
                    >
                    <p>{{ content(`step${step}`) }}</p>
                </li>
            </ol>
        </section>
        <section class="public-limit">
            <h2>{{ t('publicSite.limitTitle') }}</h2>
            <p>{{ content('limit') }}</p>
        </section>
        <section class="public-cta">
            <div>
                <h2>{{ t('publicSite.ctaTitle') }}</h2>
                <p>{{ t('publicSite.freeNote') }}</p>
            </div>
            <PublicAction
                :can-register="canRegister"
                :workspace-url="feature.workspaceUrl"
            />
        </section>
        <section class="public-section">
            <div class="public-section-heading">
                <h2>{{ t('publicSite.related') }}</h2>
                <Link href="/features" class="public-text-link"
                    >{{ t('publicSite.allFeatures') }} &rarr;</Link
                >
            </div>
            <PublicFeatureCards
                :features="
                    features
                        .filter((item) => item.slug !== feature.slug)
                        .slice(0, 3)
                "
            />
        </section>
    </PublicLayout>
</template>
