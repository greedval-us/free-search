<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/composables/useI18n';

const COOKIE_NAME = 'cookie_notice_accepted';
const COOKIE_MAX_AGE = 60 * 60 * 24 * 180;

const visible = ref(false);
const theme = ref<'light' | 'dark'>('light');
const { t } = useI18n();

const hasConsent = (): boolean => {
    if (typeof document === 'undefined') {
        return true;
    }

    return document.cookie
        .split(';')
        .some((item) => item.trim().startsWith(`${COOKIE_NAME}=true`));
};

const acceptCookies = (): void => {
    if (typeof document !== 'undefined') {
        document.cookie =
            `${COOKIE_NAME}=true; path=/; max-age=${COOKIE_MAX_AGE}; SameSite=Lax`;
    }

    visible.value = false;
};

onMounted(() => {
    if (typeof document !== 'undefined') {
        theme.value = document.documentElement.classList.contains('dark')
            ? 'dark'
            : 'light';
    }

    visible.value = !hasConsent();
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-6 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-4 opacity-0"
        >
            <div v-if="visible" class="cookie-notice-wrap">
                <div class="cookie-notice" :data-theme="theme">
                    <div class="cookie-copy">
                        <p class="cookie-title">
                            {{ t('common.cookies.title') }}
                        </p>
                    <p class="cookie-text">
                        {{ t('common.cookies.text') }}
                    </p>
                    <Link href="/privacy" class="cookie-link">
                        {{ t('common.cookies.details') }}
                    </Link>
                </div>

                    <Button
                        type="button"
                        class="cookie-button"
                        @click="acceptCookies"
                    >
                        {{ t('common.cookies.accept') }}
                    </Button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.cookie-notice-wrap {
    position: fixed;
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 100;
    pointer-events: none;
}

.cookie-notice {
    pointer-events: auto;
    margin: 0 auto;
    display: flex;
    width: min(100%, 100rem);
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    border-top: 1px solid rgba(148, 163, 184, 0.2);
    padding: 1rem 1.25rem;
    backdrop-filter: blur(16px);
    box-shadow: 0 -16px 40px -28px rgba(15, 23, 42, 0.45);
}

.cookie-notice[data-theme='light'] {
    background: rgba(255, 255, 255, 0.92);
    color: #0f172a;
}

.cookie-notice[data-theme='dark'] {
    background: rgba(15, 23, 42, 0.92);
    color: #e2e8f0;
}

.cookie-copy {
    min-width: 0;
    flex: 1 1 32rem;
}

.cookie-title {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 600;
}

.cookie-text {
    margin: 0.35rem 0 0;
    font-size: 0.88rem;
    line-height: 1.5;
}

.cookie-link {
    display: inline-flex;
    margin-top: 0.6rem;
    color: inherit;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: underline;
    text-underline-offset: 0.2rem;
}

.cookie-button {
    flex: 0 0 auto;
    min-width: 8.5rem;
}

@media (max-width: 640px) {
    .cookie-notice {
        padding: 0.9rem 1rem calc(0.9rem + env(safe-area-inset-bottom));
    }

    .cookie-button {
        width: 100%;
    }
}
</style>
