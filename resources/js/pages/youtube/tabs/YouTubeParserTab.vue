<script setup lang="ts">
import { computed } from 'vue';
import ParserControlPanel from '@/components/ui/parser/ParserControlPanel.vue';
import ParserProgressPanel from '@/components/ui/parser/ParserProgressPanel.vue';
import { useI18n } from '@/composables/useI18n';
import { useYouTubeParser } from '../composables/useYouTubeParser';

const { t } = useI18n();

const {
    form,
    settingsCollapsed,
    loading,
    error,
    progress,
    stage,
    processedComments,
    processedReplies,
    downloadUrl,
    downloadJsonUrl,
    canStart,
    start,
    stop,
    download,
    downloadJson,
} = useYouTubeParser(t);

const stageLabel = computed(() => {
    if (stage.value === 'comments') {
        return t('youtube.parser.progress.stage.comments');
    }

    if (stage.value === 'replies') {
        return t('youtube.parser.progress.stage.replies');
    }

    if (stage.value === 'finishing') {
        return t('youtube.parser.progress.stage.finishing');
    }

    if (stage.value === 'completed') {
        return t('youtube.parser.progress.stage.completed');
    }

    if (stage.value === 'failed') {
        return t('youtube.parser.progress.stage.failed');
    }

    if (stage.value === 'stopped') {
        return t('youtube.parser.progress.stage.stopped');
    }

    return t('youtube.parser.progress.stage.idle');
});

const progressStats = computed(() => [
    {
        label: t('youtube.parser.progress.comments'),
        value: processedComments.value,
    },
    {
        label: t('youtube.parser.progress.replies'),
        value: processedReplies.value,
    },
]);
</script>

<template>
    <ParserControlPanel
        :title="t('youtube.parser.title')"
        :help-label="t('youtube.help.label')"
        :help-text="t('youtube.parser.help.overview')"
        :subtitle="t('youtube.parser.subtitle')"
        :collapsed-text="t('youtube.parser.collapsed')"
        :settings-collapsed="settingsCollapsed"
        :loading="loading"
        :can-start="canStart"
        :download-url="downloadUrl"
        :download-json-url="downloadJsonUrl"
        :start-label="t('youtube.parser.start')"
        :collecting-label="t('youtube.parser.collecting')"
        :stop-label="t('youtube.parser.stop')"
        :download-label="t('youtube.parser.download')"
        :download-json-label="t('youtube.parser.downloadJson')"
        @update:settings-collapsed="settingsCollapsed = $event"
        @start="start"
        @stop="stop"
        @download="download"
        @download-json="downloadJson"
    >
        <template #fields>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-12">
                <label class="block min-w-0 xl:col-span-12">
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                        >{{ t('youtube.parser.videoId') }}</span
                    >
                    <input
                        v-model="form.videoId"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    />
                </label>
            </div>
        </template>
        <template #afterActions>
            <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
        </template>
    </ParserControlPanel>

    <ParserProgressPanel
        :title="t('youtube.parser.progress.title')"
        :stage-label="stageLabel"
        :progress="progress"
        :stats="progressStats"
    />
</template>
