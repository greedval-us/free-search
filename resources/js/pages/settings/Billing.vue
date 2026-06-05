<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Check, CreditCard, ShieldCheck } from 'lucide-vue-next';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/composables/useI18n';
import type { AccountAccess } from '@/types';

const props = defineProps<{
    access: AccountAccess;
    plans: Record<string, Record<string, number>>;
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

const planKeys = ['free', 'plus', 'pro'];
const currentPlan = computed(() => props.access.plan);

const formatDate = (value: string | null | undefined): string => {
    if (!value) {
        return t('settings.billingPage.noExpiry');
    }

    return new Date(value).toLocaleDateString(locale.value);
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
            class="rounded-lg border border-sidebar-border/70 bg-background/40 p-4"
        >
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p
                        class="text-xs tracking-wide text-muted-foreground uppercase"
                    >
                        {{ t('settings.billingPage.currentPlan') }}
                    </p>
                    <h2 class="mt-1 text-2xl font-semibold uppercase">
                        {{ currentPlan }}
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ t('settings.billingPage.validUntil') }}:
                        {{ formatDate(access.subscription?.ends_at) }}
                    </p>
                </div>
                <ShieldCheck class="h-7 w-7 text-cyan-300" />
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-3">
            <article
                v-for="plan in planKeys"
                :key="plan"
                class="rounded-lg border border-sidebar-border/70 bg-background/40 p-4"
                :class="plan === currentPlan ? 'border-primary/60' : ''"
            >
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold uppercase">{{ plan }}</h3>
                    <span
                        v-if="plan === currentPlan"
                        class="rounded-full bg-primary/15 px-2 py-0.5 text-xs text-primary"
                    >
                        {{ t('settings.billingPage.active') }}
                    </span>
                </div>

                <ul class="mt-4 space-y-2 text-sm">
                    <li class="flex items-center gap-2">
                        <Check class="h-4 w-4 text-emerald-300" />
                        {{ t('settings.billingPage.analytics') }}:
                        {{ plans[plan]?.analytics ?? 0 }}
                    </li>
                    <li class="flex items-center gap-2">
                        <Check class="h-4 w-4 text-emerald-300" />
                        {{ t('settings.billingPage.parser') }}:
                        {{ plans[plan]?.parser ?? 0 }}
                    </li>
                </ul>

                <Button class="mt-5 w-full" type="button" disabled>
                    <CreditCard class="h-4 w-4" />
                    {{ t('settings.billingPage.upgradesSoon') }}
                </Button>
            </article>
        </section>

        <Button variant="outline" as-child>
            <Link href="/dashboard">{{
                t('settings.billingPage.backDashboard')
            }}</Link>
        </Button>
    </div>
</template>
