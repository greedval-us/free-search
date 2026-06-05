<script setup lang="ts">
import ParserControlPanel from './ParserControlPanel.vue';
import ParserProgressPanel from './ParserProgressPanel.vue';

withDefaults(
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
        progressTitle: string;
        stageLabel: string;
        progress: number;
        stats: Array<{ label: string; value: number | string }>;
        statsGridClass?: string;
    }>(),
    {
        statsGridClass: 'md:grid-cols-2',
    }
);

const emit = defineEmits<{
    'update:settingsCollapsed': [value: boolean];
    start: [];
    stop: [];
    download: [];
    downloadJson: [];
}>();
</script>

<template>
    <ParserControlPanel
        :title="title"
        :help-label="helpLabel"
        :help-text="helpText"
        :subtitle="subtitle"
        :collapsed-text="collapsedText"
        :settings-collapsed="settingsCollapsed"
        :loading="loading"
        :can-start="canStart"
        :download-url="downloadUrl"
        :download-json-url="downloadJsonUrl"
        :start-label="startLabel"
        :collecting-label="collectingLabel"
        :stop-label="stopLabel"
        :download-label="downloadLabel"
        :download-json-label="downloadJsonLabel"
        @update:settings-collapsed="emit('update:settingsCollapsed', $event)"
        @start="emit('start')"
        @stop="emit('stop')"
        @download="emit('download')"
        @download-json="emit('downloadJson')"
    >
        <template #fields>
            <slot name="fields" />
        </template>
        <template #afterActions>
            <slot name="afterActions" />
        </template>
    </ParserControlPanel>

    <ParserProgressPanel
        :title="progressTitle"
        :stage-label="stageLabel"
        :progress="progress"
        :stats="stats"
        :stats-grid-class="statsGridClass"
    />
</template>
