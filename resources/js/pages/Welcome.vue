<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import CookieNotice from '@/components/CookieNotice.vue';
import SeoHead from '@/components/SeoHead.vue';
import { useI18n } from '@/composables/useI18n';
import { useSeoPage } from '@/composables/useSeoPage';
import { dashboard, login, register } from '@/routes';

type LandingModule = {
    title: string;
    href: string;
    summary: string;
    badges: string[];
};

type LandingStep = {
    title: string;
    text: string;
};

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    }
);

const { locale, setLocale, t } = useI18n();
const {
    path: seoPath,
    title: seoTitle,
    description: seoDescription,
} = useSeoPage('welcome', locale);

const copy = computed(() => {
    if (locale.value === 'ru') {
        return {
            headTitle: 'Uraboros | Intelligence Workspace',
            brand: 'Uraboros Intelligence Workspace',
            badge: 'OSINT WORKSPACE',
            heroTitle:
                'Рабочее пространство для поиска, проверки и аналитики без прыжков между сервисами',
            heroText:
                'В одном интерфейсе уже доступны модули для Telegram, YouTube, Bluesky, Mastodon, Site Intel, News & Media Intel и Shifr. Dashboard сохраняет рабочий контекст, а Wiki помогает быстро понять, что делает каждый модуль и какие данные ему нужны.',
            ctaMain: 'Открыть рабочее пространство',
            ctaAlt: 'Создать аккаунт',
            signIn: 'Войти',
            dashboard: 'Панель',
            register: 'Регистрация',
            localeToggleLabel: 'Переключить язык интерфейса',
            modulesTitle: 'Что уже доступно',
            modulesText:
                'Каждый модуль решает понятный рабочий сценарий: поиск, аналитика, парсинг или техническая проверка.',
            modules: [
                {
                    title: 'Telegram',
                    href: '/telegram',
                    summary:
                        'Поиск по каналам, аналитика активности и парсинг сообщений с отчетами.',
                    badges: ['Search', 'Analytics', 'Parser'],
                },
                {
                    title: 'YouTube',
                    href: '/youtube',
                    summary:
                        'Поиск видео и каналов, аналитика по контенту и сбор комментариев.',
                    badges: ['Search', 'Analytics', 'Comments'],
                },
                {
                    title: 'Bluesky',
                    href: '/bluesky',
                    summary:
                        'Поиск постов и профилей, раскрытие связей и аналитика по темам.',
                    badges: ['Posts', 'Profiles', 'Analytics'],
                },
                {
                    title: 'Mastodon',
                    href: '/mastodon',
                    summary:
                        'Поиск по аккаунтам, постам и хэштегам с разбором контекста обсуждений.',
                    badges: ['Accounts', 'Hashtags', 'Context'],
                },
                {
                    title: 'Site Intel',
                    href: '/site-intel',
                    summary:
                        'Проверка сайта, домена и технических рисков: DNS, SSL, SEO и базовая аналитика.',
                    badges: ['Domain', 'Security', 'SEO'],
                },
                {
                    title: 'News & Media Intel',
                    href: '/news-media-intel',
                    summary:
                        'Мониторинг новостей и медиа по теме, бренду, персоне или событию.',
                    badges: ['Mentions', 'Timeline', 'Topics'],
                },
                {
                    title: 'Shifr',
                    href: '/shifr',
                    summary:
                        'Утилиты для хеширования, преобразований, IOC и проверки JWT.',
                    badges: ['Hash', 'Transform', 'IOC'],
                },
            ] satisfies LandingModule[],
            workflowTitle: 'Как это используется в работе',
            workflowSteps: [
                {
                    title: 'Найти сигнал',
                    text: 'Начните с поиска по Telegram, YouTube, Bluesky, Mastodon или новостям и быстро соберите первичную выборку.',
                },
                {
                    title: 'Проверить контекст',
                    text: 'Уточните источник, автора, временной интервал, реакции, обсуждения и технические признаки.',
                },
                {
                    title: 'Зафиксировать результат',
                    text: 'Возвращайтесь к кейсам через Dashboard, сохраняйте запросы и опирайтесь на Wiki как на понятную рабочую справку.',
                },
            ] satisfies LandingStep[],
            workspaceTitle: 'Что делает сайт полезным в ежедневной работе',
            workspaceItems: [
                'Модули говорят на одном языке интерфейса и не требуют заново разбираться в каждом экране.',
                'Формы, подсказки и результаты выровнены между направлениями, поэтому переключаться между задачами проще.',
                'Dashboard собирает историю действий и сохраненные запросы, а Wiki снижает порог входа для команды.',
            ],
            supportTitle: 'Поддерживающие инструменты',
            supportCards: [
                {
                    title: 'Dashboard',
                    text: 'История запросов, сохраненные действия, быстрый возврат к рабочим сценариям.',
                },
                {
                    title: 'Modules Wiki',
                    text: 'Понятное объяснение, что делает каждый модуль, когда его использовать и что нужно указать на входе.',
                },
            ],
            metrics: [
                { value: '7', label: 'живых модулей для прикладной работы' },
                {
                    value: '1',
                    label: 'единый интерфейс вместо набора разрозненных инструментов',
                },
                {
                    value: '2',
                    label: 'рабочих слоя поддержки: Dashboard и Wiki',
                },
            ],
            finalTitle: 'Платформа уже готова к реальным рабочим сценариям',
            finalText:
                'Если вам нужен не "демо-лендинг", а понятное рабочее пространство с реальными модулями и общим контекстом анализа, здесь уже есть с чего начать.',
        };
    }

    return {
        headTitle: 'Uraboros | Intelligence Workspace',
        brand: 'Uraboros Intelligence Workspace',
        badge: 'OSINT WORKSPACE',
        heroTitle:
            'A workspace for search, validation, and intelligence tasks without jumping between disconnected tools',
        heroText:
            'The platform already includes Telegram, YouTube, Bluesky, Mastodon, Site Intel, News & Media Intel, and Shifr. Dashboard keeps the working context, while the Wiki explains what each module does and what input it expects.',
        ctaMain: 'Open workspace',
        ctaAlt: 'Create account',
        signIn: 'Sign in',
        dashboard: 'Dashboard',
        register: 'Register',
        localeToggleLabel: 'Switch interface language',
        modulesTitle: 'What is already available',
        modulesText:
            'Each module supports a clear workflow such as search, analytics, parsing, or technical validation.',
        modules: [
            {
                title: 'Telegram',
                href: '/telegram',
                summary:
                    'Channel search, activity analytics, and message parsing with reports.',
                badges: ['Search', 'Analytics', 'Parser'],
            },
            {
                title: 'YouTube',
                href: '/youtube',
                summary:
                    'Video and channel search, content analytics, and comment collection.',
                badges: ['Search', 'Analytics', 'Comments'],
            },
            {
                title: 'Bluesky',
                href: '/bluesky',
                summary:
                    'Post and profile search with relationship views and topic analytics.',
                badges: ['Posts', 'Profiles', 'Analytics'],
            },
            {
                title: 'Mastodon',
                href: '/mastodon',
                summary:
                    'Search across accounts, posts, and hashtags with discussion context.',
                badges: ['Accounts', 'Hashtags', 'Context'],
            },
            {
                title: 'Site Intel',
                href: '/site-intel',
                summary:
                    'Website and domain checks covering DNS, SSL, SEO, and technical risk signals.',
                badges: ['Domain', 'Security', 'SEO'],
            },
            {
                title: 'News & Media Intel',
                href: '/news-media-intel',
                summary:
                    'News and media monitoring by topic, brand, person, or event.',
                badges: ['Mentions', 'Timeline', 'Topics'],
            },
            {
                title: 'Shifr',
                href: '/shifr',
                summary:
                    'Utilities for hashing, transformations, IOC extraction, and JWT checks.',
                badges: ['Hash', 'Transform', 'IOC'],
            },
        ] satisfies LandingModule[],
        workflowTitle: 'How teams use it',
        workflowSteps: [
            {
                title: 'Find a signal',
                text: 'Start with search across Telegram, YouTube, Bluesky, Mastodon, or public news to build the initial dataset.',
            },
            {
                title: 'Validate the context',
                text: 'Review source, author, time window, reactions, discussions, and technical indicators in one flow.',
            },
            {
                title: 'Keep the result',
                text: 'Return to cases through Dashboard, save queries, and use the Wiki as a practical reference instead of internal jargon.',
            },
        ] satisfies LandingStep[],
        workspaceTitle: 'Why the site is useful in daily work',
        workspaceItems: [
            'Modules follow one UI language, so users do not need to relearn every screen from scratch.',
            'Forms, hints, and result views are aligned across modules, which makes switching tasks easier.',
            'Dashboard keeps history and saved queries, while the Wiki lowers the onboarding cost for the team.',
        ],
        supportTitle: 'Supporting tools',
        supportCards: [
            {
                title: 'Dashboard',
                text: 'Query history, saved actions, and a quick way back to active workflows.',
            },
            {
                title: 'Modules Wiki',
                text: 'A plain-language explanation of what each module does, when to use it, and what input it needs.',
            },
        ],
        metrics: [
            { value: '7', label: 'live modules for practical work' },
            {
                value: '1',
                label: 'shared interface instead of scattered tools',
            },
            { value: '2', label: 'support layers: Dashboard and Wiki' },
        ],
        finalTitle: 'The platform already fits real working scenarios',
        finalText:
            'If you need a practical workspace with real modules and a shared analysis context rather than a generic landing page, there is already enough here to start working.',
    };
});

const toggleLocale = () => setLocale(locale.value === 'ru' ? 'en' : 'ru');
</script>

<template>
    <SeoHead :title="seoTitle" :description="seoDescription" :path="seoPath" />

    <div class="page-root">
        <div class="bg-layer bg-layer-a" />
        <div class="bg-layer bg-layer-b" />
        <div class="mesh-overlay" />

        <div class="content-wrap">
            <header class="topbar">
                <div>
                    <p class="brand-mark">URABOROS</p>
                    <h1 class="brand-title">{{ copy.brand }}</h1>
                </div>

                <div class="topbar-actions">
                    <button
                        type="button"
                        class="locale-btn"
                        :aria-label="copy.localeToggleLabel"
                        @click="toggleLocale"
                    >
                        {{ locale.toUpperCase() }}
                    </button>

                    <Link href="/privacy" class="btn btn-ghost">
                        {{ t('common.privacyPolicy') }}
                    </Link>

                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="btn btn-outline"
                    >
                        {{ copy.dashboard }}
                    </Link>

                    <template v-else>
                        <Link :href="login()" class="btn btn-ghost">
                            {{ copy.signIn }}
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="btn btn-outline"
                        >
                            {{ copy.register }}
                        </Link>
                    </template>
                </div>
            </header>

            <main class="layout">
                <section class="hero panel rise">
                    <div class="hero-copy">
                        <p class="badge">{{ copy.badge }}</p>
                        <h2 class="hero-title">{{ copy.heroTitle }}</h2>
                        <p class="hero-text">{{ copy.heroText }}</p>

                        <div class="hero-actions">
                            <Link
                                :href="
                                    $page.props.auth.user
                                        ? dashboard()
                                        : login()
                                "
                                class="btn btn-solid"
                            >
                                {{ copy.ctaMain }}
                            </Link>
                            <Link
                                v-if="!$page.props.auth.user && canRegister"
                                :href="register()"
                                class="btn btn-ghost"
                            >
                                {{ copy.ctaAlt }}
                            </Link>
                        </div>
                    </div>

                    <div class="hero-metrics">
                        <div
                            v-for="metric in copy.metrics"
                            :key="metric.label"
                            class="metric"
                        >
                            <p class="metric-value">{{ metric.value }}</p>
                            <p class="metric-label">{{ metric.label }}</p>
                        </div>
                    </div>
                </section>

                <section class="panel fade-in">
                    <div class="section-head">
                        <h3 class="section-title">{{ copy.modulesTitle }}</h3>
                        <p class="section-text">{{ copy.modulesText }}</p>
                    </div>

                    <div class="modules-grid">
                        <Link
                            v-for="module in copy.modules"
                            :key="module.title"
                            :href="
                                $page.props.auth.user ? module.href : login()
                            "
                            class="module-card"
                        >
                            <div class="module-head">
                                <h4>{{ module.title }}</h4>
                                <span class="module-link">{{
                                    $page.props.auth.user
                                        ? copy.ctaMain
                                        : copy.signIn
                                }}</span>
                            </div>
                            <p class="module-summary">{{ module.summary }}</p>
                            <div class="module-badges">
                                <span
                                    v-for="badge in module.badges"
                                    :key="`${module.title}-${badge}`"
                                    class="module-badge"
                                >
                                    {{ badge }}
                                </span>
                            </div>
                        </Link>
                    </div>
                </section>

                <section class="panel fade-in">
                    <h3 class="section-title">{{ copy.workflowTitle }}</h3>
                    <div class="workflow-grid">
                        <article
                            v-for="(step, index) in copy.workflowSteps"
                            :key="step.title"
                            class="workflow-card"
                        >
                            <p class="workflow-index">0{{ index + 1 }}</p>
                            <h4>{{ step.title }}</h4>
                            <p>{{ step.text }}</p>
                        </article>
                    </div>
                </section>

                <section class="split">
                    <article class="panel fade-in">
                        <h3 class="section-title">{{ copy.workspaceTitle }}</h3>
                        <ul class="list">
                            <li v-for="item in copy.workspaceItems" :key="item">
                                {{ item }}
                            </li>
                        </ul>
                    </article>

                    <article class="panel fade-in">
                        <h3 class="section-title">{{ copy.supportTitle }}</h3>
                        <div class="support-grid">
                            <div
                                v-for="card in copy.supportCards"
                                :key="card.title"
                                class="support-card"
                            >
                                <h4>{{ card.title }}</h4>
                                <p>{{ card.text }}</p>
                            </div>
                        </div>
                    </article>
                </section>

                <section class="panel cta-panel rise">
                    <h3>{{ copy.finalTitle }}</h3>
                    <p>{{ copy.finalText }}</p>
                    <div class="hero-actions">
                        <Link
                            :href="
                                $page.props.auth.user ? dashboard() : login()
                            "
                            class="btn btn-solid"
                        >
                            {{ copy.ctaMain }}
                        </Link>
                        <Link
                            v-if="!$page.props.auth.user && canRegister"
                            :href="register()"
                            class="btn btn-ghost"
                        >
                            {{ copy.ctaAlt }}
                        </Link>
                    </div>
                </section>
            </main>
        </div>

        <CookieNotice />
    </div>
</template>

<style scoped>
.page-root {
    position: relative;
    min-height: 100vh;
    overflow: hidden;
    background:
        radial-gradient(
            120% 140% at 20% 0%,
            #153053 0%,
            #091121 53%,
            #04070f 100%
        ),
        linear-gradient(180deg, #08101d 0%, #04070f 100%);
    color: #e6ecff;
    font-family: 'Space Grotesk', 'Manrope', 'Segoe UI', sans-serif;
    perspective: 1200px;
}

.bg-layer {
    position: absolute;
    border-radius: 9999px;
    filter: blur(96px);
    opacity: 0.42;
    pointer-events: none;
    animation: ambient-float 14s ease-in-out infinite alternate;
}

.bg-layer-a {
    width: 34rem;
    height: 34rem;
    top: -8rem;
    left: -6rem;
    background: #14b8a6;
}

.bg-layer-b {
    width: 28rem;
    height: 28rem;
    right: -8rem;
    bottom: -6rem;
    background: #f59e0b;
    animation-delay: -4s;
}

.mesh-overlay {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255, 255, 255, 0.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
    background-size: 44px 44px;
    mask-image: radial-gradient(circle at center, black 45%, transparent 100%);
    pointer-events: none;
    opacity: 0.7;
}

.content-wrap {
    position: relative;
    margin: 0 auto;
    max-width: 1220px;
    padding: 2rem 1.2rem 2.6rem;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.3rem;
    padding: 0.9rem 1rem;
    border: 1px solid rgba(148, 163, 184, 0.16);
    border-radius: 1.25rem;
    background: linear-gradient(
        180deg,
        rgba(9, 15, 29, 0.82),
        rgba(9, 15, 29, 0.56)
    );
    box-shadow: 0 30px 80px -48px rgba(0, 0, 0, 0.65);
    backdrop-filter: blur(16px);
    position: sticky;
    top: 1rem;
    z-index: 10;
}

.topbar::after {
    content: '';
    position: absolute;
    inset: auto 1.25rem 0;
    height: 1px;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(125, 211, 252, 0.52),
        transparent
    );
}

.brand-mark {
    margin: 0;
    letter-spacing: 0.35em;
    font-size: 0.66rem;
    color: #7dd3fc;
}

.brand-title {
    margin: 0.55rem 0 0;
    font-size: 1.2rem;
    font-weight: 650;
}

.topbar-actions {
    display: flex;
    align-items: center;
    gap: 0.55rem;
}

.locale-btn,
.btn {
    border-radius: 0.95rem;
    border: 1px solid rgba(148, 163, 184, 0.28);
    color: #e8edf8;
    font-size: 0.86rem;
    font-weight: 560;
    text-decoration: none;
    transition:
        transform 0.2s ease,
        border-color 0.2s ease,
        background-color 0.2s ease;
}

.locale-btn {
    cursor: pointer;
    padding: 0.56rem 0.75rem;
    background: rgba(15, 23, 42, 0.65);
}

.btn {
    padding: 0.6rem 0.95rem;
}

.btn-ghost {
    background: rgba(11, 18, 34, 0.74);
}

.btn-outline {
    background: rgba(20, 184, 166, 0.15);
    border-color: rgba(45, 212, 191, 0.45);
}

.btn-solid {
    background: linear-gradient(135deg, #14b8a6, #2dd4bf);
    color: #041018;
    border-color: transparent;
    box-shadow:
        0 18px 34px rgba(45, 212, 191, 0.24),
        inset 0 1px 0 rgba(255, 255, 255, 0.24);
}

.btn:hover,
.locale-btn:hover,
.module-card:hover {
    transform: translateY(-2px) scale(1.01);
    border-color: rgba(125, 211, 252, 0.62);
}

.layout {
    display: grid;
    gap: 1rem;
}

.panel {
    border: 1px solid rgba(148, 163, 184, 0.2);
    background: linear-gradient(
        155deg,
        rgba(18, 28, 49, 0.94),
        rgba(9, 14, 27, 0.78)
    );
    border-radius: 1.35rem;
    padding: 1.2rem;
    backdrop-filter: blur(14px);
    box-shadow:
        0 30px 85px -48px rgba(3, 7, 18, 0.8),
        inset 0 1px 0 rgba(255, 255, 255, 0.05);
    position: relative;
    overflow: hidden;
}

.panel::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(
        130deg,
        rgba(255, 255, 255, 0.08),
        transparent 28%,
        transparent 62%,
        rgba(56, 189, 248, 0.08)
    );
    pointer-events: none;
}

.panel::after {
    content: '';
    position: absolute;
    inset: auto 0 0;
    height: 0.9rem;
    background: radial-gradient(
        60% 120% at 50% 0%,
        rgba(34, 211, 238, 0.12),
        transparent 72%
    );
    pointer-events: none;
}

.hero {
    display: grid;
    gap: 1rem;
    position: relative;
}

.hero-copy {
    min-width: 0;
}

.hero-title {
    margin: 0.9rem 0 0;
    font-size: clamp(1.7rem, 4.2vw, 3rem);
    line-height: 1.04;
    max-width: 14ch;
    text-wrap: balance;
}

.hero-text {
    margin-top: 0.8rem;
    max-width: 760px;
    color: #c9d4ee;
    line-height: 1.62;
    font-size: 0.98rem;
}

.badge {
    display: inline-flex;
    margin: 0;
    border: 1px solid rgba(45, 212, 191, 0.5);
    border-radius: 999px;
    background: rgba(20, 184, 166, 0.12);
    color: #99f6e4;
    padding: 0.24rem 0.62rem;
    letter-spacing: 0.1em;
    font-size: 0.69rem;
    font-weight: 650;
}

.hero-actions {
    margin-top: 1rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.55rem;
}

.hero-metrics {
    display: grid;
    gap: 0.7rem;
    align-content: end;
}

.metric {
    border: 1px solid rgba(148, 163, 184, 0.2);
    border-radius: 1rem;
    background: linear-gradient(
        180deg,
        rgba(11, 18, 34, 0.72),
        rgba(7, 13, 25, 0.5)
    );
    padding: 0.9rem;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
    transform: translateZ(0);
}

.metric-value {
    margin: 0;
    color: #7dd3fc;
    font-size: 1.45rem;
    font-weight: 670;
}

.metric-label {
    margin: 0.24rem 0 0;
    color: #cad3e9;
    font-size: 0.84rem;
    line-height: 1.45;
}

.section-head {
    display: grid;
    gap: 0.35rem;
}

.section-title {
    margin: 0;
    font-size: 1.08rem;
}

.section-text {
    margin: 0;
    color: #bfd0ef;
    font-size: 0.92rem;
    line-height: 1.52;
}

.modules-grid {
    margin-top: 1rem;
    display: grid;
    gap: 0.9rem;
}

.module-card {
    display: grid;
    gap: 0.8rem;
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 1rem;
    background: linear-gradient(
        180deg,
        rgba(8, 13, 25, 0.68),
        rgba(6, 11, 22, 0.5)
    );
    padding: 1rem;
    text-decoration: none;
    color: inherit;
    transition:
        transform 0.2s ease,
        border-color 0.2s ease,
        background-color 0.2s ease,
        box-shadow 0.2s ease;
    box-shadow: 0 16px 34px -28px rgba(0, 0, 0, 0.55);
}

.module-card:hover,
.workflow-card:hover,
.support-card:hover,
.metric:hover {
    box-shadow:
        0 22px 44px -30px rgba(4, 10, 24, 0.7),
        0 8px 20px -16px rgba(34, 211, 238, 0.28);
}

.module-card:hover {
    transform: translateY(-4px);
    border-color: rgba(125, 211, 252, 0.34);
}

.module-head {
    display: flex;
    justify-content: space-between;
    gap: 0.8rem;
    align-items: start;
}

.module-head h4 {
    margin: 0;
    font-size: 1rem;
}

.module-link {
    color: #8be7da;
    font-size: 0.74rem;
    white-space: nowrap;
}

.module-summary {
    margin: 0;
    color: #c8d5f0;
    font-size: 0.9rem;
    line-height: 1.55;
}

.module-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
}

.module-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    border: 1px solid rgba(125, 211, 252, 0.18);
    background: rgba(15, 23, 42, 0.72);
    padding: 0.26rem 0.58rem;
    color: #dbe8ff;
    font-size: 0.76rem;
}

.workflow-grid {
    margin-top: 1rem;
    display: grid;
    gap: 0.9rem;
}

.workflow-card {
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 1rem;
    background: linear-gradient(
        180deg,
        rgba(10, 16, 30, 0.72),
        rgba(6, 11, 22, 0.48)
    );
    padding: 1rem;
    transition:
        transform 0.24s ease,
        box-shadow 0.24s ease,
        border-color 0.24s ease;
}

.workflow-card:hover {
    transform: translateY(-4px);
    border-color: rgba(125, 211, 252, 0.42);
}

.workflow-index {
    margin: 0;
    color: #87e4d8;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.08em;
}

.workflow-card h4 {
    margin: 0.45rem 0 0;
    font-size: 1rem;
}

.workflow-card p:last-child {
    margin: 0.55rem 0 0;
    color: #c9d4ee;
    font-size: 0.9rem;
    line-height: 1.55;
}

.split {
    display: grid;
    gap: 1rem;
}

.list {
    margin: 0.9rem 0 0;
    padding: 0;
    list-style: none;
    display: grid;
    gap: 0.52rem;
}

.list li {
    color: #d7e0f5;
    font-size: 0.93rem;
    line-height: 1.5;
    padding-left: 1.05rem;
    position: relative;
}

.list li::before {
    content: '';
    width: 0.42rem;
    height: 0.42rem;
    border-radius: 999px;
    background: #2dd4bf;
    box-shadow: 0 0 0 4px rgba(45, 212, 191, 0.2);
    position: absolute;
    left: 0;
    top: 0.45rem;
}

.support-grid {
    margin-top: 1rem;
    display: grid;
    gap: 0.8rem;
}

.support-card {
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 1rem;
    background: linear-gradient(
        180deg,
        rgba(10, 16, 30, 0.72),
        rgba(6, 11, 22, 0.48)
    );
    padding: 1rem;
    transition:
        transform 0.24s ease,
        border-color 0.24s ease,
        box-shadow 0.24s ease;
}

.support-card:hover {
    transform: translateY(-4px);
    border-color: rgba(125, 211, 252, 0.4);
}

.support-card h4 {
    margin: 0;
    font-size: 0.98rem;
}

.support-card p {
    margin: 0.5rem 0 0;
    color: #c9d4ee;
    font-size: 0.9rem;
    line-height: 1.5;
}

.cta-panel h3 {
    margin: 0;
    font-size: 1.22rem;
}

.cta-panel p {
    margin: 0.65rem 0 0;
    color: #d2ddf4;
    line-height: 1.58;
    font-size: 0.93rem;
    max-width: 760px;
}

.rise {
    animation: rise-in 0.58s ease both;
}

.fade-in {
    animation: fade-in 0.66s ease both;
}

@keyframes rise-in {
    from {
        opacity: 0;
        transform: translateY(16px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fade-in {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes ambient-float {
    from {
        transform: translate3d(0, 0, 0) scale(1);
    }
    to {
        transform: translate3d(1rem, -1rem, 0) scale(1.06);
    }
}

@media (min-width: 900px) {
    .content-wrap {
        padding: 2.2rem 1.6rem 2.8rem;
    }

    .hero {
        grid-template-columns: minmax(0, 1.3fr) minmax(280px, 0.7fr);
        align-items: end;
    }

    .modules-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .workflow-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .split {
        grid-template-columns: 1.1fr 0.9fr;
    }
}

@media (min-width: 1180px) {
    .modules-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 720px) {
    .topbar {
        position: static;
        padding: 0.9rem;
    }

    .topbar,
    .topbar-actions,
    .hero-actions {
        flex-wrap: wrap;
    }

    .topbar-actions {
        width: 100%;
        justify-content: flex-start;
    }
}
</style>
