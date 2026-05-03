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
    },
);

const { t, locale, setLocale } = useI18n();

const heroCopy = computed(() => {
    if (locale.value === 'ru') {
        return {
            headTitle: 'Uraboros | OSINT Platform',
            platformTitle: 'OSINT Intelligence Platform',
            pill: 'Telegram, Username, Site, FIO, Email',
            heroTitle: 'Единая платформа для поиска и аналитики',
            heroDescription:
                'Работайте с Telegram, username, сайтами, ФИО и email в одном окне: находите сигналы, анализируйте риски и быстро возвращайтесь к результатам через Dashboard.',
        };
    }

    return {
        headTitle: 'Uraboros | OSINT Platform',
        platformTitle: 'OSINT Intelligence Platform',
        pill: 'Telegram, Username, Site, FIO, Email',
        heroTitle: 'One platform for search and intelligence',
        heroDescription:
            'Work across Telegram, username, site, full-name, and email modules in one workspace: detect signals, analyze risks, and revisit results through Dashboard.',
    };
});

const pageTitle = computed(() => heroCopy.value.headTitle);

const features = computed(() => {
    if (locale.value === 'ru') {
        return [
            {
                title: 'Мульти-модульный OSINT',
                text: 'Telegram, Username, Site Intel, FIO и Email Intel в едином рабочем пространстве.',
            },
            {
                title: 'Онлайн-поиск и проверка',
                text: 'Быстрая проверка сущностей и источников с аналитикой без перегруженного интерфейса.',
            },
            {
                title: 'Централизованный Dashboard',
                text: 'История действий, сохраненные запросы и быстрый повтор сценариев в одном месте.',
            },
        ];
    }

    return [
        {
            title: 'Multi-module OSINT',
            text: 'Telegram, Username, Site Intel, FIO, and Email Intel in one operational workspace.',
        },
        {
            title: 'Live search and verification',
            text: 'Fast entity checks and source validation with analytics in a clean interface.',
        },
        {
            title: 'Centralized Dashboard',
            text: 'Action history, saved queries, and quick reruns of your workflows in one place.',
        },
    ];
});

const moduleCards = computed(() => {
    if (locale.value === 'ru') {
        return [
            {
                title: 'Telegram',
                text: 'Поиск сообщений и комментариев, аналитика каналов, сбор и экспорт данных по периодам.',
            },
            {
                title: 'Username OSINT',
                text: 'Проверка никнеймов по открытым платформам, оценка совпадений и граф связей.',
            },
            {
                title: 'Site Intel',
                text: 'Site Health, Domain Lite, SEO-аудит и аналитические сигналы по доменам.',
            },
            {
                title: 'FIO Intel',
                text: 'Поиск публичных упоминаний ФИО из нескольких источников с кластеризацией и скорингом.',
            },
            {
                title: 'Email Intel',
                text: 'DNS/MX/SPF/DMARC анализ, риск-сигналы, граф сущностей и массовая проверка.',
            },
            {
                title: 'Dashboard',
                text: 'История запросов, сохраненные сценарии, избранные модули и быстрый повтор действий.',
            },
        ];
    }

    return [
        {
            title: 'Telegram',
            text: 'Message and comments search, channel analytics, collection, and export by period.',
        },
        {
            title: 'Username OSINT',
            text: 'Cross-platform username checks with match confidence and relationship graph.',
        },
        {
            title: 'Site Intel',
            text: 'Site Health, Domain Lite, SEO audit, and domain-level analytics signals.',
        },
        {
            title: 'FIO Intel',
            text: 'Public full-name lookup from multiple sources with clustering and confidence scoring.',
        },
        {
            title: 'Email Intel',
            text: 'DNS/MX/SPF/DMARC analysis, risk signals, entity graph, and bulk checks.',
        },
        {
            title: 'Dashboard',
            text: 'Query history, saved queries, pinned modules, and quick reruns.',
        },
    ];
});

const commercialBullets = computed(() => {
    if (locale.value === 'ru') {
        return [
            '5 рабочих модулей в одном интерфейсе',
            'Онлайн-поиск и аналитика без перегруженного интерфейса',
            'Быстрый переход от сигнала к проверке гипотез',
        ];
    }

    return [
        '5 operational modules in one interface',
        'Live search and analytics in a clean workflow',
        'Fast path from signal detection to hypothesis validation',
    ];
});

const toggleLocale = () => {
    setLocale(locale.value === 'ru' ? 'en' : 'ru');
};
</script>

<template>
    <Head :title="pageTitle" />

    <div class="relative min-h-screen overflow-hidden bg-slate-950 text-slate-100">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -top-32 left-1/2 h-80 w-80 -translate-x-1/2 rounded-full bg-cyan-500/30 blur-3xl" />
            <div class="absolute bottom-0 left-0 h-72 w-72 rounded-full bg-emerald-500/20 blur-3xl" />
            <div class="absolute right-0 top-1/3 h-72 w-72 rounded-full bg-indigo-500/25 blur-3xl" />
            <div class="grid-pattern absolute inset-0 opacity-30" />
        </div>

        <div class="relative mx-auto flex w-full max-w-6xl flex-col px-6 py-8 sm:px-8 lg:px-10">
            <header class="mb-10 flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.22em] text-cyan-300">Uraboros</p>
                    <h1 class="mt-2 text-xl font-semibold sm:text-2xl">{{ heroCopy.platformTitle }}</h1>
                </div>

                <nav class="flex items-center gap-3">
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
                        {{ t('welcome.dashboard') }}
                    </Link>

                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-lg border border-slate-700 bg-slate-900/70 px-4 py-2 text-sm font-medium text-slate-100 transition hover:border-cyan-300/40 hover:text-cyan-100"
                        >
                            {{ t('welcome.signIn') }}
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="rounded-lg border border-cyan-300/40 bg-cyan-400/10 px-4 py-2 text-sm font-medium text-cyan-100 transition hover:bg-cyan-400/20"
                        >
                            {{ t('welcome.register') }}
                        </Link>
                    </template>
                </nav>
            </header>

            <main class="grid gap-6">
                <section class="rounded-3xl border border-slate-800/90 bg-slate-900/70 p-8 shadow-2xl backdrop-blur xl:p-10">
                    <p class="inline-flex rounded-full border border-cyan-300/35 bg-cyan-300/10 px-3 py-1 text-xs font-medium text-cyan-200">
                        {{ heroCopy.pill }}
                    </p>

                    <h2 class="mt-5 max-w-3xl text-3xl font-semibold leading-tight sm:text-4xl xl:text-5xl">
                        {{ heroCopy.heroTitle }}
                    </h2>

                    <p class="mt-5 max-w-2xl text-sm leading-relaxed text-slate-300 sm:text-base">
                        {{ heroCopy.heroDescription }}
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <Link
                            :href="$page.props.auth.user ? dashboard() : login()"
                            class="rounded-xl bg-cyan-400 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300"
                        >
                            {{ $page.props.auth.user ? t('welcome.openDashboard') : t('welcome.getStarted') }}
                        </Link>

                        <Link
                            v-if="!$page.props.auth.user && canRegister"
                            :href="register()"
                            class="rounded-xl border border-slate-700 bg-slate-900/80 px-5 py-2.5 text-sm font-semibold text-slate-100 transition hover:border-cyan-300/40 hover:text-cyan-100"
                        >
                            {{ t('welcome.createAccount') }}
                        </Link>
                    </div>
                </section>

                <aside class="grid gap-4 md:grid-cols-3">
                    <article
                        v-for="feature in features"
                        :key="feature.title"
                        class="rounded-2xl border border-slate-800 bg-slate-900/65 p-5 shadow-lg backdrop-blur"
                    >
                        <h3 class="text-base font-semibold text-cyan-100">{{ feature.title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-300">{{ feature.text }}</p>
                    </article>
                </aside>

                <section class="rounded-3xl border border-slate-800/90 bg-slate-900/65 p-6 shadow-2xl backdrop-blur xl:p-8">
                    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                        <h3 class="text-2xl font-semibold text-slate-100">
                            {{ locale === 'ru' ? 'Функционал платформы' : 'Platform capabilities' }}
                        </h3>
                        <p class="text-sm text-slate-300">
                            {{ locale === 'ru' ? 'Общий обзор модулей' : 'High-level module overview' }}
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <article
                            v-for="card in moduleCards"
                            :key="card.title"
                            class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4"
                        >
                            <h4 class="text-sm font-semibold uppercase tracking-wide text-cyan-200">{{ card.title }}</h4>
                            <p class="mt-2 text-sm leading-relaxed text-slate-300">{{ card.text }}</p>
                        </article>
                    </div>
                </section>

                <section class="grid gap-4 lg:grid-cols-[1.15fr_0.85fr]">
                    <article class="rounded-3xl border border-slate-800/90 bg-slate-900/65 p-6 shadow-xl backdrop-blur">
                        <h3 class="text-lg font-semibold text-slate-100">
                            {{ locale === 'ru' ? 'Почему выбирают Uraboros' : 'Why teams choose Uraboros' }}
                        </h3>
                        <ul class="mt-3 space-y-2 text-sm text-slate-300">
                            <li
                                v-for="item in commercialBullets"
                                :key="item"
                                class="rounded-xl border border-slate-800 bg-slate-950/45 px-3 py-2"
                            >
                                {{ item }}
                            </li>
                        </ul>
                    </article>

                    <article class="rounded-3xl border border-cyan-300/25 bg-gradient-to-br from-cyan-400/15 via-slate-900/80 to-emerald-400/10 p-6 shadow-xl backdrop-blur">
                        <h3 class="text-lg font-semibold text-slate-100">
                            {{ locale === 'ru' ? 'Запустить платформу' : 'Launch the platform' }}
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-300">
                            {{
                                locale === 'ru'
                                    ? 'Откройте рабочее пространство и начните анализ с нужного модуля.'
                                    : 'Open your workspace and start from the module you need.'
                            }}
                        </p>
                        <div class="mt-5 flex flex-wrap gap-2">
                            <Link
                                :href="$page.props.auth.user ? dashboard() : login()"
                                class="rounded-xl bg-cyan-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300"
                            >
                                {{ $page.props.auth.user ? t('welcome.openDashboard') : t('welcome.getStarted') }}
                            </Link>
                            <Link
                                v-if="!$page.props.auth.user && canRegister"
                                :href="register()"
                                class="rounded-xl border border-slate-700 bg-slate-900/75 px-4 py-2 text-sm font-semibold text-slate-100 transition hover:border-cyan-300/40 hover:text-cyan-100"
                            >
                                {{ t('welcome.createAccount') }}
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
    background-image: linear-gradient(to right, rgba(148, 163, 184, 0.12) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(148, 163, 184, 0.12) 1px, transparent 1px);
    background-size: 40px 40px;
}
</style>
