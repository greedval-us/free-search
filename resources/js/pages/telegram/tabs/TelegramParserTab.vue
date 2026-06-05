<script setup lang="ts">
import { computed } from 'vue';
import ParserTabLayout from '@/components/ui/parser/ParserTabLayout.vue';
import { useI18n } from '@/composables/useI18n';
import { useStageLabel } from '@/composables/useStageLabel';
import { useTelegramParser } from '../composables/useTelegramParser';

const { t } = useI18n();

const {
    form,
    settingsCollapsed,
    loading,
    error,
    progress,
    stage,
    processedMessages,
    processedComments,
    downloadUrl,
    downloadJsonUrl,
    keywordActive,
    customPeriod,
    canStart,
    start,
    stop,
    download,
    downloadJson,
} = useTelegramParser(t);

const stageLabel = useStageLabel(stage, (value) =>
    t(`telegram.parser.progress.stage.${value}`)
);

const progressStats = computed(() => [
    {
        label: t('telegram.parser.progress.messages'),
        value: processedMessages.value,
    },
    {
        label: t('telegram.parser.progress.comments'),
        value: processedComments.value,
    },
]);
</script>

<template>
    <ParserTabLayout
        :title="t('telegram.parser.title')"
        :help-label="t('telegram.help.label')"
        :help-text="t('telegram.parser.help.overview')"
        :subtitle="t('telegram.parser.subtitle')"
        :collapsed-text="t('telegram.parser.collapsed')"
        :settings-collapsed="settingsCollapsed"
        :loading="loading"
        :can-start="canStart"
        :download-url="downloadUrl"
        :download-json-url="downloadJsonUrl"
        :start-label="t('telegram.parser.start')"
        :collecting-label="t('telegram.parser.collecting')"
        :stop-label="t('telegram.parser.stop')"
        :download-label="t('telegram.parser.download')"
        :download-json-label="t('telegram.parser.downloadJson')"
        :progress-title="t('telegram.parser.progress.title')"
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
                <label class="intel-field xl:col-span-3">
                    <span class="intel-label">{{
                        t('telegram.parser.filters.channel')
                    }}</span>
                    <input
                        v-model="form.chatUsername"
                        type="text"
                        class="intel-input"
                        :placeholder="t('telegram.search.placeholderChannel')"
                    />
                </label>

                <label class="intel-field xl:col-span-3">
                    <span class="intel-label">{{
                        t('telegram.parser.filters.keyword')
                    }}</span>
                    <input
                        v-model="form.keyword"
                        type="text"
                        class="intel-input"
                        :placeholder="t('telegram.search.placeholderKeyword')"
                    />
                </label>

                <label class="intel-field xl:col-span-2">
                    <span class="intel-label">{{
                        t('telegram.parser.filters.period')
                    }}</span>
                    <select v-model="form.period" class="intel-select">
                        <option value="day">
                            {{ t('telegram.parser.periods.day') }}
                        </option>
                        <option value="week">
                            {{ t('telegram.parser.periods.week') }}
                        </option>
                        <option value="month">
                            {{ t('telegram.parser.periods.month') }}
                        </option>
                        <option value="custom">
                            {{ t('telegram.parser.periods.custom') }}
                        </option>
                    </select>
                </label>

                <label class="intel-field xl:col-span-2">
                    <span class="intel-label">{{
                        t('telegram.parser.filters.from')
                    }}</span>
                    <input
                        v-model="form.dateFrom"
                        type="date"
                        class="intel-input"
                        :disabled="keywordActive || !customPeriod"
                    />
                </label>

                <label class="intel-field xl:col-span-2">
                    <span class="intel-label">{{
                        t('telegram.parser.filters.to')
                    }}</span>
                    <input
                        v-model="form.dateTo"
                        type="date"
                        class="intel-input"
                        :disabled="keywordActive || !customPeriod"
                    />
                </label>
            </div>
        </template>
        <template #afterActions>
            <div
                v-if="keywordActive"
                class="rounded-md border border-amber-500/40 bg-amber-500/10 p-3 text-xs text-amber-300"
            >
                {{ t('telegram.parser.keywordHint') }}
            </div>

            <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
        </template>
    </ParserTabLayout>
</template>
