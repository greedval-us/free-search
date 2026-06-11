<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Check,
    CreditCard,
    Sparkles,
} from 'lucide-vue-next';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/composables/useI18n';
import type { AccountAccess } from '@/types';

const props = defineProps<{
    access: AccountAccess;
    plans: Record<string, Record<string, number>>;
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

const planKeys = ['free', 'plus', 'pro'] as const;
const currentPlan = computed(() => props.access.plan);

const replaceToken = (
    template: string,
    token: string,
    value: string
): string => template.replace(`:${token}`, value);

const featureLabel = computed(() => {
    if (!props.feature) {
        return '';
    }

    return props.feature.replaceAll('.', ' / ').replaceAll('-', ' ');
});

const formatDate = (value: string | null | undefined): string => {
    if (!value) {
        return t('settings.billingPage.noExpiry');
    }

    return new Date(value).toLocaleDateString(locale.value);
};

const planCards = computed(() =>
    planKeys.map((plan) => ({
        key: plan,
        name: t(`settings.billingPage.plans.${plan}.name`),
        price: t(`settings.billingPage.plans.${plan}.price`),
        tagline: t(`settings.billingPage.plans.${plan}.tagline`),
        description: t(`settings.billingPage.plans.${plan}.description`),
        highlights: [
            t(`settings.billingPage.plans.${plan}.highlights.first`),
            t(`settings.billingPage.plans.${plan}.highlights.second`),
            t(`settings.billingPage.plans.${plan}.highlights.third`),
        ],
        analytics: props.plans[plan]?.analytics ?? 0,
        parser: props.plans[plan]?.parser ?? 0,
        seoAudit: props.plans[plan]?.['site-intel.seo-audit'] ?? 0,
        isCurrent: currentPlan.value === plan,
        isPopular: plan === 'plus',
        actionHref:
            plan === 'free'
                ? '/settings/billing'
                : `/settings/placeholder?context=checkout&plan=${plan}&back=/settings/billing`,
    }))
);

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

const footerAction = computed(() => {
    if (currentPlan.value === 'free') {
        return {
            href: '/settings/placeholder?context=checkout&plan=plus&back=/settings/billing',
            label: t('settings.billingPage.footer.upgradePlus'),
        };
    }

    if (currentPlan.value === 'plus') {
        return {
            href: '/settings/placeholder?context=checkout&plan=pro&back=/settings/billing',
            label: t('settings.billingPage.footer.upgradePro'),
        };
    }

    return {
        href: '/settings/placeholder?context=generic&back=/settings/billing',
        label: t('settings.billingPage.footer.customAccess'),
    };
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

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('settings.billingPage.heading')"
            :description="t('settings.billingPage.description')"
        />

        <section
            class="overflow-hidden rounded-2xl border border-sidebar-border/70 bg-background/40 shadow-xl"
        >
            <div
                class="border-b border-sidebar-border/70 bg-[radial-gradient(circle_at_top_right,rgba(56,189,248,0.16),transparent_36%),radial-gradient(circle_at_bottom_left,rgba(16,185,129,0.14),transparent_32%)] p-5"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-2">
                        <p class="text-xs tracking-[0.2em] text-cyan-200 uppercase">
                            {{ t('settings.billingPage.hero.badge') }}
                        </p>
                        <h2 class="text-2xl font-semibold sm:text-3xl">
                            {{ t('settings.billingPage.hero.title') }}
                        </h2>
                        <p class="max-w-2xl text-sm leading-6 text-muted-foreground">
                            {{ t('settings.billingPage.hero.text') }}
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-cyan-400/20 bg-slate-950/35 p-4 text-right"
                    >
                        <p class="text-xs tracking-wide text-muted-foreground uppercase">
                            {{ t('settings.billingPage.currentPlan') }}
                        </p>
                        <p class="mt-2 text-2xl font-semibold uppercase">
                            {{ t(`settings.billingPage.plans.${currentPlan}.name`) }}
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ t('settings.billingPage.validUntil') }}:
                            {{ formatDate(access.subscription?.ends_at) }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section
            v-if="reasonCard"
            class="rounded-2xl border border-amber-400/25 bg-amber-400/10 p-4"
        >
            <div class="flex items-start gap-3">
                <Sparkles class="mt-0.5 h-5 w-5 text-amber-300" />
                <div>
                    <h3 class="font-semibold text-amber-50">
                        {{ reasonCard.title }}
                    </h3>
                    <p class="mt-1 text-sm leading-6 text-amber-100/90">
                        {{ reasonCard.text }}
                    </p>
                </div>
            </div>
        </section>

        <section class="grid gap-4 xl:grid-cols-3">
            <article
                v-for="plan in planCards"
                :key="plan.key"
                class="relative flex h-full flex-col overflow-hidden rounded-2xl border border-sidebar-border/70 bg-background/40 p-5 shadow-lg"
                :class="[
                    plan.isCurrent
                        ? 'border-primary/60 ring-1 ring-primary/30'
                        : '',
                    plan.isPopular ? 'xl:-translate-y-2' : '',
                ]"
            >
                <div
                    v-if="plan.isPopular"
                    class="absolute top-4 right-4 rounded-full bg-cyan-400/15 px-2.5 py-1 text-xs font-semibold text-cyan-200"
                >
                    {{ t('settings.billingPage.popular') }}
                </div>

                <div class="space-y-3">
                    <div>
                        <p class="text-xs tracking-[0.2em] text-muted-foreground uppercase">
                            {{ plan.tagline }}
                        </p>
                        <h3 class="mt-2 text-2xl font-semibold">
                            {{ plan.name }}
                        </h3>
                        <p class="mt-1 text-3xl font-semibold">
                            {{ plan.price }}
                        </p>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">
                            {{ plan.description }}
                        </p>
                    </div>

                    <ul class="space-y-2 text-sm">
                        <li class="flex items-start gap-2">
                            <Check class="mt-0.5 h-4 w-4 text-emerald-300" />
                            <span>
                                {{ t('settings.billingPage.analytics') }}:
                                {{ plan.analytics }}
                            </span>
                        </li>
                        <li class="flex items-start gap-2">
                            <Check class="mt-0.5 h-4 w-4 text-emerald-300" />
                            <span>
                                {{ t('settings.billingPage.parser') }}:
                                {{ plan.parser }}
                            </span>
                        </li>
                        <li class="flex items-start gap-2">
                            <Check class="mt-0.5 h-4 w-4 text-emerald-300" />
                            <span>
                                {{ t('settings.billingPage.compare.seoAudit') }}:
                                {{ plan.seoAudit }}
                            </span>
                        </li>
                    </ul>

                    <div class="rounded-xl border border-sidebar-border/70 bg-background/50 p-4">
                        <p class="text-xs tracking-wide text-muted-foreground uppercase">
                            {{ t('settings.billingPage.includes') }}
                        </p>
                        <ul class="mt-3 space-y-2 text-sm leading-6">
                            <li v-for="item in plan.highlights" :key="item">
                                {{ item }}
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="mt-auto pt-5">
                    <Button
                        v-if="plan.isCurrent"
                        class="w-full rounded-xl"
                        type="button"
                        disabled
                    >
                        {{ t('settings.billingPage.currentButton') }}
                    </Button>

                    <Button
                        v-else-if="plan.key === 'free'"
                        variant="outline"
                        class="w-full rounded-xl"
                        type="button"
                        disabled
                    >
                        {{ t('settings.billingPage.freeButton') }}
                    </Button>

                    <Button v-else class="w-full rounded-xl" as-child>
                        <Link :href="plan.actionHref">
                            <CreditCard class="h-4 w-4" />
                            {{ t('settings.billingPage.payButton') }}
                        </Link>
                    </Button>
                </div>
            </article>
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

                <div class="flex flex-wrap gap-3">
                    <Button variant="outline" as-child class="rounded-xl">
                        <Link href="/dashboard">
                            {{ t('settings.billingPage.backDashboard') }}
                        </Link>
                    </Button>

                    <Button as-child class="rounded-xl">
                        <Link :href="footerAction.href">
                            {{ footerAction.label }}
                        </Link>
                    </Button>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-sidebar-border/70 bg-background/40 p-5">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
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
                            class="h-11 w-full rounded-xl border border-sidebar-border/70 bg-background/70 px-4 text-sm outline-none transition focus:border-primary"
                            :placeholder="t('settings.billingPage.token.placeholder')"
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
                        class="text-sm text-destructive"
                    >
                        {{ activationForm.errors.activation_token }}
                    </p>

                    <p
                        v-else-if="tokenStatus === 'success'"
                        class="text-sm text-emerald-300"
                    >
                        {{ t('settings.billingPage.token.success') }}
                    </p>
                </form>
            </div>
        </section>
    </div>
</template>
