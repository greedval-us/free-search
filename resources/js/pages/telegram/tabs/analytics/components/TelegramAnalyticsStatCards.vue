<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';

type StatCard = {
    label: string;
    value: number | string;
    delta: string | null;
};

defineProps<{
    cards: StatCard[];
}>();
const { t } = useI18n();

const formatNumber = (value: number | null | undefined) => {
    const numeric = Number(value);

    return new Intl.NumberFormat().format(Number.isFinite(numeric) ? numeric : 0);
};
</script>

<template>
    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        <article
            v-for="card in cards"
            :key="card.label"
            class="rounded-xl border border-sidebar-border/80 bg-card/75 p-4 shadow-xl backdrop-blur"
        >
            <p class="text-xs uppercase tracking-wide text-muted-foreground">{{ card.label }}</p>
            <p class="mt-2 text-2xl font-semibold">
                {{ typeof card.value === 'number' ? formatNumber(card.value) : card.value }}
            </p>
            <p v-if="card.delta !== null" class="mt-1 text-xs text-muted-foreground">
                {{ t('telegram.analytics.vsPrevious') }}: {{ card.delta }}
            </p>
        </article>
    </div>
</template>
