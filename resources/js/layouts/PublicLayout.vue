<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Menu, X } from 'lucide-vue-next';
import { ref } from 'vue';
import CookieNotice from '@/components/CookieNotice.vue';
import PublicAction from '@/components/public/PublicAction.vue';
import { useI18n } from '@/composables/useI18n';
import { home, login, privacy, terms } from '@/routes';
import { index as features } from '@/routes/features';
import '../../css/public-site.css';

defineProps<{ canRegister: boolean }>();
const { t, locale, setLocale } = useI18n();
const menuOpen = ref(false);
const menuButton = ref<HTMLButtonElement | null>(null);

function closeMenu() {
    menuOpen.value = false;
    menuButton.value?.focus();
}
</script>

<template>
    <div class="public-shell">
        <a class="public-skip" href="#public-main">{{
            t('publicSite.skip')
        }}</a>
        <div class="public-container">
            <header class="public-header" @keydown.esc="closeMenu">
                <Link :href="home()" class="public-brand" aria-label="Uraboros"
                    ><img
                        src="/favicon.svg"
                        width="32"
                        height="32"
                        alt=""
                    /><span>URABOROS</span></Link
                >
                <button
                    ref="menuButton"
                    type="button"
                    class="public-menu-toggle"
                    :aria-expanded="menuOpen"
                    aria-controls="public-navigation"
                    :aria-label="t('publicSite.navigation')"
                    @click="menuOpen = !menuOpen"
                >
                    <X v-if="menuOpen" :size="20" aria-hidden="true" />
                    <Menu v-else :size="20" aria-hidden="true" />
                </button>
                <nav
                    id="public-navigation"
                    class="public-nav"
                    :class="{ 'is-open': menuOpen }"
                    :aria-label="t('publicSite.navigation')"
                >
                    <Link :href="features()" @click="menuOpen = false">{{
                        t('publicSite.catalog')
                    }}</Link>
                    <Link
                        v-if="!$page.props.auth.user"
                        :href="login()"
                        @click="menuOpen = false"
                        >{{ t('publicSite.signIn') }}</Link
                    >
                    <button
                        type="button"
                        class="public-locale"
                        :aria-label="t('publicSite.language')"
                        @click="setLocale(locale === 'ru' ? 'en' : 'ru')"
                    >
                        {{ locale.toUpperCase() }}
                    </button>
                    <PublicAction :can-register="canRegister" />
                </nav>
            </header>
            <main id="public-main" tabindex="-1"><slot /></main>
            <footer class="public-footer">
                <div>
                    <span class="public-brand">URABOROS</span>
                    <p>{{ t('publicSite.footer') }}</p>
                </div>
                <nav :aria-label="t('publicSite.footerNavigation')">
                    <Link :href="features()">{{ t('publicSite.catalog') }}</Link
                    ><Link :href="privacy()">{{ t('publicSite.privacy') }}</Link
                    ><Link :href="terms()">{{ t('publicSite.terms') }}</Link>
                </nav>
            </footer>
        </div>
        <CookieNotice />
    </div>
</template>
