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
        title: 'auth.register.title',
        description: 'auth.register.description',
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
                <Label for="name" class="auth-field-label">{{
                    t('auth.register.login')
                }}</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="username"
                    name="name"
                    pattern="[A-Za-z0-9\s.\'-]+"
                    title="Use Latin letters, numbers, spaces and . ' - only"
                    :placeholder="t('auth.register.loginPlaceholder')"
                    class="auth-input-skin"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email" class="auth-field-label">{{
                    t('auth.register.email')
                }}</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    :placeholder="t('auth.register.emailPlaceholder')"
                    class="auth-input-skin"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password" class="auth-field-label">{{
                    t('auth.register.password')
                }}</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    :placeholder="t('auth.register.passwordPlaceholder')"
                    class="auth-input-skin"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation" class="auth-field-label">{{
                    t('auth.register.confirmPassword')
                }}</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    :placeholder="t('auth.register.confirmPassword')"
                    class="auth-input-skin"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-start gap-3 rounded-xl border border-slate-700/70 bg-slate-900/30 p-3">
                    <input
                        id="accept_service_rules"
                        name="accept_service_rules"
                        type="checkbox"
                        value="1"
                        required
                        class="mt-0.5 h-4 w-4 rounded border-slate-500 bg-slate-950 text-cyan-400 focus:ring-cyan-400"
                    />
                    <Label
                        for="accept_service_rules"
                        class="auth-field-label leading-6 text-slate-200"
                    >
                        {{ t('auth.register.acceptPrefix') }}
                        <TextLink
                            href="/terms"
                            class="auth-link underline underline-offset-4"
                        >
                            {{ t('auth.register.acceptLink') }}
                        </TextLink>
                    </Label>
                </div>
                <InputError :message="errors.accept_service_rules" />
            </div>

            <Button
                type="submit"
                class="auth-button-primary mt-2"
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
                class="auth-link underline underline-offset-4"
                :tabindex="6"
                >{{ t('auth.register.signIn') }}</TextLink
            >
        </div>
    </Form>
</template>
