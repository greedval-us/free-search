<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import CookieNotice from '@/components/CookieNotice.vue';
import PublicAction from '@/components/public/PublicAction.vue';
import { useI18n } from '@/composables/useI18n';
import '../../css/public-site.css';

defineProps<{ canRegister: boolean }>();
const { t, locale, setLocale } = useI18n();
</script>

<template>
    <div class="public-shell">
        <a class="public-skip" href="#public-main">{{
            t('publicSite.skip')
        }}</a>
        <div class="public-container">
            <header class="public-header">
                <Link href="/" class="public-brand" aria-label="Uraboros"
                    ><img
                        src="/favicon.svg"
                        width="32"
                        height="32"
                        alt=""
                    /><span>URABOROS</span></Link
                >
                <nav
                    class="public-nav"
                    :aria-label="t('publicSite.navigation')"
                >
                    <Link href="/features">{{ t('publicSite.catalog') }}</Link>
                    <Link v-if="!$page.props.auth.user" href="/login">{{
                        t('publicSite.signIn')
                    }}</Link>
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
                    <Link href="/features">{{ t('publicSite.catalog') }}</Link
                    ><Link href="/privacy">{{ t('publicSite.privacy') }}</Link
                    ><Link href="/terms">{{ t('publicSite.terms') }}</Link>
                </nav>
            </footer>
        </div>
        <CookieNotice />
    </div>
</template>
