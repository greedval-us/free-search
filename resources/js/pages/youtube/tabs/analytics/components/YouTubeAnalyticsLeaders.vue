<script setup lang="ts">
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import SectionCard from '@/components/ui/SectionCard.vue';
import { useI18n } from '@/composables/useI18n';
import type { YouTubeVideo } from '../../../types';

const props = defineProps<{
    groups: Array<{ key: string; title: string; items: YouTubeVideo[] }>;
    formatDate: (value: string) => string;
    videoMetric: (video: YouTubeVideo, key: string) => string;
}>();

const { t } = useI18n();
</script>

<template>
    <section class="grid gap-4 xl:grid-cols-2">
        <SectionCard
            v-for="group in props.groups"
            :key="group.key"
            :title="group.title"
        >
            <template #actions>
                <HelpTooltip
                    :label="t('youtube.analytics.help.label')"
                    :text="t('youtube.analytics.help.leaders')"
                    width-class="w-72"
                    align="right"
                />
            </template>

            <div class="space-y-2">
                <article
                    v-for="video in group.items"
                    :key="`${group.key}-${video.id}`"
                    class="rounded-lg border border-border/80 bg-background/70 p-3"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="line-clamp-2 text-sm font-medium">
                                {{ video.title }}
                            </h3>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ props.videoMetric(video, group.key) }} ·
                                {{ video.durationLabel || video.duration }} ·
                                {{ props.formatDate(video.publishedAt) }}
                            </p>
                        </div>
                        <a
                            :href="video.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="shrink-0 rounded-md border border-input px-3 py-1 text-xs hover:bg-accent"
                        >
                            {{ t('youtube.common.open') }}
                        </a>
                    </div>
                </article>
            </div>
        </SectionCard>
    </section>
</template>
