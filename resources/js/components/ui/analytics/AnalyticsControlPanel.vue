<script setup lang="ts">
import { BarChart3, Download, FileText, RefreshCw, Settings } from 'lucide-vue-next';
import ControlPanelShell from '@/components/ui/control-panel/ControlPanelShell.vue';

withDefaults(
    defineProps<{
        title: string;
        helpLabel: string;
        helpText: string;
        subtitle: string;
        collapsedText: string;
        collapsed: boolean;
        loading: boolean;
        canRun: boolean;
        canUseReportActions: boolean;
        runLabel: string;
        loadingLabel: string;
        reportLabel: string;
        downloadReportLabel: string;
        hideSettingsLabel?: string | null;
    }>(),
    {
        hideSettingsLabel: null,
    }
);

const emit = defineEmits<{
    'update:collapsed': [value: boolean];
    run: [];
    openReport: [];
    downloadReport: [];
}>();
</script>

<template>
    <ControlPanelShell
        :title="title"
        :help-label="helpLabel"
        :help-text="helpText"
        :subtitle="subtitle"
        :collapsed-text="collapsedText"
        :collapsed="collapsed"
        :icon="BarChart3"
        body-class="space-y-2.5"
        @update:collapsed="emit('update:collapsed', $event)"
    >
            <slot name="fields" />

            <div
                class="flex flex-wrap items-end justify-between gap-2.5 rounded-md border border-border/70 bg-background/60 p-2.5"
            >
                <div class="min-w-0 flex-1">
                    <slot name="toolbarLeading" />
                </div>

                <div class="flex w-full flex-wrap justify-end gap-2 md:w-auto">
                    <button
                        v-if="hideSettingsLabel"
                        type="button"
                        class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-input bg-background px-3 text-sm text-foreground transition hover:bg-accent"
                        @click="emit('update:collapsed', true)"
                    >
                        <Settings class="h-4 w-4" />
                        {{ hideSettingsLabel }}
                    </button>

                    <button
                        type="button"
                        :disabled="loading || !canRun"
                        class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                        @click="emit('run')"
                    >
                        <RefreshCw
                            class="h-4 w-4"
                            :class="{ 'animate-spin': loading }"
                        />
                        {{ loading ? loadingLabel : runLabel }}
                    </button>

                    <button
                        type="button"
                        :disabled="!canUseReportActions"
                        class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="emit('openReport')"
                    >
                        <FileText class="h-4 w-4" />
                        {{ reportLabel }}
                    </button>

                    <button
                        type="button"
                        :disabled="!canUseReportActions"
                        class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                        @click="emit('downloadReport')"
                    >
                        <Download class="h-4 w-4" />
                        {{ downloadReportLabel }}
                    </button>
                </div>
            </div>

            <slot name="afterActions" />
    </ControlPanelShell>
</template>
