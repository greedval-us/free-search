<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import SeoHead from '@/components/SeoHead.vue';
import { useLocale } from '@/composables/useLocale';
import { useSeoPage } from '@/composables/useSeoPage';
import { home } from '@/routes';

const props = defineProps<{
    page: 'privacy' | 'terms';
    content: {
        backHome: string;
        eyebrow: string;
        title: string;
        subtitle: string;
        lastUpdated: string;
    };
    sections: { title: string; items: string[] }[];
}>();

const { locale, setLocale } = useLocale();
const {
    path: seoPath,
    title: seoTitle,
    description: seoDescription,
} = useSeoPage(props.page, locale);
</script>

<template>
    <SeoHead :title="seoTitle" :description="seoDescription" :path="seoPath" />

    <div class="legal-root" :class="{ 'legal-privacy': page === 'privacy' }">
        <div class="legal-shell">
            <header class="legal-topbar">
                <Link :href="home()" class="legal-home-link">
                    {{ content.backHome }}
                </Link>

                <button
                    type="button"
                    class="legal-locale-btn"
                    @click="setLocale(locale === 'ru' ? 'en' : 'ru')"
                >
                    {{ locale.toUpperCase() }}
                </button>
            </header>

            <main class="legal-content">
                <section class="legal-hero">
                    <p class="legal-eyebrow">{{ content.eyebrow }}</p>
                    <h1 class="legal-title">{{ content.title }}</h1>
                    <p class="legal-subtitle">{{ content.subtitle }}</p>
                    <p class="legal-updated">{{ content.lastUpdated }}</p>
                </section>

                <section
                    v-for="section in sections"
                    :key="section.title"
                    class="legal-section"
                >
                    <h2>{{ section.title }}</h2>
                    <ul class="legal-list">
                        <li v-for="item in section.items" :key="item">
                            {{ item }}
                        </li>
                    </ul>
                </section>
            </main>
        </div>
    </div>
</template>

<style scoped>
.legal-root {
    --legal-marker: 34, 197, 94;
    position: relative;
    min-height: 100vh;
    background: #07101f;
    color: #e7edf8;
    overflow-wrap: anywhere;
}

.legal-privacy {
    --legal-marker: 45, 212, 191;
}

.legal-shell {
    position: relative;
    margin: 0 auto;
    max-width: 76rem;
    padding: 1.25rem 1rem 6rem;
}

.legal-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.2rem;
    padding: 0.85rem 1rem;
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 1rem;
    background: #0b1628;
}

.legal-home-link,
.legal-locale-btn {
    color: #d8e6fb;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
}

.legal-locale-btn {
    border: 1px solid rgba(148, 163, 184, 0.24);
    border-radius: 0.85rem;
    background: rgba(15, 23, 42, 0.68);
    padding: 0.55rem 0.8rem;
    cursor: pointer;
}

.legal-content {
    display: grid;
    gap: 1rem;
}

.legal-hero,
.legal-section {
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 1rem;
    background: #0d192b;
    padding: 1.25rem;
}

.legal-eyebrow {
    margin: 0;
    color: #7dd3fc;
    letter-spacing: 0.18em;
    font-size: 0.73rem;
    font-weight: 700;
}

.legal-title {
    margin: 0.7rem 0 0;
    font-size: clamp(1.8rem, 4vw, 3.1rem);
    line-height: 1.05;
}

.legal-subtitle {
    margin: 0.9rem 0 0;
    max-width: 48rem;
    color: #c7d3ea;
    line-height: 1.6;
}

.legal-updated {
    margin: 1rem 0 0;
    color: #9fb2d7;
    font-size: 0.92rem;
}

.legal-section h2 {
    margin: 0;
    font-size: 1.04rem;
}

.legal-list {
    margin: 0.85rem 0 0;
    padding: 0;
    list-style: none;
    display: grid;
    gap: 0.7rem;
}

.legal-list li {
    position: relative;
    padding-left: 1rem;
    color: #d7e0f2;
    line-height: 1.62;
}

.legal-list li::before {
    content: '';
    position: absolute;
    top: 0.65rem;
    left: 0;
    width: 0.4rem;
    height: 0.4rem;
    border-radius: 9999px;
    background: rgb(var(--legal-marker));
    box-shadow: 0 0 0 4px rgba(var(--legal-marker), 0.15);
}

@media (max-width: 640px) {
    .legal-shell {
        padding: 0.9rem 0.8rem 6rem;
    }

    .legal-topbar {
        padding: 0.75rem 0.85rem;
    }

    .legal-hero,
    .legal-section {
        padding: 1rem;
    }
}
</style>
