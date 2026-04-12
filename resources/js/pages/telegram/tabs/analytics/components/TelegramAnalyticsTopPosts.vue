<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';

type TopPost = {
    id: number;
    date: number;
    message: string;
    telegramUrl: string | null;
    views: number;
    forwards: number;
    replies: number;
    reactions: number;
    mediaLabel: string | null;
    score: number;
};

defineProps<{
    posts: TopPost[];
}>();

const { t } = useI18n();

const formatNumber = (value: number | null | undefined) => {
    const numeric = Number(value);

    return new Intl.NumberFormat().format(Number.isFinite(numeric) ? numeric : 0);
};

const formatDate = (unix: number) => {
    if (!unix) {
        return '-';
    }

    return new Date(unix * 1000).toLocaleString();
};
</script>

<template>
    <article class="rounded-xl border border-sidebar-border/80 bg-card/75 p-4 shadow-xl backdrop-blur">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold">{{ t('telegram.analytics.charts.topPosts') }}</h3>
                <p class="text-xs text-muted-foreground">{{ t('telegram.analytics.charts.topPostsHint') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <div class="group relative">
                    <span
                        class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                        :aria-label="t('telegram.analytics.help.label')"
                    >
                        ?
                    </span>
                    <div class="pointer-events-none absolute right-0 top-6 z-20 hidden w-64 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                        {{ t('telegram.analytics.help.topPosts') }}
                    </div>
                </div>
                <span class="rounded-full border border-border px-2 py-1 text-xs text-muted-foreground">
                    {{ posts.length }}
                </span>
            </div>
        </div>

        <div class="mt-4 grid gap-3">
            <article
                v-for="(post, index) in posts"
                :key="post.id"
                class="rounded-xl border border-border/70 bg-background/75 p-4"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 flex-1 space-y-2">
                        <div class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                            <span>#{{ index + 1 }}</span>
                            <span>{{ formatDate(post.date) }}</span>
                            <span v-if="post.mediaLabel">{{ post.mediaLabel }}</span>
                        </div>
                        <p class="line-clamp-3 text-sm leading-relaxed">
                            {{ post.message || t('telegram.analytics.emptyPost') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="rounded-full border border-border px-2 py-1">{{ t('telegram.analytics.stats.views') }}: {{ formatNumber(post.views) }}</span>
                        <span class="rounded-full border border-border px-2 py-1">{{ t('telegram.analytics.stats.forwards') }}: {{ formatNumber(post.forwards) }}</span>
                        <span class="rounded-full border border-border px-2 py-1">{{ t('telegram.analytics.stats.replies') }}: {{ formatNumber(post.replies) }}</span>
                        <span class="rounded-full border border-border px-2 py-1">{{ t('telegram.analytics.stats.reactions') }}: {{ formatNumber(post.reactions) }}</span>
                        <span class="rounded-full border border-cyan-400/40 bg-cyan-400/10 px-2 py-1 text-cyan-200">
                            {{ t('telegram.analytics.score') }}: {{ formatNumber(post.score) }}
                        </span>
                        <a
                            v-if="post.telegramUrl"
                            :href="post.telegramUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="cursor-pointer rounded-full border border-input px-2 py-1 text-foreground hover:bg-accent"
                        >
                            {{ t('telegram.analytics.openTelegram') }}
                        </a>
                    </div>
                </div>
            </article>
        </div>
    </article>
</template>

