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
            headTitle: 'Uraboros | OSINT Platform',
            platformTitle: 'Uraboros Intelligence Platform',
            badge: 'OSINT • Analytics • Investigations',
            heroTitle:
                'Коммерческая OSINT-платформа для аналитиков и команд расследований',
            heroDescription:
                'Единое рабочее пространство для поиска, верификации и аналитики: от первичного сигнала до проверяемого отчета без переключения между десятками сервисов.',
            primaryCta: 'Открыть рабочее пространство',
            secondaryCta: 'Создать аккаунт',
            valueTitle: 'Что вы получаете',
            valueItems: [
                {
                    title: 'Быстрый переход от сигнала к гипотезе',
                    text: 'Поиск, pivots, риск-сигналы и рекомендации в едином UX-потоке без ручной склейки данных.',
                },
                {
                    title: 'Модульная архитектура для OSINT',
                    text: 'Telegram, Company Intel, Site Intel, Document Intel, Username, FIO и Email в одном продукте.',
                },
                {
                    title: 'Профессиональная подача результатов',
                    text: 'Структурированные выводы, понятные пояснения риска и быстрый экспорт для отчета/брифа.',
                },
            ],
            modulesTitle: 'Ключевые модули',
            modulesSubtitle:
                'Готовые рабочие сценарии для практической разведки',
            modules: [
                {
                    title: 'Company Intel',
                    text: 'Репутация, доменные сигналы, OSINT-переходы по бренду и инфраструктуре.',
                },
                {
                    title: 'Site Intel',
                    text: 'WHOIS/DNS/SSL/заголовки безопасности и технические риски сайта.',
                },
                {
                    title: 'Document Intel',
                    text: 'Поиск открытых документов, метаданные и признаки утечек.',
                },
                {
                    title: 'Telegram Analytics',
                    text: 'Аналитика каналов, anti-fraud сигналы, отчеты и экспорт.',
                },
                {
                    title: 'Username & Email Intel',
                    text: 'Проверка следов на платформах, сигналы совпадений и риск-профиль.',
                },
                {
                    title: 'Dashboard',
                    text: 'История запросов, повтор сценариев и централизованная рабочая лента.',
                },
            ],
            metrics: [
                { value: '7+', label: 'OSINT-модулей в одном контуре' },
                { value: '100%', label: 'Публичные источники без платных API' },
                {
                    value: '1 workflow',
                    label: 'От сигнала до отчета в едином интерфейсе',
                },
            ],
            finalTitle: 'Готовы перейти к продуктивной разведке?',
            finalText:
                'Запустите платформу и начните с любого модуля. Интерфейс и подсказки уже адаптированы под ежедневную аналитическую работу.',
            signIn: 'Войти',
            dashboard: 'Панель',
            register: 'Регистрация',
        };
    }

    return {
        headTitle: 'Uraboros | OSINT Platform',
        platformTitle: 'Uraboros Intelligence Platform',
        badge: 'OSINT • Analytics • Investigations',
        heroTitle:
            'Commercial-grade OSINT platform for analysts and investigation teams',
        heroDescription:
            'A unified workspace for search, verification, and intelligence workflows: from first signal to report-ready findings without tool fragmentation.',
        primaryCta: 'Open Workspace',
        secondaryCta: 'Create Account',
        valueTitle: 'What you get',
        valueItems: [
            {
                title: 'Fast path from signal to hypothesis',
                text: 'Search, pivots, risk signals, and recommendations in one UX flow without manual data stitching.',
            },
            {
                title: 'Modular OSINT architecture',
                text: 'Telegram, Company Intel, Site Intel, Document Intel, Username, FIO, and Email in one product.',
            },
            {
                title: 'Professional result delivery',
                text: 'Structured findings, clear risk explanations, and quick export for reports and briefings.',
            },
        ],
        modulesTitle: 'Core modules',
        modulesSubtitle:
            'Ready-to-use workflows for practical intelligence operations',
        modules: [
            {
                title: 'Company Intel',
                text: 'Reputation, domain signals, and OSINT pivots across brand and infrastructure.',
            },
            {
                title: 'Site Intel',
                text: 'WHOIS/DNS/SSL/security-header analysis and technical risk posture.',
            },
            {
                title: 'Document Intel',
                text: 'Public document discovery, metadata extraction, and exposure indicators.',
            },
            {
                title: 'Telegram Analytics',
                text: 'Channel analytics, anti-fraud signals, reporting, and export.',
            },
            {
                title: 'Username & Email Intel',
                text: 'Cross-platform traces, confidence signals, and risk profiling.',
            },
            {
                title: 'Dashboard',
                text: 'Query history, workflow reruns, and centralized analyst context.',
            },
        ],
        metrics: [
            { value: '7+', label: 'OSINT modules in one platform' },
            { value: '100%', label: 'Public-source workflows, no paid APIs' },
            {
                value: '1 workflow',
                label: 'From signal to report in one interface',
            },
        ],
        finalTitle: 'Ready to move faster with intelligence workflows?',
        finalText:
            'Launch the platform and start from any module. UI copy and hints are shaped for day-to-day analyst work.',
        signIn: 'Sign in',
        dashboard: 'Dashboard',
        register: 'Register',
    };
});

const pageTitle = computed(() => copy.value.headTitle);

const toggleLocale = () => {
    setLocale(locale.value === 'ru' ? 'en' : 'ru');
};
</script>

<template>
    <Head :title="pageTitle" />

    <div
        class="relative min-h-screen overflow-hidden bg-[#0a1020] text-slate-100"
    >
        <div class="pointer-events-none absolute inset-0">
            <div
                class="absolute -top-40 left-1/2 h-96 w-96 -translate-x-1/2 rounded-full bg-sky-500/30 blur-3xl"
            />
            <div
                class="absolute top-24 right-0 h-80 w-80 rounded-full bg-emerald-400/15 blur-3xl"
            />
            <div
                class="absolute bottom-0 left-0 h-80 w-80 rounded-full bg-cyan-400/15 blur-3xl"
            />
            <div class="grid-pattern absolute inset-0 opacity-20" />
        </div>

        <div
            class="relative mx-auto w-full max-w-7xl px-6 py-8 sm:px-8 lg:px-10"
        >
            <header class="mb-10 flex items-center justify-between gap-4">
                <div>
                    <p
                        class="text-xs tracking-[0.28em] text-cyan-300 uppercase"
                    >
                        Uraboros
                    </p>
                    <h1 class="mt-2 text-xl font-semibold sm:text-2xl">
                        {{ copy.platformTitle }}
                    </h1>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-700 bg-slate-900/70 px-3 py-2 text-sm font-medium text-slate-100 transition hover:border-cyan-300/40 hover:text-cyan-100"
                        @click="toggleLocale"
                    >
                        {{ locale.toUpperCase() }}
                    </button>

                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-lg border border-cyan-300/40 bg-cyan-400/10 px-4 py-2 text-sm font-medium text-cyan-100 transition hover:bg-cyan-400/20"
                    >
                        {{ copy.dashboard }}
                    </Link>

                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-lg border border-slate-700 bg-slate-900/70 px-4 py-2 text-sm font-medium text-slate-100 transition hover:border-cyan-300/40 hover:text-cyan-100"
                        >
                            {{ copy.signIn }}
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="rounded-lg border border-cyan-300/40 bg-cyan-400/10 px-4 py-2 text-sm font-medium text-cyan-100 transition hover:bg-cyan-400/20"
                        >
                            {{ copy.register }}
                        </Link>
                    </template>
                </div>
            </header>

            <main class="grid gap-6">
                <section
                    class="rounded-3xl border border-slate-800/90 bg-slate-900/75 p-8 shadow-2xl backdrop-blur xl:p-10"
                >
                    <p
                        class="inline-flex rounded-full border border-cyan-300/35 bg-cyan-300/10 px-3 py-1 text-xs font-medium text-cyan-200"
                    >
                        {{ copy.badge }}
                    </p>

                    <h2
                        class="mt-5 max-w-4xl text-3xl leading-tight font-semibold sm:text-4xl xl:text-5xl"
                    >
                        {{ copy.heroTitle }}
                    </h2>

                    <p
                        class="mt-5 max-w-3xl text-sm leading-relaxed text-slate-300 sm:text-base"
                    >
                        {{ copy.heroDescription }}
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <Link
                            :href="
                                $page.props.auth.user ? dashboard() : login()
                            "
                            class="rounded-xl bg-cyan-400 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300"
                        >
                            {{ copy.primaryCta }}
                        </Link>

                        <Link
                            v-if="!$page.props.auth.user && canRegister"
                            :href="register()"
                            class="rounded-xl border border-slate-700 bg-slate-900/80 px-5 py-2.5 text-sm font-semibold text-slate-100 transition hover:border-cyan-300/40 hover:text-cyan-100"
                        >
                            {{ copy.secondaryCta }}
                        </Link>
                    </div>
                </section>

                <section class="grid gap-4 md:grid-cols-3">
                    <article
                        v-for="item in copy.valueItems"
                        :key="item.title"
                        class="rounded-2xl border border-slate-800 bg-slate-900/65 p-5 shadow-lg backdrop-blur"
                    >
                        <h3 class="text-base font-semibold text-cyan-100">
                            {{ item.title }}
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-300">
                            {{ item.text }}
                        </p>
                    </article>
                </section>

                <section
                    class="rounded-3xl border border-slate-800/90 bg-slate-900/65 p-6 shadow-2xl backdrop-blur xl:p-8"
                >
                    <div
                        class="mb-5 flex flex-wrap items-end justify-between gap-3"
                    >
                        <h3 class="text-2xl font-semibold text-slate-100">
                            {{ copy.modulesTitle }}
                        </h3>
                        <p class="text-sm text-slate-300">
                            {{ copy.modulesSubtitle }}
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <article
                            v-for="card in copy.modules"
                            :key="card.title"
                            class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4"
                        >
                            <h4
                                class="text-sm font-semibold tracking-wide text-cyan-200 uppercase"
                            >
                                {{ card.title }}
                            </h4>
                            <p
                                class="mt-2 text-sm leading-relaxed text-slate-300"
                            >
                                {{ card.text }}
                            </p>
                        </article>
                    </div>
                </section>

                <section class="grid gap-4 lg:grid-cols-[1.05fr_0.95fr]">
                    <article
                        class="rounded-3xl border border-slate-800/90 bg-slate-900/65 p-6 shadow-xl backdrop-blur"
                    >
                        <h3 class="text-lg font-semibold text-slate-100">
                            {{ copy.valueTitle }}
                        </h3>
                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <div
                                v-for="metric in copy.metrics"
                                :key="metric.label"
                                class="rounded-xl border border-slate-800 bg-slate-950/45 px-3 py-3"
                            >
                                <p class="text-2xl font-semibold text-cyan-200">
                                    {{ metric.value }}
                                </p>
                                <p class="mt-1 text-xs text-slate-300">
                                    {{ metric.label }}
                                </p>
                            </div>
                        </div>
                    </article>

                    <article
                        class="rounded-3xl border border-cyan-300/25 bg-gradient-to-br from-cyan-400/15 via-slate-900/80 to-emerald-400/10 p-6 shadow-xl backdrop-blur"
                    >
                        <h3 class="text-lg font-semibold text-slate-100">
                            {{ copy.finalTitle }}
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-300">
                            {{ copy.finalText }}
                        </p>
                        <div class="mt-5 flex flex-wrap gap-2">
                            <Link
                                :href="
                                    $page.props.auth.user
                                        ? dashboard()
                                        : login()
                                "
                                class="rounded-xl bg-cyan-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300"
                            >
                                {{ copy.primaryCta }}
                            </Link>
                            <Link
                                v-if="!$page.props.auth.user && canRegister"
                                :href="register()"
                                class="rounded-xl border border-slate-700 bg-slate-900/75 px-4 py-2 text-sm font-semibold text-slate-100 transition hover:border-cyan-300/40 hover:text-cyan-100"
                            >
                                {{ copy.secondaryCta }}
                            </Link>
                        </div>
                    </article>
                </section>
            </main>
        </div>
    </div>
</template>

<style scoped>
.grid-pattern {
    background-image:
        linear-gradient(
            to right,
            rgba(148, 163, 184, 0.12) 1px,
            transparent 1px
        ),
        linear-gradient(
            to bottom,
            rgba(148, 163, 184, 0.12) 1px,
            transparent 1px
        );
    background-size: 40px 40px;
}
</style>
