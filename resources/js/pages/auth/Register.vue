<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useI18n } from '@/composables/useI18n';
import { login } from '@/routes';
import { store } from '@/routes/register';

const { t } = useI18n();

defineOptions({
    layout: {
        title: 'Create account',
        description: 'Register to start monitoring and analytics workflows',
    },
});
</script>

<template>
    <Head :title="t('auth.register.title')" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name" class="text-slate-200">{{ t('auth.register.name') }}</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    :placeholder="t('auth.register.namePlaceholder')"
                    class="border-slate-700 bg-slate-900/80 text-slate-100 placeholder:text-slate-400"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email" class="text-slate-200">{{ t('auth.register.email') }}</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    :placeholder="t('auth.register.emailPlaceholder')"
                    class="border-slate-700 bg-slate-900/80 text-slate-100 placeholder:text-slate-400"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password" class="text-slate-200">{{ t('auth.register.password') }}</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    :placeholder="t('auth.register.passwordPlaceholder')"
                    class="border-slate-700 bg-slate-900/80 text-slate-100 placeholder:text-slate-400"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation" class="text-slate-200">{{ t('auth.register.confirmPassword') }}</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    :placeholder="t('auth.register.confirmPassword')"
                    class="border-slate-700 bg-slate-900/80 text-slate-100 placeholder:text-slate-400"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full bg-cyan-400 text-slate-950 hover:bg-cyan-300"
                tabindex="5"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                {{ t('auth.register.submit') }}
            </Button>
        </div>

        <div class="text-center text-sm text-slate-300">
            {{ t('auth.register.hasAccount') }}
            <TextLink
                :href="login()"
                class="text-cyan-300 underline underline-offset-4 hover:text-cyan-200"
                :tabindex="6"
                >{{ t('auth.register.signIn') }}</TextLink
            >
        </div>
    </Form>
</template>
