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
        title: 'auth.forgotPassword.title',
        description: 'auth.forgotPassword.description',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head :title="t('auth.forgotPassword.title')" />

    <div v-if="status" class="auth-status auth-status-success">
        {{ status }}
    </div>

    <div class="space-y-6">
        <Form v-bind="email.form()" v-slot="{ errors, processing }">
            <div class="grid gap-2">
                <Label for="email" class="auth-field-label">{{
                    t('auth.forgotPassword.email')
                }}</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="off"
                    autofocus
                    :placeholder="t('auth.forgotPassword.emailPlaceholder')"
                    class="auth-input-skin"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    class="auth-button-primary"
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
            <TextLink :href="login()" class="auth-link">{{
                t('auth.forgotPassword.signIn')
            }}</TextLink>
        </div>
    </div>
</template>
