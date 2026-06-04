<script setup lang="ts">
import {
    ChevronDown,
    ChevronUp,
    Database,
    Download,
    LoaderCircle,
    Square,
    Wrench,
} from 'lucide-vue-next';
import { computed } from 'vue';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
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
    if (stage.value === 'profile') return t('bluesky.parser.progress.stage.profile');
    if (stage.value === 'feed') return t('bluesky.parser.progress.stage.feed');
    if (stage.value === 'followers') return t('bluesky.parser.progress.stage.followers');
    if (stage.value === 'follows') return t('bluesky.parser.progress.stage.follows');
    if (stage.value === 'interactions') return t('bluesky.parser.progress.stage.interactions');
    if (stage.value === 'finishing') return t('bluesky.parser.progress.stage.finishing');
    if (stage.value === 'completed') return t('bluesky.parser.progress.stage.completed');
    if (stage.value === 'failed') return t('bluesky.parser.progress.stage.failed');
    if (stage.value === 'stopped') return t('bluesky.parser.progress.stage.stopped');

    return t('bluesky.parser.progress.stage.idle');
});
</script>

<template>
    <section class="intel-panel-strong sticky top-0 z-10 shrink-0">
        <div class="flex items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <Wrench class="h-4 w-4 text-cyan-400" />
                    <span>{{ t('bluesky.parser.title') }}</span>
                    <HelpTooltip :label="t('bluesky.help.label')" :text="t('bluesky.parser.help.overview')" />
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ settingsCollapsed ? t('bluesky.parser.collapsed') : t('bluesky.parser.subtitle') }}
                </p>
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

            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="!canStart"
                    @click="start"
                >
                    <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                    <Database v-else class="h-4 w-4" />
                    {{ loading ? t('bluesky.parser.collecting') : t('bluesky.parser.start') }}
                </button>

                <button
                    type="button"
                    class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-input px-4 text-sm font-semibold text-foreground disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="!loading"
                    @click="stop"
                >
                    <Square class="h-4 w-4" />
                    {{ t('bluesky.parser.stop') }}
                </button>

                <button
                    type="button"
                    class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-cyan-400/50 bg-cyan-400/10 px-4 text-sm font-semibold text-cyan-200 hover:bg-cyan-400/15 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="!downloadUrl || loading"
                    @click="download"
                >
                    <Download class="h-4 w-4" />
                    {{ t('bluesky.parser.download') }}
                </button>

                <button
                    type="button"
                    class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-emerald-400/50 bg-emerald-400/10 px-4 text-sm font-semibold text-emerald-200 hover:bg-emerald-400/15 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="!downloadJsonUrl || loading"
                    @click="downloadJson"
                >
                    <Download class="h-4 w-4" />
                    {{ t('bluesky.parser.downloadJson') }}
                </button>
            </div>

            <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
        </div>
    </section>

    <section class="intel-panel-strong">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold">{{ t('bluesky.parser.progress.title') }}</h3>
            <span class="rounded-full border border-border px-2 py-1 text-xs text-muted-foreground">
                {{ stageLabel }}
            </span>
        </div>

        <div class="mt-3 h-3 overflow-hidden rounded-full bg-muted">
            <div
                class="h-full bg-cyan-400 transition-all"
                :style="{ width: `${Math.max(0, Math.min(100, progress))}%` }"
            />
        </div>

        <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            <article class="rounded-lg border border-border/70 bg-background/70 p-3">
                <p class="text-xs text-muted-foreground">{{ t('bluesky.parser.progress.posts') }}</p>
                <p class="mt-1 text-xl font-semibold">{{ processedPosts }}</p>
            </article>
            <article class="rounded-lg border border-border/70 bg-background/70 p-3">
                <p class="text-xs text-muted-foreground">{{ t('bluesky.parser.progress.authoredReplies') }}</p>
                <p class="mt-1 text-xl font-semibold">{{ processedAuthoredReplies }}</p>
            </article>
            <article class="rounded-lg border border-border/70 bg-background/70 p-3">
                <p class="text-xs text-muted-foreground">{{ t('bluesky.parser.progress.receivedReplies') }}</p>
                <p class="mt-1 text-xl font-semibold">{{ processedReceivedReplies }}</p>
            </article>
            <article class="rounded-lg border border-border/70 bg-background/70 p-3">
                <p class="text-xs text-muted-foreground">{{ t('bluesky.parser.progress.followers') }}</p>
                <p class="mt-1 text-xl font-semibold">{{ processedFollowers }}</p>
            </article>
            <article class="rounded-lg border border-border/70 bg-background/70 p-3">
                <p class="text-xs text-muted-foreground">{{ t('bluesky.parser.progress.follows') }}</p>
                <p class="mt-1 text-xl font-semibold">{{ processedFollows }}</p>
            </article>
            <article class="rounded-lg border border-border/70 bg-background/70 p-3">
                <p class="text-xs text-muted-foreground">{{ t('bluesky.parser.progress.reactions') }}</p>
                <p class="mt-1 text-xl font-semibold">{{ processedReactions }}</p>
            </article>
        </div>
    </section>
</template>
