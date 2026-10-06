<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/composables/useI18n';
import { show as projectShow } from '@/routes/monitoring/projects';
import {
    download,
    regenerate,
    show,
    status,
} from '@/routes/monitoring/reports';
import MaterialCard from './MaterialCard.vue';
import Pagination from './Pagination.vue';
import { displayDate, reportNeedsPolling } from './presentation';
import ReportSummary from './ReportSummary.vue';
import type { Material, Pagination as Page, Report } from './types';
import { useMonitoringState } from './useMonitoringState';
const props = defineProps<{
    report: Report;
    items: Page<{ id: number; snapshot: Material }>;
}>();
const { t, locale } = useI18n();
const { state, busy, error, mutate } = useMonitoringState(
    computed(() => ({ report: props.report })),
    computed(() => status.url(props.report.id)),
    (s) => reportNeedsPolling(s.report)
);
const requestKey = ref(crypto.randomUUID());
const ready = computed(
    () =>
        ['completed', 'partial', 'empty'].includes(state.value.report.status) &&
        !state.value.report.expired
);
watch(
    () => state.value.report.status,
    (next, previous) => {
        if (
            ['queued', 'building'].includes(previous) &&
            !['queued', 'building'].includes(next)
        ) {
            router.reload({ only: ['report', 'items'] });
        }
    }
);
const rebuild = async () => {
    const result = await mutate<{ id: number }>(
        regenerate.url(props.report.id),
        'POST',
        { period: props.report.period, request_key: requestKey.value }
    );

    if (result) {
        router.visit(show.url(result.id));
    }
};
</script>
<template>
    <Head :title="`${t('monitoring.report')} #${report.id}`" />
    <div class="mx-auto grid w-full max-w-5xl gap-6 p-4 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                :title="`${t('monitoring.report')} #${report.id} · v${report.version}`"
                :description="report.project_name ?? ''"
            /><Button as-child variant="outline"
                ><Link :href="projectShow(report.project_id)">{{
                    t('monitoring.backProject')
                }}</Link></Button
            >
        </div>
        <p
            v-if="error"
            role="alert"
            class="rounded-lg bg-destructive/10 p-4 text-destructive"
        >
            {{ error }}
        </p>
        <section
            class="grid gap-3 rounded-xl border border-sidebar-border/70 p-5"
        >
            <p class="font-medium">
                {{ t(`monitoring.status.${state.report.status}`) }}
            </p>
            <p class="text-sm">
                {{ t(`monitoring.period.${report.period}`) }} ·
                {{ displayDate(report.start_at, report.timezone, locale) }} →
                {{ displayDate(report.end_at, report.timezone, locale) }} ({{
                    report.timezone
                }})
            </p>
            <p class="text-xs text-muted-foreground">
                {{ t('monitoring.cutoff') }}:
                {{ displayDate(report.cutoff_at, report.timezone, locale) }} ·
                {{ t('monitoring.delivery') }}:
                {{
                    t(
                        `monitoring.deliveryStatus.${state.report.delivery_status}`
                    )
                }}
            </p>
            <p class="text-xs text-muted-foreground">
                {{ t('monitoring.retainedUntil') }}:
                {{ displayDate(report.expires_at, report.timezone, locale) }}
            </p>
            <p
                v-if="['queued', 'building'].includes(state.report.status)"
                role="status"
                class="text-sm text-muted-foreground"
            >
                {{ t('monitoring.backgroundHint') }}
            </p>
            <p
                v-if="state.report.status === 'partial'"
                class="text-sm text-amber-700 dark:text-amber-300"
            >
                {{ t('monitoring.partialHint') }}
            </p>
            <p
                v-if="state.report.status === 'empty'"
                class="text-sm text-muted-foreground"
            >
                {{ t('monitoring.emptyHint') }}
            </p>
            <p
                v-if="state.report.status === 'failed'"
                role="alert"
                class="text-sm text-destructive"
            >
                {{ t('monitoring.failedHint') }}
            </p>
            <p v-if="state.report.expired" class="text-sm text-destructive">
                {{ t('monitoring.expired') }}
            </p>
            <p v-if="ready" class="text-sm text-muted-foreground" role="status">
                {{ t(`monitoring.fileStatus.${state.report.file_status}`) }}
            </p>
            <div class="flex flex-wrap gap-3">
                <template v-if="ready && state.report.file_status === 'ready'"
                    ><Button
                        v-for="format in ['xlsx', 'json']"
                        :key="format"
                        as-child
                        variant="outline"
                        ><a :href="download.url([report.id, format])"
                            >{{ t('monitoring.download') }}
                            {{ format.toUpperCase() }}</a
                        ></Button
                    ></template
                ><Button
                    v-if="ready"
                    :disabled="busy"
                    variant="outline"
                    @click="rebuild"
                    >{{ t('monitoring.regenerate') }}</Button
                >
            </div>
        </section>
        <section v-if="ready" class="grid gap-4">
            <ReportSummary :report="state.report" />
            <h2 class="font-semibold">{{ t('monitoring.materials') }}</h2>
            <p v-if="!items.data.length" class="text-sm text-muted-foreground">
                {{ t('monitoring.noMaterials') }}
            </p>
            <MaterialCard
                v-for="item in items.data"
                :key="item.id"
                :material="item.snapshot"
                :timezone="report.timezone"
            /><Pagination :page="items" />
        </section>
    </div>
</template>
