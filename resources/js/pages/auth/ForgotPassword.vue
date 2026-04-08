<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useI18n } from '@/composables/useI18n';
import { login } from '@/routes';
import { email } from '@/routes/password';

const { t } = useI18n();

defineOptions({
    layout: {
        title: 'Password recovery',
        description: 'Enter your email and we will send a reset link',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head :title="t('auth.forgotPassword.title')" />

    <div
        v-if="status"
        class="mb-4 rounded-lg border border-emerald-300/30 bg-emerald-400/10 p-3 text-center text-sm font-medium text-emerald-200"
    >
        {{ status }}
    </div>

    <div class="space-y-6">
        <Form v-bind="email.form()" v-slot="{ errors, processing }">
            <div class="grid gap-2">
                <Label for="email" class="text-slate-200">{{ t('auth.forgotPassword.email') }}</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="off"
                    autofocus
                    :placeholder="t('auth.forgotPassword.emailPlaceholder')"
                    class="border-slate-700 bg-slate-900/80 text-slate-100 placeholder:text-slate-400"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    class="w-full bg-cyan-400 text-slate-950 hover:bg-cyan-300"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                >
                    <Spinner v-if="processing" />
                    {{ t('auth.forgotPassword.submit') }}
                </Button>
            </div>
        </Form>

        <div class="space-x-1 text-center text-sm text-slate-300">
            <span>{{ t('auth.forgotPassword.backTo') }}</span>
            <TextLink :href="login()" class="text-cyan-300 hover:text-cyan-200">{{ t('auth.forgotPassword.signIn') }}</TextLink>
        </div>
    </div>
</template>
