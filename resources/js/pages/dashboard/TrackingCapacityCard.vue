<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, Radar } from 'lucide-vue-next';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import { useI18n } from '@/composables/useI18n';
import type { TrackingCapacity } from '../telegram/tracking/types';

defineProps<{ capacity: TrackingCapacity }>();
const { t } = useI18n();
</script>

<template>
    <section
        class="intel-panel min-w-0 border-primary/25 bg-primary/5"
        :aria-label="t('dashboard.tracking.title')"
    >
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2">
                <Radar
                    class="size-5 shrink-0 text-primary"
                    aria-hidden="true"
                />
                <h2 class="text-base font-semibold">
                    {{ t('dashboard.tracking.title') }}
                </h2>
                <HelpTooltip
                    :label="t('dashboard.tracking.title')"
                    :text="t('dashboard.tracking.help')"
                />
            </div>
            <Link href="/telegram?tab=tracking" class="intel-button-secondary"
                >{{ t('dashboard.tracking.open')
                }}<ArrowUpRight class="size-4" aria-hidden="true"
            /></Link>
        </div>
        <dl class="mt-3 grid grid-cols-3 gap-2 sm:gap-4">
            <div
                v-for="(value, key) in {
                    active: capacity.active_count,
                    remaining: capacity.remaining,
                    limit: capacity.limit,
                }"
                :key="key"
                class="min-w-0 rounded-lg border border-border/60 bg-card/60 p-2 sm:p-3"
            >
                <dt
                    class="text-xs [overflow-wrap:anywhere] text-muted-foreground"
                >
                    {{ t(`dashboard.tracking.${key}`) }}
                </dt>
                <dd
                    class="mt-1 text-2xl font-semibold tabular-nums"
                    :class="key === 'remaining' ? 'text-primary' : ''"
                >
                    {{ value }}
                </dd>
            </div>
        </dl>
    </section>
</template>
