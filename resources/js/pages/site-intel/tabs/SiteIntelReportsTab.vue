<script setup lang="ts">
import { Plus, RefreshCw } from 'lucide-vue-next';
import { shallowRef } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useI18n } from '@/composables/useI18n';
import ReportHistoryPanel from '../reports/ReportHistoryPanel.vue';
import ReportScheduleCard from '../reports/ReportScheduleCard.vue';
import ReportScheduleForm from '../reports/ReportScheduleForm.vue';
import { useSiteIntelReports } from '../reports/useSiteIntelReports';

const { t } = useI18n();
const {
    list,
    busy,
    error,
    notice,
    form,
    targetsError,
    canCreate,
    create,
    change,
    run,
    remove,
    refresh,
    reportViewUrl,
    reportDownloadUrl,
} = useSiteIntelReports();
const createOpen = shallowRef(false);
const createSchedule = async () => {
    if (await create()) {
        createOpen.value = false;
    }
};
</script>

<template>
    <section
        class="flex min-h-0 min-w-0 flex-1 flex-col gap-3 p-1"
        :aria-label="t('siteIntelReports.title')"
        :aria-busy="busy"
    >
        <header
            class="flex shrink-0 flex-wrap items-center justify-between gap-3"
        >
            <div class="min-w-0 space-y-1">
                <h2 class="text-lg font-semibold">
                    {{ t('siteIntelReports.title') }}
                </h2>
                <p class="max-w-2xl text-xs text-muted-foreground">
                    {{ t('siteIntelReports.subtitle') }}
                </p>
                <p v-if="list" class="text-xs text-muted-foreground">
                    {{
                        t('siteIntelReports.quota', {
                            used: list.schedules.length,
                            max: list.maxSchedules,
                        })
                    }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="intel-button-secondary"
                    :disabled="busy"
                    @click="refresh()"
                >
                    <RefreshCw
                        class="size-4"
                        :class="{ 'animate-spin': busy }"
                        aria-hidden="true"
                    />
                    {{ t('siteIntelReports.refresh') }}
                </button>
                <Dialog v-if="list" v-model:open="createOpen">
                    <DialogTrigger as-child>
                        <button
                            type="button"
                            class="intel-button-primary"
                            :disabled="busy"
                        >
                            <Plus class="size-4" aria-hidden="true" />{{
                                t('siteIntelReports.new')
                            }}
                        </button>
                    </DialogTrigger>
                    <DialogContent class="intel-scroll sm:max-w-2xl">
                        <DialogHeader class="pr-10 text-left">
                            <DialogTitle>{{
                                t('siteIntelReports.new')
                            }}</DialogTitle>
                            <DialogDescription>{{
                                t('siteIntelReports.periodHelp')
                            }}</DialogDescription>
                        </DialogHeader>
                        <p
                            v-if="error"
                            role="alert"
                            class="text-sm text-destructive"
                        >
                            {{ error }}
                        </p>
                        <ReportScheduleForm
                            v-model="form"
                            :busy="busy"
                            :bot-linked="list.botLinked"
                            :bot-exports-enabled="list.botExportsEnabled"
                            :max-targets="list.maxTargets"
                            :available-report-types="list.availableReportTypes"
                            :limit-reached="
                                list.schedules.length >= list.maxSchedules
                            "
                            :targets-error="targetsError"
                            :can-create="canCreate"
                            @create="createSchedule"
                        />
                    </DialogContent>
                </Dialog>
            </div>
        </header>
        <p
            v-if="error && !createOpen"
            role="alert"
            class="shrink-0 rounded-lg border border-destructive/40 bg-destructive/5 p-3 text-sm text-destructive"
        >
            {{ error }}
        </p>
        <p
            v-if="notice"
            role="status"
            class="shrink-0 rounded-lg border border-primary/30 bg-primary/5 p-3 text-sm"
        >
            {{ notice }}
        </p>
        <p
            v-if="!list && !error"
            role="status"
            class="p-6 text-muted-foreground"
        >
            {{ t('siteIntelReports.loading') }}
        </p>
        <div
            v-if="list"
            class="intel-scroll min-h-0 flex-1 overflow-y-auto overscroll-contain p-1"
        >
            <div
                class="grid min-w-0 gap-5 lg:grid-cols-[minmax(18rem,0.85fr)_minmax(0,1.5fr)]"
            >
                <section
                    class="min-w-0 space-y-3"
                    :aria-label="t('siteIntelReports.schedules')"
                >
                    <h3 class="text-sm font-semibold">
                        {{ t('siteIntelReports.schedules') }}
                    </h3>
                    <div
                        v-if="!list.schedules.length"
                        class="intel-panel-strong space-y-3 text-sm text-muted-foreground"
                    >
                        <p>{{ t('siteIntelReports.emptySchedules') }}</p>
                        <button
                            type="button"
                            class="intel-button-secondary"
                            @click="createOpen = true"
                        >
                            {{ t('siteIntelReports.new') }}
                        </button>
                    </div>
                    <ReportScheduleCard
                        v-for="schedule in list.schedules"
                        :key="schedule.id"
                        :schedule="schedule"
                        :busy="busy"
                        @change="change"
                        @run="run"
                        @remove="remove"
                    />
                </section>
                <ReportHistoryPanel
                    :history="list.reports"
                    :busy="busy"
                    :view-url="reportViewUrl"
                    :download-url="reportDownloadUrl"
                    @page="refresh"
                />
            </div>
        </div>
    </section>
</template>
