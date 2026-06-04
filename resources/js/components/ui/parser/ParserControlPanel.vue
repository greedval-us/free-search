<script setup lang="ts">
import {
    ChevronDown,
    ChevronUp,
    Database,
    Download,
    LoaderCircle,
    Square,
    Wrench,
} from 'lucide-vue-next';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';

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
    <section class="intel-panel-strong sticky top-0 z-10 shrink-0">
        <div class="flex items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <Wrench class="h-4 w-4 text-cyan-400" />
                    <span>{{ title }}</span>
                    <HelpTooltip :label="helpLabel" :text="helpText" />
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ settingsCollapsed ? collapsedText : subtitle }}
                </p>
            </div>

            <button
                type="button"
                class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-input text-sm text-foreground transition hover:bg-accent"
                @click="emit('update:settingsCollapsed', !settingsCollapsed)"
            >
                <ChevronDown v-if="settingsCollapsed" class="h-4 w-4" />
                <ChevronUp v-else class="h-4 w-4" />
            </button>
        </div>

        <div v-if="!settingsCollapsed" class="mt-3 space-y-3">
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
        </div>
    </section>
</template>
