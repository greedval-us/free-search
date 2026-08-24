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

    <div class="space-y-5">
        <div
            class="rounded-xl border border-cyan-400/20 bg-cyan-400/[0.06] p-4"
        >
            <div class="flex items-start gap-4">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-cyan-300/30 bg-cyan-300/10 text-cyan-100"
                >
                    <ShieldCheck class="size-6" />
                </div>

                <div class="space-y-2">
                    <p
                        class="text-xs font-semibold tracking-[0.24em] text-cyan-200 uppercase"
                    >
                        {{ t('auth.verifyEmail.badge') }}
                    </p>
                    <p class="text-sm leading-6 text-slate-200">
                        {{ t('auth.verifyEmail.intro') }}
                    </p>
                    <p class="text-sm leading-6 text-slate-400">
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

        <div
            class="grid divide-y divide-slate-800 rounded-xl border border-slate-800 bg-slate-950/30 sm:grid-cols-3 sm:divide-x sm:divide-y-0"
        >
            <div class="p-3">
                <div
                    class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-slate-900 text-cyan-100"
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

            <div class="p-3">
                <div
                    class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-slate-900 text-emerald-100"
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

            <div class="p-3">
                <div
                    class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-slate-900 text-sky-100"
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

        <div class="rounded-xl border border-slate-800 bg-slate-900/50 p-4">
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
