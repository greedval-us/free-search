<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import SeoHead from '@/components/SeoHead.vue';
import { useI18n } from '@/composables/useI18n';
import { useSeoPage } from '@/composables/useSeoPage';
import en from '@/locales/en';
import ru from '@/locales/ru';
import { home } from '@/routes';

type TermsSection = {
    title: string;
    items: string[];
};

type TermsContent = {
    metaTitle: string;
    eyebrow: string;
    title: string;
    subtitle: string;
    lastUpdated: string;
    backHome: string;
    sections: Record<string, TermsSection>;
};

const { locale, setLocale } = useI18n();
const seo = useSeoPage('terms', locale);

const content = computed<TermsContent>(
    () => (locale.value === 'ru' ? ru.terms : en.terms) as TermsContent
);

const sections = computed(() => Object.values(content.value.sections));
</script>

<template>
    <SeoHead
        :title="seo.title"
        :description="seo.description"
        :path="seo.path"
    />

    <div class="terms-root">
        <div class="terms-ambient terms-ambient-a" />
        <div class="terms-ambient terms-ambient-b" />

        <div class="terms-shell">
            <header class="terms-topbar">
                <Link :href="home()" class="terms-home-link">
                    {{ content.backHome }}
                </Link>

                <button
                    type="button"
                    class="terms-locale-btn"
                    @click="setLocale(locale === 'ru' ? 'en' : 'ru')"
                >
                    {{ locale.toUpperCase() }}
                </button>
            </header>

            <main class="terms-content">
                <section class="terms-hero">
                    <p class="terms-eyebrow">{{ content.eyebrow }}</p>
                    <h1 class="terms-title">{{ content.title }}</h1>
                    <p class="terms-subtitle">{{ content.subtitle }}</p>
                    <p class="terms-updated">{{ content.lastUpdated }}</p>
                </section>

                <section
                    v-for="section in sections"
                    :key="section.title"
                    class="terms-section"
                >
                    <h2>{{ section.title }}</h2>
                    <ul class="terms-list">
                        <li
                            v-for="item in section.items"
                            :key="`${section.title}-${item}`"
                        >
                            {{ item }}
                        </li>
                    </ul>
                </section>
            </main>
        </div>
    </div>
</template>

<style scoped>
.terms-root {
    position: relative;
    min-height: 100vh;
    overflow: hidden;
    background:
        radial-gradient(
            120% 140% at 10% 0%,
            #143252 0%,
            #0a1325 52%,
            #04070f 100%
        ),
        linear-gradient(180deg, #07101d 0%, #04070f 100%);
    color: #e7edf8;
}

.terms-ambient {
    position: absolute;
    border-radius: 9999px;
    filter: blur(100px);
    opacity: 0.3;
    pointer-events: none;
}

.terms-ambient-a {
    top: -8rem;
    left: -6rem;
    width: 24rem;
    height: 24rem;
    background: #22c55e;
}

.terms-ambient-b {
    right: -6rem;
    bottom: 12rem;
    width: 22rem;
    height: 22rem;
    background: #38bdf8;
}

.terms-shell {
    position: relative;
    margin: 0 auto;
    max-width: 76rem;
    padding: 1.25rem 1rem 6rem;
}

.terms-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.2rem;
    padding: 0.85rem 1rem;
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 1rem;
    background: rgba(8, 15, 28, 0.72);
    backdrop-filter: blur(14px);
}

.terms-home-link,
.terms-locale-btn {
    color: #d8e6fb;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
}

.terms-locale-btn {
    border: 1px solid rgba(148, 163, 184, 0.24);
    border-radius: 0.85rem;
    background: rgba(15, 23, 42, 0.68);
    padding: 0.55rem 0.8rem;
    cursor: pointer;
}

.terms-content {
    display: grid;
    gap: 1rem;
}

.terms-hero,
.terms-section {
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 1.25rem;
    background: rgba(9, 15, 29, 0.78);
    padding: 1.25rem;
    backdrop-filter: blur(14px);
    box-shadow: 0 26px 80px -48px rgba(2, 6, 23, 0.75);
}

.terms-eyebrow {
    margin: 0;
    color: #7dd3fc;
    letter-spacing: 0.18em;
    font-size: 0.73rem;
    font-weight: 700;
}

.terms-title {
    margin: 0.7rem 0 0;
    font-size: clamp(1.8rem, 4vw, 3.1rem);
    line-height: 1.05;
}

.terms-subtitle {
    margin: 0.9rem 0 0;
    max-width: 48rem;
    color: #c7d3ea;
    line-height: 1.6;
}

.terms-updated {
    margin: 1rem 0 0;
    color: #9fb2d7;
    font-size: 0.92rem;
}

.terms-section h2 {
    margin: 0;
    font-size: 1.04rem;
}

.terms-list {
    margin: 0.85rem 0 0;
    padding: 0;
    list-style: none;
    display: grid;
    gap: 0.7rem;
}

.terms-list li {
    position: relative;
    padding-left: 1rem;
    color: #d7e0f2;
    line-height: 1.62;
}

.terms-list li::before {
    content: '';
    position: absolute;
    top: 0.65rem;
    left: 0;
    width: 0.4rem;
    height: 0.4rem;
    border-radius: 9999px;
    background: #22c55e;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.15);
}

@media (max-width: 640px) {
    .terms-shell {
        padding: 0.9rem 0.8rem 6rem;
    }

    .terms-topbar {
        padding: 0.75rem 0.85rem;
    }

    .terms-hero,
    .terms-section {
        padding: 1rem;
    }
}
</style>
