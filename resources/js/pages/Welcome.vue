<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    }
);

const { locale, setLocale } = useI18n();

const copy = computed(() => {
    if (locale.value === 'ru') {
        return {
            headTitle: 'Uraboros | Intelligence Workspace',
            brand: 'Uraboros Intelligence Workspace',
            badge: 'OSINT PLATFORM',
            heroTitle: 'Операционная OSINT-платформа для аналитических команд',
            heroText:
                'Рабочая среда для поиска, проверки и аналитики: Telegram, Site Intel, YouTube, News & Media, Shifr, Dashboard и Wiki в едином контуре.',
            ctaMain: 'Перейти в рабочее пространство',
            ctaAlt: 'Запросить доступ',
            signIn: 'Войти',
            dashboard: 'Панель',
            register: 'Регистрация',
            stackTitle: 'Что уже в проде',
            stackItems: [
                'Telegram Search / Analytics / Parser',
                'Site Intel и технический профиль доменов',
                'YouTube Search / Analytics / Parser',
                'News & Media Intel',
                'Shifr toolkit и Dashboard-автоматизация',
            ],
            workflowsTitle: 'Ключевые сценарии',
            workflows: [
                {
                    title: 'Расследование по сигналу',
                    text: 'От первого индикатора до проверяемой гипотезы с историей действий в Dashboard.',
                },
                {
                    title: 'Мониторинг упоминаний',
                    text: 'Сбор публичных следов, аналитика динамики и экспорт результата в отчет.',
                },
                {
                    title: 'Техническая проверка актива',
                    text: 'Быстрый профиль домена/источника: риски конфигурации, связки, артефакты.',
                },
            ],
            trustTitle: 'Для коммерческого применения',
            trustItems: [
                'Единый UX без прыжков между внешними сервисами',
                'Повторяемые workflow и сохраненные запросы',
                'Контроль качества через структуру модулей и wiki-документацию',
            ],
            metrics: [
                { value: '6', label: 'прикладных направлений в одном продукте' },
                { value: '1', label: 'контур работы: сигнал → аналитика → вывод' },
                { value: '24/7', label: 'доступность среды для командной работы' },
            ],
            finalTitle: 'Готово для ежедневной коммерческой аналитики',
            finalText:
                'Запускайте расследования, фиксируйте контекст и возвращайтесь к кейсам без потери данных и хода анализа.',
        };
    }

    return {
        headTitle: 'Uraboros | Intelligence Workspace',
        brand: 'Uraboros Intelligence Workspace',
        badge: 'OSINT PLATFORM',
        heroTitle: 'Operational OSINT platform for analyst teams',
        heroText:
            'A unified environment for search, validation, and intelligence workflows across Telegram, Site Intel, YouTube, News & Media, Shifr, Dashboard, and Wiki.',
        ctaMain: 'Open workspace',
        ctaAlt: 'Request access',
        signIn: 'Sign in',
        dashboard: 'Dashboard',
        register: 'Register',
        stackTitle: 'Live capabilities',
        stackItems: [
            'Telegram Search / Analytics / Parser',
            'Site Intel and technical domain profiling',
            'YouTube Search / Analytics / Parser',
            'News & Media Intel',
            'Shifr toolkit and Dashboard automation',
        ],
        workflowsTitle: 'Core workflows',
        workflows: [
            {
                title: 'Signal-driven investigation',
                text: 'From first indicator to testable hypothesis with full analyst context in Dashboard.',
            },
            {
                title: 'Mentions monitoring',
                text: 'Collect public traces, analyze trend dynamics, and export report-ready outputs.',
            },
            {
                title: 'Technical asset validation',
                text: 'Rapid source/domain profile with configuration risks and linked artifacts.',
            },
        ],
        trustTitle: 'Built for commercial operations',
        trustItems: [
            'Single UX flow without tool-hopping across external services',
            'Repeatable workflows with saved query templates',
            'Operational consistency via modular architecture and wiki docs',
        ],
        metrics: [
            { value: '6', label: 'applied intelligence domains in one product' },
            { value: '1', label: 'workflow: signal -> analysis -> findings' },
            { value: '24/7', label: 'availability for team-based operations' },
        ],
        finalTitle: 'Ready for day-to-day commercial intelligence',
        finalText:
            'Launch investigations, preserve context, and revisit cases without losing data or analytical momentum.',
    };
});

const pageTitle = computed(() => copy.value.headTitle);

const toggleLocale = () => setLocale(locale.value === 'ru' ? 'en' : 'ru');
</script>

<template>
    <Head :title="pageTitle" />

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
                    <button type="button" class="locale-btn" @click="toggleLocale">
                        {{ locale.toUpperCase() }}
                    </button>

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
                        <Link v-if="canRegister" :href="register()" class="btn btn-outline">
                            {{ copy.register }}
                        </Link>
                    </template>
                </div>
            </header>

            <main class="layout">
                <section class="hero panel rise">
                    <p class="badge">{{ copy.badge }}</p>
                    <h2 class="hero-title">{{ copy.heroTitle }}</h2>
                    <p class="hero-text">{{ copy.heroText }}</p>

                    <div class="hero-actions">
                        <Link :href="$page.props.auth.user ? dashboard() : login()" class="btn btn-solid">
                            {{ copy.ctaMain }}
                        </Link>
                        <Link v-if="!$page.props.auth.user && canRegister" :href="register()" class="btn btn-ghost">
                            {{ copy.ctaAlt }}
                        </Link>
                    </div>
                </section>

                <section class="panel stack fade-in">
                    <h3 class="section-title">{{ copy.stackTitle }}</h3>
                    <ul class="list">
                        <li v-for="item in copy.stackItems" :key="item">
                            {{ item }}
                        </li>
                    </ul>
                </section>

                <section class="cards-grid">
                    <article
                        v-for="(workflow, index) in copy.workflows"
                        :key="workflow.title"
                        class="panel workflow-card fade-in"
                        :style="{ animationDelay: `${index * 0.12}s` }"
                    >
                        <h4>{{ workflow.title }}</h4>
                        <p>{{ workflow.text }}</p>
                    </article>
                </section>

                <section class="split">
                    <article class="panel fade-in">
                        <h3 class="section-title">{{ copy.trustTitle }}</h3>
                        <ul class="list">
                            <li v-for="item in copy.trustItems" :key="item">
                                {{ item }}
                            </li>
                        </ul>
                    </article>

                    <article class="panel metrics-panel fade-in">
                        <div v-for="metric in copy.metrics" :key="metric.label" class="metric">
                            <p class="metric-value">{{ metric.value }}</p>
                            <p class="metric-label">{{ metric.label }}</p>
                        </div>
                    </article>
                </section>

                <section class="panel cta-panel rise">
                    <h3>{{ copy.finalTitle }}</h3>
                    <p>{{ copy.finalText }}</p>
                    <div class="hero-actions">
                        <Link :href="$page.props.auth.user ? dashboard() : login()" class="btn btn-solid">
                            {{ copy.ctaMain }}
                        </Link>
                        <Link v-if="!$page.props.auth.user && canRegister" :href="register()" class="btn btn-ghost">
                            {{ copy.ctaAlt }}
                        </Link>
                    </div>
                </section>
            </main>
        </div>
    </div>
</template>

<style scoped>
.page-root {
    position: relative;
    min-height: 100vh;
    overflow: hidden;
    background: radial-gradient(120% 140% at 20% 0%, #14213d 0%, #090f1f 55%, #04070f 100%);
    color: #e6ecff;
    font-family: "Space Grotesk", "Manrope", "Segoe UI", sans-serif;
}

.bg-layer {
    position: absolute;
    border-radius: 9999px;
    filter: blur(80px);
    opacity: 0.42;
    pointer-events: none;
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
}

.content-wrap {
    position: relative;
    margin: 0 auto;
    max-width: 1160px;
    padding: 2rem 1.2rem 2.4rem;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.3rem;
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
    border-radius: 0.75rem;
    border: 1px solid rgba(148, 163, 184, 0.28);
    color: #e8edf8;
    font-size: 0.86rem;
    font-weight: 560;
    text-decoration: none;
    transition: all 0.2s ease;
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
    box-shadow: 0 12px 24px rgba(45, 212, 191, 0.22);
}

.btn:hover,
.locale-btn:hover {
    transform: translateY(-1px);
    border-color: rgba(125, 211, 252, 0.62);
}

.layout {
    display: grid;
    gap: 0.95rem;
}

.panel {
    border: 1px solid rgba(148, 163, 184, 0.2);
    background: linear-gradient(155deg, rgba(15, 23, 42, 0.88), rgba(9, 14, 27, 0.75));
    border-radius: 1.25rem;
    padding: 1.2rem;
    backdrop-filter: blur(9px);
}

.hero-title {
    margin: 0.9rem 0 0;
    font-size: clamp(1.55rem, 4vw, 2.75rem);
    line-height: 1.12;
}

.hero-text {
    margin-top: 0.8rem;
    max-width: 830px;
    color: #c9d4ee;
    line-height: 1.58;
    font-size: 0.97rem;
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
    gap: 0.5rem;
}

.section-title {
    margin: 0;
    font-size: 1.05rem;
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
    line-height: 1.45;
    padding-left: 1.05rem;
    position: relative;
}

.list li::before {
    content: "";
    width: 0.42rem;
    height: 0.42rem;
    border-radius: 999px;
    background: #2dd4bf;
    box-shadow: 0 0 0 4px rgba(45, 212, 191, 0.2);
    position: absolute;
    left: 0;
    top: 0.45rem;
}

.cards-grid {
    display: grid;
    gap: 0.95rem;
}

.workflow-card h4 {
    margin: 0;
    font-size: 0.98rem;
}

.workflow-card p {
    margin: 0.55rem 0 0;
    color: #c9d4ee;
    font-size: 0.9rem;
    line-height: 1.5;
}

.split {
    display: grid;
    gap: 0.95rem;
}

.metrics-panel {
    display: grid;
    gap: 0.7rem;
}

.metric {
    border: 1px solid rgba(148, 163, 184, 0.22);
    border-radius: 0.9rem;
    background: rgba(8, 13, 25, 0.55);
    padding: 0.8rem 0.85rem;
}

.metric-value {
    margin: 0;
    color: #7dd3fc;
    font-size: 1.4rem;
    font-weight: 670;
}

.metric-label {
    margin: 0.2rem 0 0;
    color: #cad3e9;
    font-size: 0.83rem;
}

.cta-panel h3 {
    margin: 0;
    font-size: 1.2rem;
}

.cta-panel p {
    margin: 0.65rem 0 0;
    color: #d2ddf4;
    line-height: 1.56;
    font-size: 0.92rem;
    max-width: 780px;
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

@media (min-width: 900px) {
    .content-wrap {
        padding: 2.2rem 1.6rem 2.6rem;
    }

    .cards-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .split {
        grid-template-columns: 1.1fr 0.9fr;
    }
}
</style>
