<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const { locale, setLocale, t } = useI18n();

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative min-h-svh overflow-hidden bg-slate-950 text-slate-100">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -top-32 left-1/2 h-80 w-80 -translate-x-1/2 rounded-full bg-cyan-500/30 blur-3xl" />
            <div class="absolute bottom-0 left-0 h-72 w-72 rounded-full bg-emerald-500/20 blur-3xl" />
            <div class="absolute right-0 top-1/3 h-72 w-72 rounded-full bg-indigo-500/25 blur-3xl" />
            <div class="grid-pattern absolute inset-0 opacity-30" />
        </div>

        <div class="relative mx-auto flex min-h-svh w-full max-w-6xl items-center px-6 py-10 sm:px-8 lg:px-10">
            <div class="grid w-full gap-6 lg:grid-cols-[1fr_1.05fr]">
                <section class="hidden rounded-3xl border border-slate-800/90 bg-slate-900/70 p-8 shadow-2xl backdrop-blur lg:block xl:p-10">
                    <p class="inline-flex rounded-full border border-cyan-300/35 bg-cyan-300/10 px-3 py-1 text-xs font-medium text-cyan-200">
                        Uraboros
                    </p>
                    <h2 class="mt-5 text-3xl font-semibold leading-tight text-slate-100 xl:text-4xl">
                        Telegram Intelligence Platform
                    </h2>
                    <p class="mt-4 text-sm leading-relaxed text-slate-300 xl:text-base">
                        Unified access point for search, monitoring, and OSINT workflows based on Telegram data.
                    </p>
                </section>

                <section class="relative rounded-3xl border border-slate-800/90 bg-slate-900/75 p-6 shadow-2xl backdrop-blur sm:p-8">
                    <button
                        type="button"
                        class="absolute right-4 top-4 rounded-lg border border-slate-700 bg-slate-800/80 px-2.5 py-1.5 text-xs font-medium text-slate-200 transition hover:border-cyan-300/40 hover:text-cyan-100"
                        @click="setLocale(locale === 'ru' ? 'en' : 'ru')"
                    >
                        {{ locale.toUpperCase() }}
                    </button>

                    <div class="flex flex-col gap-7">
                        <div class="flex flex-col items-center gap-4 text-center">
                            <Link :href="home()" class="flex items-center gap-2 font-medium text-cyan-100">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-cyan-300/35 bg-cyan-300/10">
                                    <AppLogoIcon class="size-6 fill-current text-cyan-100" />
                                </div>
                                <span class="text-sm uppercase tracking-[0.18em] text-cyan-200">Uraboros</span>
                            </Link>
                            <div class="space-y-2">
                                <h1 class="text-2xl font-semibold text-slate-100">{{ title ? t(title) : '' }}</h1>
                                <p class="text-sm leading-relaxed text-slate-300">{{ description ? t(description) : '' }}</p>
                            </div>
                        </div>

                        <slot />
                    </div>
                </section>
            </div>
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
