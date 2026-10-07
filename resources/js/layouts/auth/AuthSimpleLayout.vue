<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    FileText,
    Globe2,
    MessagesSquare,
    Search,
} from 'lucide-vue-next';
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
        <main class="auth-stage-grid">
            <div class="auth-stage-layout">
                <aside
                    class="auth-story-panel"
                    aria-labelledby="auth-platform-title"
                >
                    <Link :href="home()" class="auth-brand">
                        <span class="auth-brand-mark">
                            <AppLogoIcon class="auth-brand-icon" />
                        </span>
                        <span>Uraboros</span>
                    </Link>
                    <h2 id="auth-platform-title" class="auth-story-title">
                        {{ t('auth.layout.platformTitle') }}
                    </h2>
                    <p class="auth-story-text">
                        {{ t('auth.layout.platformText') }}
                    </p>

                    <div class="auth-source-map" aria-hidden="true">
                        <div class="auth-source-stack">
                            <span class="auth-source-node"
                                ><Globe2 :size="23"
                            /></span>
                            <span class="auth-source-node"
                                ><FileText :size="23"
                            /></span>
                            <span class="auth-source-node"
                                ><MessagesSquare :size="23"
                            /></span>
                        </div>
                        <span class="auth-source-connection"
                            ><ArrowRight :size="20"
                        /></span>
                        <div class="auth-result-node">
                            <Search :size="30" />
                            <div class="auth-result-lines">
                                <span></span><span></span><span></span>
                            </div>
                        </div>
                    </div>
                </aside>

                <section class="auth-form-panel">
                    <div class="auth-form-toolbar">
                        <Link :href="home()" class="auth-brand auth-form-brand">
                            <span class="auth-brand-mark">
                                <AppLogoIcon class="auth-brand-icon" />
                            </span>
                            <span>Uraboros</span>
                        </Link>
                        <button
                            type="button"
                            class="auth-locale"
                            :aria-label="t('publicSite.language')"
                            @click="setLocale(locale === 'ru' ? 'en' : 'ru')"
                        >
                            {{ locale.toUpperCase() }}
                        </button>
                    </div>

                    <div class="auth-form-content">
                        <header>
                            <h1 class="auth-form-title">
                                {{ title ? t(title) : '' }}
                            </h1>
                            <p class="auth-form-description">
                                {{ description ? t(description) : '' }}
                            </p>
                        </header>
                        <slot />
                    </div>
                </section>
            </div>
        </main>
    </div>
</template>
