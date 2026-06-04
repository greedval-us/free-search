<script setup lang="ts">
import { computed } from 'vue';
import ParserControlPanel from '@/components/ui/parser/ParserControlPanel.vue';
import ParserProgressPanel from '@/components/ui/parser/ParserProgressPanel.vue';
import { useI18n } from '@/composables/useI18n';
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

const stageLabel = computed(() => {
    if (stage.value === 'profile') {
        return t('bluesky.parser.progress.stage.profile');
    }

    if (stage.value === 'feed') {
        return t('bluesky.parser.progress.stage.feed');
    }

    if (stage.value === 'followers') {
        return t('bluesky.parser.progress.stage.followers');
    }

    if (stage.value === 'follows') {
        return t('bluesky.parser.progress.stage.follows');
    }

    if (stage.value === 'interactions') {
        return t('bluesky.parser.progress.stage.interactions');
    }

    if (stage.value === 'finishing') {
        return t('bluesky.parser.progress.stage.finishing');
    }

    if (stage.value === 'completed') {
        return t('bluesky.parser.progress.stage.completed');
    }

    if (stage.value === 'failed') {
        return t('bluesky.parser.progress.stage.failed');
    }

    if (stage.value === 'stopped') {
        return t('bluesky.parser.progress.stage.stopped');
    }

    return t('bluesky.parser.progress.stage.idle');
});

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
    <ParserControlPanel
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
        @update:settings-collapsed="settingsCollapsed = $event"
        @start="start"
        @stop="stop"
        @download="download"
        @download-json="downloadJson"
    >
        <template #fields>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-12">
                <label class="block min-w-0 xl:col-span-12">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                        {{ t('bluesky.parser.actor') }}
                    </span>
                    <input
                        v-model="form.actor"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('bluesky.parser.actorPlaceholder')"
                    />
                </label>
            </div>

            <div class="rounded-md border border-border/70 bg-background/40 p-3 text-xs text-muted-foreground">
                {{ t('bluesky.parser.hint') }}
            </div>
        </template>
        <template #afterActions>
            <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
        </template>
    </ParserControlPanel>

    <ParserProgressPanel
        :title="t('bluesky.parser.progress.title')"
        :stage-label="stageLabel"
        :progress="progress"
        :stats="progressStats"
        stats-grid-class="md:grid-cols-2 xl:grid-cols-3"
    />
</template>
