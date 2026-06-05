<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useI18n } from '@/composables/useI18n';
import { store } from '@/routes/password/confirm';

const { t } = useI18n();

defineOptions({
    layout: {
        title: 'auth.confirmPassword.title',
        description: 'auth.confirmPassword.description',
    },
});
</script>

<template>
    <Head :title="t('auth.confirmPassword.title')" />

    <Form
        v-bind="store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
    >
        <div class="space-y-6">
            <div class="grid gap-2">
                <Label htmlFor="password" class="auth-field-label">{{
                    t('auth.confirmPassword.password')
                }}</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="auth-input-skin mt-1 block w-full"
                    required
                    autocomplete="current-password"
                    autofocus
                />

                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center">
                <Button
                    class="auth-button-primary"
                    :disabled="processing"
                    data-test="confirm-password-button"
                >
                    <Spinner v-if="processing" />
                    {{ t('auth.confirmPassword.submit') }}
                </Button>
            </div>
        </div>
    </Form>
</template>
