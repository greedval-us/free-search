<script setup lang="ts">
import { computed } from 'vue';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import { useI18n } from '@/composables/useI18n';
import type { TelegramAnalyticsSummary } from '../../../types';

type TopMedia = TelegramAnalyticsSummary['summary']['topMedia'];
type TopReactions = TelegramAnalyticsSummary['summary']['topReactions'];

const props = defineProps<{
    topMedia: TopMedia;
    topReactions: TopReactions;
}>();

const { t } = useI18n();

const maxDistribution = computed(() => {
    const mediaMax = Math.max(...props.topMedia.map((item) => item.count), 1);
    const reactionMax = Math.max(
        ...props.topReactions.map((item) => item.count),
        1
    );

    return Math.max(mediaMax, reactionMax, 1);
});

const formatNumber = (value: number | null | undefined) => {
    const numeric = Number(value);

    return new Intl.NumberFormat().format(
        Number.isFinite(numeric) ? numeric : 0
    );
};

const mediaLabel = (key: string) => {
    const map: Record<string, string> = {
        photo: t('telegram.mediaTypes.photo'),
        video: t('telegram.mediaTypes.video'),
        document: t('telegram.mediaTypes.document'),
        audio: t('telegram.mediaTypes.audio'),
        geo: t('telegram.mediaTypes.geo'),
        poll: t('telegram.mediaTypes.poll'),
        contact: t('telegram.mediaTypes.contact'),
        link_preview: t('telegram.mediaTypes.link_preview'),
        other: t('telegram.mediaTypes.other'),
        none: t('telegram.mediaTypes.none'),
    };

    return map[key] ?? key;
};

const widthForCount = (count: number) =>
    `${Math.max(6, (count / maxDistribution.value) * 100)}%`;
</script>

<template>
    <article class="intel-panel-strong">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold">
                    {{ t('telegram.analytics.charts.distribution') }}
                </h3>
                <span class="text-xs text-muted-foreground">{{
                    t('telegram.analytics.charts.distributionHint')
                }}</span>
            </div>
            <HelpTooltip
                :label="t('telegram.analytics.help.label')"
                :text="t('telegram.analytics.help.distribution')"
                width-class="w-64"
                align="right"
            />
        </div>

        <div class="mt-4 space-y-5">
            <div>
                <h4
                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    {{ t('telegram.analytics.charts.media') }}
                </h4>
                <div class="mt-3 space-y-3">
                    <div
                        v-for="item in topMedia"
                        :key="item.key"
                        class="space-y-1"
                    >
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium">{{
                                mediaLabel(item.key)
                            }}</span>
                            <span class="text-muted-foreground"
                                >{{ formatNumber(item.count) }} /
                                {{ item.share }}%</span
                            >
                        </div>
                        <div class="h-2 rounded-full bg-muted">
                            <div
                                class="h-2 rounded-full bg-primary"
                                :style="{ width: widthForCount(item.count) }"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <h4
                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    {{ t('telegram.analytics.charts.reactions') }}
                </h4>
                <div class="mt-3 space-y-3">
                    <div
                        v-for="item in topReactions"
                        :key="item.label"
                        class="space-y-1"
                    >
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium">{{ item.label }}</span>
                            <span class="text-muted-foreground"
                                >{{ formatNumber(item.count) }} /
                                {{ item.share }}%</span
                            >
                        </div>
                        <div class="h-2 rounded-full bg-muted">
                            <div
                                class="h-2 rounded-full bg-emerald-400"
                                :style="{ width: widthForCount(item.count) }"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </article>
</template>
