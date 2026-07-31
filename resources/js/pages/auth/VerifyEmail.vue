<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { CheckCheck, Inbox, RefreshCw, ShieldCheck } from 'lucide-vue-next';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useI18n } from '@/composables/useI18n';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

const { t } = useI18n();

defineOptions({
    layout: {
        title: 'auth.verifyEmail.title',
        description: 'auth.verifyEmail.description',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head :title="t('auth.verifyEmail.title')" />

    <div class="space-y-6">
        <div
            class="rounded-3xl border border-cyan-400/20 bg-linear-to-br from-cyan-500/14 via-slate-900/70 to-emerald-500/10 p-6 shadow-[0_24px_80px_-40px_rgba(34,211,238,0.55)]"
        >
            <div class="flex items-start gap-4">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-cyan-300/30 bg-cyan-300/12 text-cyan-100"
                >
                    <ShieldCheck class="size-6" />
                </div>

                <div class="space-y-3">
                    <p
                        class="text-xs font-semibold tracking-[0.24em] text-cyan-200 uppercase"
                    >
                        {{ t('auth.verifyEmail.badge') }}
                    </p>
                    <p class="text-sm leading-7 text-slate-200">
                        {{ t('auth.verifyEmail.intro') }}
                    </p>
                    <p class="text-sm leading-7 text-slate-400">
                        {{ t('auth.verifyEmail.hint') }}
                    </p>
                </div>
            </div>
        </div>

        <div
            v-if="status === 'verification-link-sent'"
            class="auth-status auth-status-success"
        >
            {{ t('auth.verifyEmail.statusMessage') }}
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <div
                class="rounded-2xl border border-slate-700/70 bg-slate-950/45 p-4"
            >
                <div
                    class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl border border-slate-700 bg-slate-900/80 text-cyan-100"
                >
                    <Inbox class="size-5" />
                </div>
                <p class="text-sm font-medium text-slate-100">
                    {{ t('auth.verifyEmail.stepInboxTitle') }}
                </p>
                <p class="mt-2 text-sm leading-6 text-slate-400">
                    {{ t('auth.verifyEmail.stepInboxText') }}
                </p>
            </div>

            <div
                class="rounded-2xl border border-slate-700/70 bg-slate-950/45 p-4"
            >
                <div
                    class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl border border-slate-700 bg-slate-900/80 text-emerald-100"
                >
                    <CheckCheck class="size-5" />
                </div>
                <p class="text-sm font-medium text-slate-100">
                    {{ t('auth.verifyEmail.stepConfirmTitle') }}
                </p>
                <p class="mt-2 text-sm leading-6 text-slate-400">
                    {{ t('auth.verifyEmail.stepConfirmText') }}
                </p>
            </div>

            <div
                class="rounded-2xl border border-slate-700/70 bg-slate-950/45 p-4"
            >
                <div
                    class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl border border-slate-700 bg-slate-900/80 text-sky-100"
                >
                    <RefreshCw class="size-5" />
                </div>
                <p class="text-sm font-medium text-slate-100">
                    {{ t('auth.verifyEmail.stepResendTitle') }}
                </p>
                <p class="mt-2 text-sm leading-6 text-slate-400">
                    {{ t('auth.verifyEmail.stepResendText') }}
                </p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/40 p-5">
            <p class="text-sm font-medium text-slate-100">
                {{ t('auth.verifyEmail.actionTitle') }}
            </p>
            <p class="mt-2 text-sm leading-6 text-slate-400">
                {{ t('auth.verifyEmail.actionText') }}
            </p>
        </div>

        <Form v-bind="send.form()" class="space-y-4" v-slot="{ processing }">
            <Button :disabled="processing" class="auth-button-primary w-full">
                <Spinner v-if="processing" />
                {{ t('auth.verifyEmail.submit') }}
            </Button>

            <TextLink
                :href="logout()"
                as="button"
                class="auth-link mx-auto block text-center text-sm"
            >
                {{ t('auth.verifyEmail.logout') }}
            </TextLink>
        </Form>
    </div>
</template>
