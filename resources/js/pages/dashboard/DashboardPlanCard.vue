<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronDown, CreditCard } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { edit as billing } from '@/routes/billing';
import type { AccountAccess } from '@/types/auth';
const props = defineProps<{ access: AccountAccess }>();
const { t } = useI18n();
const quotaLabel = (quota: { limit: number; remaining: number }): string => {
    if (!quota || quota.limit === 0) {
        return t('dashboard.plan.unavailable');
    }

    return `${quota.remaining}/${quota.limit}`;
};

const quotaGroups = computed(() => {
    const groups: Record<
        string,
        Array<{
            key: string;
            capability: string;
            limit: number;
            remaining: number;
        }>
    > = {};

    for (const [key, quota] of Object.entries(props.access.features)) {
        if (!key.includes('.')) {
            continue;
        }

        const [module, ...capabilityParts] = key.split('.');
        const capability = capabilityParts.join('.');

        groups[module] ??= [];
        groups[module].push({
            key,
            capability,
            limit: quota.limit,
            remaining: quota.remaining,
        });
    }

    return Object.entries(groups).map(([module, items]) => ({
        module,
        items,
    }));
});

const quotaModuleLabel = (module: string): string => {
    const translationKey = `dashboard.plan.modules.${module}`;
    const translated = t(translationKey);

    return translated === translationKey ? module : translated;
};

const quotaCapabilityLabel = (capability: string): string => {
    const translationKey = `dashboard.plan.capabilities.${capability}`;
    const translated = t(translationKey);

    return translated === translationKey ? capability : translated;
};
</script>
<template>
    <section class="intel-panel bg-card/80">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="intel-kicker">
                    {{ t('dashboard.plan.title') }}
                </p>
                <h2 class="mt-1 text-lg font-semibold uppercase">
                    {{ access.plan }}
                </h2>
            </div>
            <Link
                :href="billing()"
                class="intel-button-ghost min-h-11 rounded-xl px-3 text-sm"
            >
                <CreditCard class="h-4 w-4" />
                {{ t('dashboard.plan.manage') }}
            </Link>
        </div>
        <details class="group mt-4 border-t border-border/70 pt-3">
            <summary
                class="flex min-h-11 cursor-pointer items-center justify-between gap-3 rounded-lg text-sm font-medium focus-visible:outline-2 focus-visible:outline-ring"
            >
                {{ t('dashboard.plan.showLimits')
                }}<ChevronDown
                    class="size-4 shrink-0 transition-transform group-open:rotate-180"
                    aria-hidden="true"
                />
            </summary>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="group in quotaGroups"
                    :key="group.module"
                    class="intel-list-item"
                >
                    <p class="intel-kicker">
                        {{ quotaModuleLabel(group.module) }}
                    </p>
                    <dl class="mt-2 space-y-1.5 text-sm">
                        <div
                            v-for="item in group.items"
                            :key="item.key"
                            class="flex items-center justify-between gap-3"
                        >
                            <dt
                                class="min-w-0 break-words text-muted-foreground"
                            >
                                {{ quotaCapabilityLabel(item.capability) }}
                            </dt>
                            <dd class="shrink-0 font-semibold">
                                {{ quotaLabel(item) }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </details>
    </section>
</template>
