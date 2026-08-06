<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useI18n } from '@/composables/useI18n';
import type { YouTubeParserHistoryItem } from '../../types';

defineProps<{
    items: YouTubeParserHistoryItem[];
    loading: boolean;
    retentionDays: number;
}>();

const emit = defineEmits<{
    download: [item: YouTubeParserHistoryItem];
    downloadJson: [item: YouTubeParserHistoryItem];
}>();

const { t, locale } = useI18n();

const formatDateTime = (value: string | null) => {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat(locale.value, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const historyStageLabel = (value: string | null) => {
    if (!value) {
        return t('youtube.parser.history.unknown');
    }

    const key = `youtube.parser.progress.stage.${value}`;
    const translated = t(key);

    return translated === key ? value : translated;
};
</script>

<template>
    <section
        class="flex max-h-[72vh] min-h-0 flex-col rounded-xl border border-border/70 bg-background/70 p-4"
    >
        <div
            class="flex flex-col gap-3 border-b border-border/70 pb-4 md:flex-row md:items-start md:justify-between"
        >
            <div class="space-y-1">
                <h3 class="text-base font-semibold">
                    {{ t('youtube.parser.history.title') }}
                </h3>
                <p class="text-sm text-muted-foreground">
                    {{
                        t('youtube.parser.history.description', {
                            days: retentionDays,
                        })
                    }}
                </p>
            </div>

            <div
                class="rounded-lg border border-border/70 bg-background/80 px-3 py-2 text-xs text-muted-foreground"
            >
                {{
                    t('youtube.parser.history.retention', {
                        days: retentionDays,
                    })
                }}
            </div>
        </div>

        <div v-if="loading" class="py-6 text-sm text-muted-foreground">
            {{ t('youtube.parser.history.loading') }}
        </div>

        <div
            v-else-if="items.length === 0"
            class="py-6 text-sm text-muted-foreground"
        >
            {{ t('youtube.parser.history.empty') }}
        </div>

        <div
            v-else
            class="intel-scroll mt-4 min-h-0 flex-1 space-y-3 overflow-y-auto overscroll-contain pr-1 [scrollbar-gutter:stable]"
        >
            <article
                v-for="item in items"
                :key="item.runId"
                class="rounded-xl border border-border/70 bg-background/80 p-4"
            >
                <div
                    class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"
                >
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold">
                                {{
                                    item.videoId ||
                                    t('youtube.parser.history.unknown')
                                }}
                            </span>
                            <span
                                class="rounded-full border border-border/70 bg-background px-2 py-0.5 text-[11px] text-muted-foreground uppercase"
                            >
                                {{ historyStageLabel(item.stage) }}
                            </span>
                        </div>

                        <div
                            class="grid gap-2 text-xs text-muted-foreground md:grid-cols-2 xl:grid-cols-4"
                        >
                            <div>
                                <span class="block">
                                    {{ t('youtube.parser.history.created') }}
                                </span>
                                <span class="text-foreground">
                                    {{ formatDateTime(item.createdAt) }}
                                </span>
                            </div>
                            <div>
                                <span class="block">
                                    {{ t('youtube.parser.history.expires') }}
                                </span>
                                <span class="text-foreground">
                                    {{ formatDateTime(item.expiresAt) }}
                                </span>
                            </div>
                            <div>
                                <span class="block">
                                    {{ t('youtube.parser.videoId') }}
                                </span>
                                <span class="text-foreground">
                                    {{ item.videoId || '-' }}
                                </span>
                            </div>
                        </div>

                        <div
                            class="flex flex-wrap gap-2 text-xs text-muted-foreground"
                        >
                            <span
                                class="rounded-md border border-border/70 bg-background px-2 py-1"
                            >
                                {{
                                    t('youtube.parser.history.comments', {
                                        count: item.processedComments,
                                    })
                                }}
                            </span>
                            <span
                                class="rounded-md border border-border/70 bg-background px-2 py-1"
                            >
                                {{
                                    t('youtube.parser.history.replies', {
                                        count: item.processedReplies,
                                    })
                                }}
                            </span>
                        </div>

                        <p v-if="item.error" class="text-xs text-destructive">
                            {{ item.error }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="!item.downloadable"
                            @click="emit('download', item)"
                        >
                            {{ t('youtube.parser.download') }}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="!item.downloadable"
                            @click="emit('downloadJson', item)"
                        >
                            {{ t('youtube.parser.downloadJson') }}
                        </Button>
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>
