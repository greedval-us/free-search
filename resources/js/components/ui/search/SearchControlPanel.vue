<script setup lang="ts">
import { Search, Settings } from 'lucide-vue-next';
import ControlPanelShell from '@/components/ui/control-panel/ControlPanelShell.vue';

defineProps<{
    title: string;
    helpLabel: string;
    helpText: string;
    subtitle: string;
    collapsedText: string;
    collapsed: boolean;
    showAdvanced: boolean;
    loading: boolean;
    canSearch: boolean;
    advancedShowAria: string;
    advancedHideAria: string;
    submitLabel: string;
    searchingLabel: string;
}>();

const emit = defineEmits<{
    'update:collapsed': [value: boolean];
    'update:showAdvanced': [value: boolean];
    submit: [];
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
        :icon="Search"
        body-class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_auto]"
        @update:collapsed="emit('update:collapsed', $event)"
    >
            <slot name="fields" />

            <div class="flex flex-wrap items-end gap-2 xl:justify-end">
                <slot name="toolbarLeading" />

                <button
                    type="button"
                    :aria-label="
                        showAdvanced ? advancedHideAria : advancedShowAria
                    "
                    class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-md border border-slate-700 bg-slate-900/80 text-slate-200 transition hover:border-cyan-300/40 hover:text-cyan-100"
                    :class="{
                        'border-cyan-400/50 bg-cyan-400/20 text-cyan-300':
                            showAdvanced,
                    }"
                    @click="emit('update:showAdvanced', !showAdvanced)"
                >
                    <Settings class="h-4 w-4" />
                </button>

                <button
                    :disabled="loading || !canSearch"
                    class="h-10 cursor-pointer rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                    @click="emit('submit')"
                >
                    {{ loading ? searchingLabel : submitLabel }}
                </button>
            </div>
        <slot name="advanced" />
        <slot name="afterActions" />
    </ControlPanelShell>
</template>
