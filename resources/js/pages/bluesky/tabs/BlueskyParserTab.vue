<script setup lang="ts">
import { computed } from 'vue';
import ParserTabLayout from '@/components/ui/parser/ParserTabLayout.vue';
import { useI18n } from '@/composables/useI18n';
import { useStageLabel } from '@/composables/useStageLabel';
import { useBlueskyParser } from '../composables/useBlueskyParser';

const { t } = useI18n();

const {
    form,
    settingsCollapsed,
    loading,
    error,
    progress,
    stage,
    processedPosts,
    processedAuthoredReplies,
    processedReceivedReplies,
    processedFollowers,
    processedFollows,
    processedReactions,
    downloadUrl,
    downloadJsonUrl,
    canStart,
    start,
    stop,
    download,
    downloadJson,
} = useBlueskyParser(t);

const stageLabel = useStageLabel(stage, (value) =>
    t(`bluesky.parser.progress.stage.${value}`)
);

const progressStats = computed(() => [
    {
        label: t('bluesky.parser.progress.posts'),
        value: processedPosts.value,
    },
    {
        label: t('bluesky.parser.progress.authoredReplies'),
        value: processedAuthoredReplies.value,
    },
    {
        label: t('bluesky.parser.progress.receivedReplies'),
        value: processedReceivedReplies.value,
    },
    {
        label: t('bluesky.parser.progress.followers'),
        value: processedFollowers.value,
    },
    {
        label: t('bluesky.parser.progress.follows'),
        value: processedFollows.value,
    },
    {
        label: t('bluesky.parser.progress.reactions'),
        value: processedReactions.value,
    },
]);
</script>

<template>
    <ParserTabLayout
        :title="t('bluesky.parser.title')"
        :help-label="t('bluesky.help.label')"
        :help-text="t('bluesky.parser.help.overview')"
        :subtitle="t('bluesky.parser.subtitle')"
        :collapsed-text="t('bluesky.parser.collapsed')"
        :settings-collapsed="settingsCollapsed"
        :loading="loading"
        :can-start="canStart"
        :download-url="downloadUrl"
        :download-json-url="downloadJsonUrl"
        :start-label="t('bluesky.parser.start')"
        :collecting-label="t('bluesky.parser.collecting')"
        :stop-label="t('bluesky.parser.stop')"
        :download-label="t('bluesky.parser.download')"
        :download-json-label="t('bluesky.parser.downloadJson')"
        :progress-title="t('bluesky.parser.progress.title')"
        :stage-label="stageLabel"
        :progress="progress"
        :stats="progressStats"
        stats-grid-class="md:grid-cols-2 xl:grid-cols-3"
        @update:settings-collapsed="settingsCollapsed = $event"
        @start="start"
        @stop="stop"
        @download="download"
        @download-json="downloadJson"
    >
        <template #fields>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-12">
                <label class="intel-field xl:col-span-12">
                    <span class="intel-label">
                        {{ t('bluesky.parser.actor') }}
                    </span>
                    <input
                        v-model="form.actor"
                        type="text"
                        class="intel-input"
                        :placeholder="t('bluesky.parser.actorPlaceholder')"
                    />
                </label>
            </div>

            <div class="intel-section text-muted-foreground">
                {{ t('bluesky.parser.hint') }}
            </div>
        </template>
        <template #afterActions>
            <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
        </template>
    </ParserTabLayout>
</template>
