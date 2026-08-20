<script setup lang="ts">
import {
    Database,
    Download,
    LoaderCircle,
    Square,
    Wrench,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
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

            <div class="grid gap-2 sm:flex sm:flex-wrap sm:items-center">
                <Button
                    type="button"
                    :disabled="!canStart"
                    @click="emit('start')"
                >
                    <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                    <Database v-else class="h-4 w-4" />
                    {{ loading ? collectingLabel : startLabel }}
                </Button>

                <Button
                    type="button"
                    variant="outline"
                    :disabled="!loading"
                    @click="emit('stop')"
                >
                    <Square class="h-4 w-4" />
                    {{ stopLabel }}
                </Button>

                <Button
                    type="button"
                    variant="outline"
                    class="border-cyan-500/40 bg-cyan-500/10 text-cyan-700 hover:bg-cyan-500/15 hover:text-cyan-800 dark:text-cyan-200 dark:hover:text-cyan-100"
                    :disabled="!downloadUrl || loading"
                    @click="emit('download')"
                >
                    <Download class="h-4 w-4" />
                    {{ downloadLabel }}
                </Button>

                <Button
                    type="button"
                    variant="outline"
                    class="border-emerald-500/40 bg-emerald-500/10 text-emerald-700 hover:bg-emerald-500/15 hover:text-emerald-800 dark:text-emerald-200 dark:hover:text-emerald-100"
                    :disabled="!downloadJsonUrl || loading"
                    @click="emit('downloadJson')"
                >
                    <Download class="h-4 w-4" />
                    {{ downloadJsonLabel }}
                </Button>
            </div>

            <slot name="afterActions" />
    </ControlPanelShell>
</template>
