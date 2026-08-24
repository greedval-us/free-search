<script setup lang="ts">
import { Search, Settings } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
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

            <div
                class="grid min-w-0 grid-cols-[minmax(0,1fr)_auto] items-end gap-2 sm:flex sm:flex-wrap xl:justify-end"
            >
                <slot name="toolbarLeading" />

                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    :aria-label="
                        showAdvanced ? advancedHideAria : advancedShowAria
                    "
                    :title="showAdvanced ? advancedHideAria : advancedShowAria"
                    :class="{
                        'border-primary/50 bg-primary/10 text-primary':
                            showAdvanced,
                    }"
                    @click="emit('update:showAdvanced', !showAdvanced)"
                >
                    <Settings class="h-4 w-4" />
                </Button>

                <Button
                    type="button"
                    :disabled="loading || !canSearch"
                    class="col-span-full w-full min-w-0 px-5 sm:w-auto sm:min-w-32"
                    @click="emit('submit')"
                >
                    {{ loading ? searchingLabel : submitLabel }}
                </Button>
            </div>
        <slot name="advanced" />
        <slot name="afterActions" />
    </ControlPanelShell>
</template>
