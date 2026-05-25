<script setup lang="ts">
import { computed } from 'vue';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import { useI18n } from '@/composables/useI18n';
import type { TelegramAnalyticsSummary } from '../../../types';

type FraudSignals = TelegramAnalyticsSummary['summary']['fraudSignals'];

const props = defineProps<{
    signals: FraudSignals;
}>();

const { t } = useI18n();

const formatNumber = (value: number | null | undefined) => {
    const numeric = Number(value);

    return new Intl.NumberFormat().format(
        Number.isFinite(numeric) ? numeric : 0
    );
};

const formatDate = (unix: number) => {
    if (!unix) {
        return '-';
    }

    return new Date(unix * 1000).toLocaleString();
};

const riskLevelLabel = computed(() =>
    t(`telegram.analytics.fraud.level.${props.signals.riskLevel}`)
);

const riskBadgeClass = computed(() => {
    if (props.signals.riskLevel === 'high') {
        return 'border-red-500/40 bg-red-500/10 text-red-300';
    }

    if (props.signals.riskLevel === 'medium') {
        return 'border-amber-500/40 bg-amber-500/10 text-amber-300';
    }

    return 'border-emerald-500/40 bg-emerald-500/10 text-emerald-300';
});

const triggerLabel = (key: string): string =>
    t(`telegram.analytics.fraud.trigger.${key}`);
const reasonLabel = (key: string): string =>
    t(`telegram.analytics.fraud.reason.${key}`);
</script>

<template>
    <article class="intel-panel-strong">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-semibold">
                        {{ t('telegram.analytics.fraud.title') }}
                    </h3>
                    <HelpTooltip
                        :label="t('telegram.analytics.help.label')"
                        :text="t('telegram.analytics.help.antiFraud')"
                        width-class="w-64"
                        align="right"
                    />
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ t('telegram.analytics.fraud.hint') }}
                </p>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <span class="rounded-full border border-border px-2 py-1">
                    {{ t('telegram.analytics.fraud.riskScore') }}:
                    {{ formatNumber(signals.riskScore) }}
                </span>
                <span
                    class="rounded-full border px-2 py-1"
                    :class="riskBadgeClass"
                >
                    {{ t('telegram.analytics.fraud.riskLevel') }}:
                    {{ riskLevelLabel }}
                </span>
            </div>
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <div
                class="rounded-lg border border-border/70 bg-background/70 p-3"
            >
                <p
                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    {{ t('telegram.analytics.fraud.triggers') }}
                </p>
                <div class="mt-2 space-y-2">
                    <article
                        v-for="trigger in signals.triggers"
                        :key="`fraud-trigger-${trigger.key}`"
                        class="rounded-md border border-border/70 bg-background/80 p-2 text-xs"
                    >
                        <p class="font-semibold">
                            {{ triggerLabel(trigger.key) }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            +{{ trigger.score }} ·
                            {{ formatNumber(trigger.value) }} /
                            {{ formatNumber(trigger.threshold) }}
                        </p>
                    </article>
                    <p
                        v-if="signals.triggers.length === 0"
                        class="text-xs text-muted-foreground"
                    >
                        {{ t('telegram.analytics.fraud.noTriggers') }}
                    </p>
                </div>
            </div>

            <div
                class="rounded-lg border border-border/70 bg-background/70 p-3"
            >
                <p
                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    {{ t('telegram.analytics.fraud.suspiciousPosts') }}:
                    {{ formatNumber(signals.suspiciousPostsCount) }}
                </p>
                <div class="mt-2 space-y-2">
                    <article
                        v-for="post in signals.suspiciousPosts"
                        :key="`fraud-post-${post.id}`"
                        class="rounded-md border border-border/70 bg-background/80 p-2 text-xs"
                    >
                        <p class="font-semibold">
                            #{{ post.id }} · {{ formatDate(post.date) }}
                        </p>
                        <p class="mt-1 line-clamp-2 text-muted-foreground">
                            {{
                                post.message ||
                                t('telegram.analytics.emptyPost')
                            }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            {{ t('telegram.analytics.fraud.riskScore') }}:
                            {{ formatNumber(post.riskScore) }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            {{ post.reasons.map(reasonLabel).join(' · ') }}
                        </p>
                    </article>
                </div>
            </div>
        </div>
    </article>
</template>
