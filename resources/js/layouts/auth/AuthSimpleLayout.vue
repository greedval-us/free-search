<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useI18n } from '@/composables/useI18n';
import { home } from '@/routes';

const { locale, setLocale, t } = useI18n();

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="auth-stage">
        <div class="auth-stage-grid">
            <div class="auth-stage-layout">
                <section class="auth-story-panel">
                    <p class="auth-chip">Uraboros</p>
                    <h2
                        class="mt-4 text-2xl leading-tight font-semibold text-slate-100"
                    >
                        {{ t('auth.layout.platformTitle') }}
                    </h2>
                    <p class="mt-3 text-sm leading-relaxed text-slate-300">
                        {{ t('auth.layout.platformText') }}
                    </p>
                </section>

                <section class="auth-form-panel">
                    <button
                        type="button"
                        class="absolute top-4 right-4 rounded-lg border border-slate-700 bg-slate-800/80 px-2.5 py-1.5 text-xs font-medium text-slate-200 transition hover:border-cyan-300/40 hover:text-cyan-100"
                        @click="setLocale(locale === 'ru' ? 'en' : 'ru')"
                    >
                        {{ locale.toUpperCase() }}
                    </button>

                    <div class="flex flex-col gap-5">
                        <div
                            class="flex flex-col items-center gap-3 text-center"
                        >
                            <Link
                                :href="home()"
                                class="flex items-center gap-2 font-medium text-cyan-100"
                            >
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-lg border border-cyan-300/35 bg-cyan-300/10"
                                >
                                    <AppLogoIcon
                                        class="size-6 fill-current text-cyan-100"
                                    />
                                </div>
                                <span
                                    class="text-sm tracking-[0.18em] text-cyan-200 uppercase"
                                    >Uraboros</span
                                >
                            </Link>
                            <div class="space-y-2">
                                <h1
                                    class="text-2xl font-semibold text-slate-100"
                                >
                                    {{ title ? t(title) : '' }}
                                </h1>
                                <p
                                    class="text-sm leading-relaxed text-slate-300"
                                >
                                    {{ description ? t(description) : '' }}
                                </p>
                            </div>
                        </div>
                        <slot />
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>
