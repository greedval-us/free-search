<script setup lang="ts">
import {
    Database,
    Download,
    LoaderCircle,
    Square,
    Wrench,
} from 'lucide-vue-next';
import ControlPanelShell from '@/components/ui/control-panel/ControlPanelShell.vue';

defineProps<{
    title: string;
    helpLabel: string;
    helpText: string;
    subtitle: string;
    collapsedText: string;
    settingsCollapsed: boolean;
    loading: boolean;
    canStart: boolean;
    downloadUrl: string | null;
    downloadJsonUrl: string | null;
    startLabel: string;
    collectingLabel: string;
    stopLabel: string;
    downloadLabel: string;
    downloadJsonLabel: string;
}>();

const emit = defineEmits<{
    'update:settingsCollapsed': [value: boolean];
    start: [];
    stop: [];
    download: [];
    downloadJson: [];
}>();
</script>

<template>
    <ControlPanelShell
        :title="title"
        :help-label="helpLabel"
        :help-text="helpText"
        :subtitle="subtitle"
        :collapsed-text="collapsedText"
        :collapsed="settingsCollapsed"
        :icon="Wrench"
        body-class="space-y-3"
        @update:collapsed="emit('update:settingsCollapsed', $event)"
    >
            <slot name="fields" />

            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="!canStart"
                    @click="emit('start')"
                >
                    <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                    <Database v-else class="h-4 w-4" />
                    {{ loading ? collectingLabel : startLabel }}
                </button>

                <button
                    type="button"
                    class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-input px-4 text-sm font-semibold text-foreground disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="!loading"
                    @click="emit('stop')"
                >
                    <Square class="h-4 w-4" />
                    {{ stopLabel }}
                </button>

                <button
                    type="button"
                    class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-cyan-400/50 bg-cyan-400/10 px-4 text-sm font-semibold text-cyan-200 hover:bg-cyan-400/15 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="!downloadUrl || loading"
                    @click="emit('download')"
                >
                    <Download class="h-4 w-4" />
                    {{ downloadLabel }}
                </button>

                <button
                    type="button"
                    class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-emerald-400/50 bg-emerald-400/10 px-4 text-sm font-semibold text-emerald-200 hover:bg-emerald-400/15 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="!downloadJsonUrl || loading"
                    @click="emit('downloadJson')"
                >
                    <Download class="h-4 w-4" />
                    {{ downloadJsonLabel }}
                </button>
            </div>

            <slot name="afterActions" />
    </ControlPanelShell>
</template>
