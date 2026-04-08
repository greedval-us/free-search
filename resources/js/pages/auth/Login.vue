<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useI18n } from '@/composables/useI18n';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

const { t } = useI18n();

defineOptions({
    layout: {
        title: 'Sign in',
        description: 'Use your account to access Telegram analytics dashboard',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <Head :title="t('auth.login.title')" />

    <div
        v-if="status"
        class="mb-4 rounded-lg border border-emerald-300/30 bg-emerald-400/10 p-3 text-center text-sm font-medium text-emerald-200"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email" class="text-slate-200">{{ t('auth.login.email') }}</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    :placeholder="t('auth.login.emailPlaceholder')"
                    class="border-slate-700 bg-slate-900/80 text-slate-100 placeholder:text-slate-400"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password" class="text-slate-200">{{ t('auth.login.password') }}</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm text-cyan-300 hover:text-cyan-200"
                        :tabindex="5"
                    >
                        {{ t('auth.login.forgotPassword') }}
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    :placeholder="t('auth.login.passwordPlaceholder')"
                    class="border-slate-700 bg-slate-900/80 text-slate-100 placeholder:text-slate-400"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3 text-slate-300">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span>{{ t('auth.login.remember') }}</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-4 w-full bg-cyan-400 text-slate-950 hover:bg-cyan-300"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                {{ t('auth.login.submit') }}
            </Button>
        </div>

        <div
            class="text-center text-sm text-slate-300"
            v-if="canRegister"
        >
            {{ t('auth.login.noAccount') }}
            <TextLink :href="register()" :tabindex="5" class="text-cyan-300 hover:text-cyan-200">{{ t('auth.login.createOne') }}</TextLink>
        </div>
    </Form>
</template>
