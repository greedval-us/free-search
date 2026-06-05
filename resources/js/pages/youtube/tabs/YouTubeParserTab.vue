<script setup lang="ts">
import { computed } from 'vue';
import ParserTabLayout from '@/components/ui/parser/ParserTabLayout.vue';
import { useI18n } from '@/composables/useI18n';
import { useStageLabel } from '@/composables/useStageLabel';
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

const stageLabel = useStageLabel(stage, (value) =>
    t(`youtube.parser.progress.stage.${value}`)
);

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
    <ParserTabLayout
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
        :progress-title="t('youtube.parser.progress.title')"
        :stage-label="stageLabel"
        :progress="progress"
        :stats="progressStats"
        @update:settings-collapsed="settingsCollapsed = $event"
        @start="start"
        @stop="stop"
        @download="download"
        @download-json="downloadJson"
    >
        <template #fields>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-12">
                <label class="intel-field xl:col-span-12">
                    <span class="intel-label">{{
                        t('youtube.parser.videoId')
                    }}</span>
                    <input
                        v-model="form.videoId"
                        type="text"
                        class="intel-input"
                        :placeholder="t('youtube.parser.videoIdPlaceholder')"
                    />
                </label>
            </div>
        </template>
        <template #afterActions>
            <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
        </template>
    </ParserTabLayout>
</template>
