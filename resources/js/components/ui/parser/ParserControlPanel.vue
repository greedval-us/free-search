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
import { useI18n } from '@/composables/useI18n';

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

const { t } = useI18n();
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

        <div
            class="flex flex-col gap-3 border-t border-border/60 pt-3 lg:flex-row lg:items-center lg:justify-between"
        >
            <div
                class="grid gap-2 sm:flex sm:flex-wrap sm:items-center"
                role="group"
                :aria-label="t('parser.actions.collection')"
            >
                <Button
                    type="button"
                    class="motion-reduce:transition-none"
                    :disabled="loading || !canStart"
                    @click="emit('start')"
                >
                    <LoaderCircle
                        v-if="loading"
                        class="size-4 animate-spin motion-reduce:animate-none"
                        aria-hidden="true"
                    />
                    <Database v-else class="size-4" aria-hidden="true" />
                    {{ loading ? collectingLabel : startLabel }}
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    class="border-destructive/25 text-destructive hover:bg-destructive/10 hover:text-destructive motion-reduce:transition-none"
                    :disabled="!loading"
                    @click="emit('stop')"
                >
                    <Square class="size-3.5" aria-hidden="true" />
                    {{ stopLabel }}
                </Button>
            </div>

            <div
                class="grid gap-2 sm:flex sm:flex-wrap sm:items-center"
                role="group"
                :aria-label="t('parser.actions.exports')"
            >
                <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    class="motion-reduce:transition-none"
                    :disabled="!downloadUrl || loading"
                    @click="emit('download')"
                >
                    <Download class="size-3.5" aria-hidden="true" />
                    {{ downloadLabel }}
                </Button>
                <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    class="motion-reduce:transition-none"
                    :disabled="!downloadJsonUrl || loading"
                    @click="emit('downloadJson')"
                >
                    <Download class="size-3.5" aria-hidden="true" />
                    {{ downloadJsonLabel }}
                </Button>
            </div>
        </div>

        <slot name="afterActions" />
    </ControlPanelShell>
</template>
