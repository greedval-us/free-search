<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Spinner from '@/components/ui/spinner/Spinner.vue';
import { useI18n } from '@/composables/useI18n';
import { edit } from '@/routes/profile';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                titleKey: 'settings.profilePage.title',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const { t } = useI18n();
</script>

<template>
    <Head :title="t('settings.profilePage.title')" />

    <div class="max-w-2xl space-y-8">
        <section class="space-y-5">
            <Heading
                variant="small"
                as="h1"
                :title="t('settings.profilePage.heading')"
                :description="t('settings.profilePage.headingDescription')"
            />

            <Form
                v-bind="ProfileController.update.form()"
                class="space-y-5"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <div class="grid gap-2">
                    <Label for="name">{{
                        t('settings.profilePage.name')
                    }}</Label>
                    <Input
                        id="name"
                        name="name"
                        :default-value="user.name"
                        required
                        autocomplete="username"
                        :placeholder="t('settings.profilePage.namePlaceholder')"
                    />
                    <p class="text-xs leading-5 text-muted-foreground">
                        {{ t('settings.profilePage.nameHint') }}
                    </p>
                    <InputError :message="errors.name" />
                </div>

                <div class="grid items-center gap-3 sm:flex sm:gap-4">
                    <Button
                        :disabled="processing"
                        data-test="update-profile-button"
                    >
                        <Spinner v-if="processing" class="mr-2" />
                        {{ t('settings.profilePage.save') }}
                    </Button>

                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p
                            v-show="recentlySuccessful"
                            class="text-sm text-muted-foreground"
                        >
                            {{ t('common.saved') }}
                        </p>
                    </Transition>
                </div>
            </Form>
        </section>

        <DeleteUser />
    </div>
</template>
