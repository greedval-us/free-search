<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import en from '@/locales/en';
import ru from '@/locales/ru';
import { home } from '@/routes';

type PrivacyContent = {
    metaTitle: string;
    eyebrow: string;
    title: string;
    subtitle: string;
    lastUpdated: string;
    noticeTitle: string;
    noticeText: string;
    backHome: string;
    sectionLabels: Record<string, string>;
    general: string[];
    operator: string[];
    data: string[];
    purposes: string[];
    legalBasis: string[];
    cookies: string[];
    sharing: string[];
    rights: string[];
    security: string[];
    retention: string[];
    contacts: string[];
    changes: string[];
};

const { locale, setLocale } = useI18n();

const content = computed<PrivacyContent>(
    () => (locale.value === 'ru' ? ru.privacy : en.privacy) as PrivacyContent
);

const sections = computed(() => [
    { key: 'general', items: content.value.general },
    //{ key: 'operator', items: content.value.operator },
    { key: 'data', items: content.value.data },
    { key: 'purposes', items: content.value.purposes },
    { key: 'legalBasis', items: content.value.legalBasis },
    { key: 'cookies', items: content.value.cookies },
    { key: 'sharing', items: content.value.sharing },
    { key: 'rights', items: content.value.rights },
    { key: 'security', items: content.value.security },
    { key: 'retention', items: content.value.retention },
    { key: 'contacts', items: content.value.contacts },
    { key: 'changes', items: content.value.changes },
]);
</script>

<template>
    <Head :title="content.metaTitle" />

    <div class="privacy-root">
        <div class="privacy-ambient privacy-ambient-a" />
        <div class="privacy-ambient privacy-ambient-b" />

        <div class="privacy-shell">
            <header class="privacy-topbar">
                <Link :href="home()" class="privacy-home-link">
                    {{ content.backHome }}
                </Link>

                <button
                    type="button"
                    class="privacy-locale-btn"
                    @click="setLocale(locale === 'ru' ? 'en' : 'ru')"
                >
                    {{ locale.toUpperCase() }}
                </button>
            </header>

            <main class="privacy-content">
                <section class="privacy-hero">
                    <p class="privacy-eyebrow">{{ content.eyebrow }}</p>
                    <h1 class="privacy-title">{{ content.title }}</h1>
                    <p class="privacy-subtitle">{{ content.subtitle }}</p>
                    <p class="privacy-updated">{{ content.lastUpdated }}</p>
                </section>

                <section
                    v-for="section in sections"
                    :key="section.key"
                    class="privacy-section"
                >
                    <h2>{{ content.sectionLabels[section.key] }}</h2>
                    <ul class="privacy-list">
                        <li
                            v-for="item in section.items"
                            :key="`${section.key}-${item}`"
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
.privacy-root {
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

.privacy-ambient {
    position: absolute;
    border-radius: 9999px;
    filter: blur(100px);
    opacity: 0.3;
    pointer-events: none;
}

.privacy-ambient-a {
    top: -8rem;
    left: -6rem;
    width: 24rem;
    height: 24rem;
    background: #14b8a6;
}

.privacy-ambient-b {
    right: -6rem;
    bottom: 12rem;
    width: 22rem;
    height: 22rem;
    background: #f59e0b;
}

.privacy-shell {
    position: relative;
    margin: 0 auto;
    max-width: 76rem;
    padding: 1.25rem 1rem 6rem;
}

.privacy-topbar {
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

.privacy-home-link,
.privacy-locale-btn {
    color: #d8e6fb;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
}

.privacy-locale-btn {
    border: 1px solid rgba(148, 163, 184, 0.24);
    border-radius: 0.85rem;
    background: rgba(15, 23, 42, 0.68);
    padding: 0.55rem 0.8rem;
    cursor: pointer;
}

.privacy-content {
    display: grid;
    gap: 1rem;
}

.privacy-hero,
.privacy-note,
.privacy-section {
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 1.25rem;
    background: rgba(9, 15, 29, 0.78);
    padding: 1.25rem;
    backdrop-filter: blur(14px);
    box-shadow: 0 26px 80px -48px rgba(2, 6, 23, 0.75);
}

.privacy-eyebrow {
    margin: 0;
    color: #7dd3fc;
    letter-spacing: 0.18em;
    font-size: 0.73rem;
    font-weight: 700;
}

.privacy-title {
    margin: 0.7rem 0 0;
    font-size: clamp(1.8rem, 4vw, 3.1rem);
    line-height: 1.05;
}

.privacy-subtitle {
    margin: 0.9rem 0 0;
    max-width: 48rem;
    color: #c7d3ea;
    line-height: 1.6;
}

.privacy-updated {
    margin: 1rem 0 0;
    color: #9fb2d7;
    font-size: 0.92rem;
}

.privacy-note {
    border-color: rgba(45, 212, 191, 0.24);
    background: rgba(7, 22, 26, 0.82);
}

.privacy-note h2,
.privacy-section h2 {
    margin: 0;
    font-size: 1.04rem;
}

.privacy-note p {
    margin: 0.7rem 0 0;
    color: #cfe4de;
    line-height: 1.6;
}

.privacy-list {
    margin: 0.85rem 0 0;
    padding: 0;
    list-style: none;
    display: grid;
    gap: 0.7rem;
}

.privacy-list li {
    position: relative;
    padding-left: 1rem;
    color: #d7e0f2;
    line-height: 1.62;
}

.privacy-list li::before {
    content: '';
    position: absolute;
    top: 0.65rem;
    left: 0;
    width: 0.4rem;
    height: 0.4rem;
    border-radius: 9999px;
    background: #2dd4bf;
    box-shadow: 0 0 0 4px rgba(45, 212, 191, 0.15);
}

@media (max-width: 640px) {
    .privacy-shell {
        padding: 0.9rem 0.8rem 6rem;
    }

    .privacy-topbar {
        padding: 0.75rem 0.85rem;
    }

    .privacy-hero,
    .privacy-note,
    .privacy-section {
        padding: 1rem;
    }
}
</style>
