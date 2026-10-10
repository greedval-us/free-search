<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Sparkles } from 'lucide-vue-next';
import { computed } from 'vue';
import SettingsHero from '@/components/settings/SettingsHero.vue';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/composables/useI18n';
import type { AccountAccess } from '@/types';

const props = defineProps<{
    access: AccountAccess;
    plans: Record<string, Record<string, number>>;
    checkoutEnabled: boolean;
    reason?: string | null;
    feature?: string | null;
    tokenStatus?: string | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Billing',
                titleKey: 'settings.billingPage.title',
                href: '/settings/billing',
            },
        ],
    },
});

const { t, locale } = useI18n();

const featureLabelKeys: Record<string, string> = {
    'bluesky.analytics': 'blueskyAnalytics',
    'bluesky.parser': 'blueskyCollection',
    'mastodon.analytics': 'mastodonAnalytics',
    'mastodon.parser': 'mastodonCollection',
    'site-intel.analytics': 'siteIntelAnalytics',
    'site-intel.seo-audit': 'siteIntelSeoAudit',
    'telegram.analytics': 'telegramAnalytics',
    'telegram.parser': 'telegramCollection',
    'youtube.analytics': 'youtubeAnalytics',
    'youtube.parser': 'youtubeCollection',
};
const currentPlan = computed(() => props.access.plan);

const replaceToken = (template: string, token: string, value: string): string =>
    template.replace(`:${token}`, value);

const featureLabel = computed(() => {
    if (!props.feature) {
        return '';
    }

    const labelKey = featureLabelKeys[props.feature] ?? 'unknown';

    return t(`settings.billingPage.reason.features.${labelKey}`);
});

const formatDate = (value: string | null | undefined): string => {
    if (!value) {
        return t('settings.billingPage.noExpiry');
    }

    return new Date(value).toLocaleDateString(locale.value);
};

const reasonCard = computed(() => {
    if (props.reason === 'plan') {
        return {
            title: t('settings.billingPage.reason.planTitle'),
            text: props.feature
                ? replaceToken(
                      t('settings.billingPage.reason.planTextWithFeature'),
                      'feature',
                      featureLabel.value
                  )
                : t('settings.billingPage.reason.planText'),
        };
    }

    if (props.reason === 'quota') {
        return {
            title: t('settings.billingPage.reason.quotaTitle'),
            text: props.feature
                ? replaceToken(
                      t('settings.billingPage.reason.quotaTextWithFeature'),
                      'feature',
                      featureLabel.value
                  )
                : t('settings.billingPage.reason.quotaText'),
        };
    }

    return null;
});

const activationForm = useForm({
    activation_token: '',
});

const submitActivationToken = (): void => {
    activationForm.post('/settings/billing/activate-token', {
        preserveScroll: true,
        onSuccess: () => activationForm.reset('activation_token'),
    });
};
</script>

<template>
    <Head :title="t('settings.billingPage.title')" />

    <div class="max-w-5xl space-y-5">
        <SettingsHero
            :badge="t('settings.billingPage.hero.badge')"
            :title="t('settings.billingPage.hero.title')"
            :description="t('settings.billingPage.hero.textDisabled')"
        >
            <template #summary>
                <p
                    class="text-xs tracking-wide text-muted-foreground uppercase"
                >
                    {{ t('settings.billingPage.currentPlan') }}
                </p>
                <p class="mt-2 text-2xl font-semibold uppercase">
                    {{ t(`settings.billingPage.plans.${currentPlan}.name`) }}
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ t('settings.billingPage.validUntil') }}:
                    {{ formatDate(access.subscription?.ends_at) }}
                </p>
            </template>
        </SettingsHero>

        <section
            v-if="reasonCard"
            class="rounded-2xl border border-amber-400/25 bg-amber-400/10 p-4"
        >
            <div class="flex items-start gap-3">
                <Sparkles
                    class="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-300"
                />
                <div class="min-w-0">
                    <h3 class="font-semibold text-amber-900 dark:text-amber-50">
                        {{ reasonCard.title }}
                    </h3>
                    <p
                        class="mt-1 text-sm leading-6 text-amber-800 dark:text-amber-100/90"
                    >
                        {{ reasonCard.text }}
                    </p>
                </div>
            </div>
        </section>

        <section
            class="rounded-2xl border border-sidebar-border/70 bg-background/40 p-5"
        >
            <div
                class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between"
            >
                <div class="max-w-2xl">
                    <h2 class="text-lg font-semibold">
                        {{ t('settings.billingPage.token.title') }}
                    </h2>
                    <p class="mt-1 text-sm leading-6 text-muted-foreground">
                        {{ t('settings.billingPage.token.description') }}
                    </p>
                </div>

                <form
                    class="w-full max-w-xl space-y-3"
                    @submit.prevent="submitActivationToken"
                >
                    <label
                        for="activation_token"
                        class="text-sm font-medium text-foreground"
                    >
                        {{ t('settings.billingPage.token.label') }}
                    </label>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <input
                            id="activation_token"
                            v-model="activationForm.activation_token"
                            type="text"
                            class="h-11 w-full rounded-xl border border-sidebar-border/70 bg-background/70 px-4 text-sm transition outline-none focus:border-primary"
                            :placeholder="
                                t('settings.billingPage.token.placeholder')
                            "
                            autocomplete="off"
                        />

                        <Button
                            type="submit"
                            class="h-11 rounded-xl px-6"
                            :disabled="activationForm.processing"
                        >
                            {{ t('settings.billingPage.token.button') }}
                        </Button>
                    </div>

                    <p
                        v-if="activationForm.errors.activation_token"
                        class="text-sm break-words text-destructive"
                    >
                        {{ activationForm.errors.activation_token }}
                    </p>

                    <p
                        v-else-if="tokenStatus === 'success'"
                        class="text-sm text-emerald-700 dark:text-emerald-300"
                    >
                        {{ t('settings.billingPage.token.success') }}
                    </p>
                </form>
            </div>
        </section>

        <section
            class="rounded-2xl border border-sidebar-border/70 bg-background/40 p-5"
        >
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">
                        {{ t('settings.billingPage.supportTitle') }}
                    </h2>
                    <p class="mt-1 text-sm leading-6 text-muted-foreground">
                        {{ t('settings.billingPage.supportText') }}
                    </p>
                </div>

                <div class="grid gap-3 sm:flex sm:flex-wrap">
                    <Button variant="outline" as-child class="rounded-xl">
                        <Link href="/dashboard">
                            {{ t('settings.billingPage.backDashboard') }}
                        </Link>
                    </Button>
                </div>
            </div>
        </section>
    </div>
</template>
