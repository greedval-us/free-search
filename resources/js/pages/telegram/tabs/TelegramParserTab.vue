<script setup lang="ts">
import { ChevronDown, ChevronUp, Database, Download, LoaderCircle, Square, Wrench } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
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
    keywordActive,
    customPeriod,
    canStart,
    start,
    stop,
    download,
} = useTelegramParser(t);

const stageLabel = computed(() => {
    if (stage.value === 'messages') {
        return t('telegram.parser.progress.stage.messages');
    }

    if (stage.value === 'comments') {
        return t('telegram.parser.progress.stage.comments');
    }

    if (stage.value === 'finishing') {
        return t('telegram.parser.progress.stage.finishing');
    }

    if (stage.value === 'completed') {
        return t('telegram.parser.progress.stage.completed');
    }

    if (stage.value === 'failed') {
        return t('telegram.parser.progress.stage.failed');
    }

    if (stage.value === 'stopped') {
        return t('telegram.parser.progress.stage.stopped');
    }

    return t('telegram.parser.progress.stage.idle');
});
</script>

<template>
    <section class="sticky top-0 z-10 shrink-0 rounded-xl border border-sidebar-border/80 bg-card/75 p-4 shadow-xl backdrop-blur">
        <div class="flex items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <Wrench class="h-4 w-4 text-cyan-400" />
                    <span>{{ t('telegram.parser.title') }}</span>
                </div>
            </div>

            <button
                type="button"
                class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-input text-sm text-foreground transition hover:bg-accent"
                @click="settingsCollapsed = !settingsCollapsed"
            >
                <ChevronDown v-if="settingsCollapsed" class="h-4 w-4" />
                <ChevronUp v-else class="h-4 w-4" />
            </button>
        </div>

        <div v-if="!settingsCollapsed" class="mt-3 space-y-3">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-12">
                <label class="block min-w-0 xl:col-span-3">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('telegram.parser.filters.channel') }}</span>
                    <input
                        v-model="form.chatUsername"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        placeholder="durov"
                    />
                </label>

                <label class="block min-w-0 xl:col-span-3">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('telegram.parser.filters.keyword') }}</span>
                    <input
                        v-model="form.keyword"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('telegram.search.placeholderKeyword')"
                    />
                </label>

                <label class="block min-w-0 xl:col-span-2">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('telegram.parser.filters.period') }}</span>
                    <select v-model="form.period" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                        <option value="day">{{ t('telegram.parser.periods.day') }}</option>
                        <option value="week">{{ t('telegram.parser.periods.week') }}</option>
                        <option value="month">{{ t('telegram.parser.periods.month') }}</option>
                        <option value="custom">{{ t('telegram.parser.periods.custom') }}</option>
                    </select>
                </label>

                <label class="block min-w-0 xl:col-span-2">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('telegram.parser.filters.from') }}</span>
                    <input
                        v-model="form.dateFrom"
                        type="date"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm disabled:opacity-50"
                        :disabled="keywordActive || !customPeriod"
                    />
                </label>

                <label class="block min-w-0 xl:col-span-2">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('telegram.parser.filters.to') }}</span>
                    <input
                        v-model="form.dateTo"
                        type="date"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm disabled:opacity-50"
                        :disabled="keywordActive || !customPeriod"
                    />
                </label>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="!canStart"
                    @click="start"
                >
                    <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                    <Database v-else class="h-4 w-4" />
                    {{ loading ? t('telegram.parser.collecting') : t('telegram.parser.start') }}
                </button>

                <button
                    type="button"
                    class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-input px-4 text-sm font-semibold text-foreground disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="!loading"
                    @click="stop"
                >
                    <Square class="h-4 w-4" />
                    {{ t('telegram.parser.stop') }}
                </button>

                <button
                    type="button"
                    class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-cyan-400/50 bg-cyan-400/10 px-4 text-sm font-semibold text-cyan-200 hover:bg-cyan-400/15 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="!downloadUrl || loading"
                    @click="download"
                >
                    <Download class="h-4 w-4" />
                    {{ t('telegram.parser.download') }}
                </button>
            </div>

            <div v-if="keywordActive" class="rounded-md border border-amber-500/40 bg-amber-500/10 p-3 text-xs text-amber-300">
                {{ t('telegram.parser.keywordHint') }}
            </div>

            <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
        </div>
    </section>

    <section class="rounded-xl border border-sidebar-border/80 bg-card/75 p-4 shadow-xl backdrop-blur">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold">{{ t('telegram.parser.progress.title') }}</h3>
            <span class="rounded-full border border-border px-2 py-1 text-xs text-muted-foreground">{{ stageLabel }}</span>
        </div>

        <div class="mt-3 h-3 overflow-hidden rounded-full bg-muted">
            <div class="h-full bg-cyan-400 transition-all" :style="{ width: `${Math.max(0, Math.min(100, progress))}%` }" />
        </div>

        <div class="mt-3 grid gap-3 md:grid-cols-2">
            <article class="rounded-lg border border-border/70 bg-background/70 p-3">
                <p class="text-xs text-muted-foreground">{{ t('telegram.parser.progress.messages') }}</p>
                <p class="mt-1 text-xl font-semibold">{{ processedMessages }}</p>
            </article>

            <article class="rounded-lg border border-border/70 bg-background/70 p-3">
                <p class="text-xs text-muted-foreground">{{ t('telegram.parser.progress.comments') }}</p>
                <p class="mt-1 text-xl font-semibold">{{ processedComments }}</p>
            </article>
        </div>

    </section>
</template>
