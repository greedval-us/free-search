<script setup lang="ts">
import { computed } from 'vue';
import ParserControlPanel from '@/components/ui/parser/ParserControlPanel.vue';
import ParserProgressPanel from '@/components/ui/parser/ParserProgressPanel.vue';
import { useI18n } from '@/composables/useI18n';
import { useMastodonParser } from '../composables/useMastodonParser';

const { t } = useI18n();

const {
    form,
    settingsCollapsed,
    loading,
    error,
    progress,
    stage,
    processedStatuses,
    processedComments,
    downloadUrl,
    downloadJsonUrl,
    canStart,
    start,
    stop,
    download,
    downloadJson,
} = useMastodonParser(t);

const stageLabel = computed(() => {
    if (stage.value === 'statuses') {
        return t('mastodon.parser.progress.stage.statuses');
    }

    if (stage.value === 'comments') {
        return t('mastodon.parser.progress.stage.comments');
    }

    if (stage.value === 'finishing') {
        return t('mastodon.parser.progress.stage.finishing');
    }

    if (stage.value === 'completed') {
        return t('mastodon.parser.progress.stage.completed');
    }

    if (stage.value === 'failed') {
        return t('mastodon.parser.progress.stage.failed');
    }

    if (stage.value === 'stopped') {
        return t('mastodon.parser.progress.stage.stopped');
    }

    return t('mastodon.parser.progress.stage.idle');
});

const progressStats = computed(() => [
    {
        label: t('mastodon.parser.progress.statuses'),
        value: processedStatuses.value,
    },
    {
        label: t('mastodon.parser.progress.comments'),
        value: processedComments.value,
    },
]);
</script>

<template>
    <ParserControlPanel
        :title="t('mastodon.parser.title')"
        :help-label="t('mastodon.help.label')"
        :help-text="t('mastodon.parser.help.overview')"
        :subtitle="t('mastodon.parser.subtitle')"
        :collapsed-text="t('mastodon.parser.collapsed')"
        :settings-collapsed="settingsCollapsed"
        :loading="loading"
        :can-start="canStart"
        :download-url="downloadUrl"
        :download-json-url="downloadJsonUrl"
        :start-label="t('mastodon.parser.start')"
        :collecting-label="t('mastodon.parser.collecting')"
        :stop-label="t('mastodon.parser.stop')"
        :download-label="t('mastodon.parser.download')"
        :download-json-label="t('mastodon.parser.downloadJson')"
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
                        >{{ t('mastodon.parser.account') }}</span
                    >
                    <input
                        v-model="form.account"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('mastodon.parser.accountPlaceholder')"
                    />
                </label>
            </div>

            <div
                class="rounded-md border border-border/70 bg-background/40 p-3 text-xs text-muted-foreground"
            >
                {{ t('mastodon.parser.hint') }}
            </div>
        </template>
        <template #afterActions>
            <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
        </template>
    </ParserControlPanel>

    <ParserProgressPanel
        :title="t('mastodon.parser.progress.title')"
        :stage-label="stageLabel"
        :progress="progress"
        :stats="progressStats"
    />
</template>
