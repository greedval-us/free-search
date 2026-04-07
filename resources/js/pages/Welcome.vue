<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const features = [
    {
        title: 'Мониторинг каналов и чатов',
        text: 'Собирайте сигналы из Telegram в едином пространстве без ручной рутины.',
    },
    {
        title: 'OSINT и аналитика связей',
        text: 'Отслеживайте сущности, связи и динамику информационных кампаний.',
    },
    {
        title: 'Рабочие дашборды',
        text: 'Фокусируйтесь на важных метриках и быстро переходите от гипотез к проверке.',
    },
];
</script>

<template>
    <Head title="Uraboros | Telegram Analytics" />

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
                    <h1 class="mt-2 text-xl font-semibold sm:text-2xl">Telegram Intelligence Platform</h1>
                </div>

                <nav class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-lg border border-cyan-300/40 bg-cyan-400/10 px-4 py-2 text-sm font-medium text-cyan-100 transition hover:bg-cyan-400/20"
                    >
                        Dashboard
                    </Link>

                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-lg border border-slate-700 bg-slate-900/70 px-4 py-2 text-sm font-medium text-slate-100 transition hover:border-cyan-300/40 hover:text-cyan-100"
                        >
                            Вход
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="rounded-lg border border-cyan-300/40 bg-cyan-400/10 px-4 py-2 text-sm font-medium text-cyan-100 transition hover:bg-cyan-400/20"
                        >
                            Регистрация
                        </Link>
                    </template>
                </nav>
            </header>

            <main class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                <section class="rounded-3xl border border-slate-800/90 bg-slate-900/70 p-8 shadow-2xl backdrop-blur xl:p-10">
                    <p class="inline-flex rounded-full border border-cyan-300/35 bg-cyan-300/10 px-3 py-1 text-xs font-medium text-cyan-200">
                        Поиск, мониторинг, OSINT
                    </p>

                    <h2 class="mt-5 max-w-3xl text-3xl font-semibold leading-tight sm:text-4xl xl:text-5xl">
                        Стартовая точка для анализа информации в Telegram
                    </h2>

                    <p class="mt-5 max-w-2xl text-sm leading-relaxed text-slate-300 sm:text-base">
                        Платформа помогает собирать данные, фильтровать шум и находить значимые сигналы.
                        Базовый контур уже готов, дальше можно масштабировать систему под вашу аналитику,
                        исследование источников и построение сценариев OSINT.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <Link
                            :href="$page.props.auth.user ? dashboard() : login()"
                            class="rounded-xl bg-cyan-400 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300"
                        >
                            {{ $page.props.auth.user ? 'Открыть Dashboard' : 'Начать работу' }}
                        </Link>

                        <Link
                            v-if="!$page.props.auth.user && canRegister"
                            :href="register()"
                            class="rounded-xl border border-slate-700 bg-slate-900/80 px-5 py-2.5 text-sm font-semibold text-slate-100 transition hover:border-cyan-300/40 hover:text-cyan-100"
                        >
                            Создать аккаунт
                        </Link>
                    </div>
                </section>

                <aside class="space-y-4">
                    <article
                        v-for="feature in features"
                        :key="feature.title"
                        class="rounded-2xl border border-slate-800 bg-slate-900/65 p-5 shadow-lg backdrop-blur"
                    >
                        <h3 class="text-base font-semibold text-cyan-100">{{ feature.title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-300">{{ feature.text }}</p>
                    </article>
                </aside>
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
