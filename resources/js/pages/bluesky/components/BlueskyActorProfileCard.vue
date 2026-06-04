<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import type { BlueskyActor } from '../types';

defineProps<{
    actor: BlueskyActor;
}>();

const { t } = useI18n();
</script>

<template>
    <article class="rounded-lg border border-border/80 bg-background/70 p-4">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <a
                    :href="actor.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-sm font-semibold text-primary hover:underline"
                >
                    {{ actor.displayName || actor.handle }}
                </a>
                <p class="text-xs text-muted-foreground">@{{ actor.handle }}</p>
            </div>

            <a
                :href="actor.url"
                target="_blank"
                rel="noopener noreferrer"
                class="intel-link-action"
            >
                {{ t('bluesky.common.openProfile') }}
            </a>
        </div>

        <p class="mt-3 text-sm leading-relaxed text-foreground">
            {{ actor.description || t('bluesky.search.noBio') }}
        </p>

        <div class="mt-3 flex flex-wrap gap-2 text-xs text-muted-foreground">
            <span class="intel-pill">
                {{ t('bluesky.metrics.followers') }}: {{ actor.followersCount }}
            </span>
            <span class="intel-pill">
                {{ t('bluesky.metrics.following') }}: {{ actor.followsCount }}
            </span>
            <span class="intel-pill">
                {{ t('bluesky.metrics.posts') }}: {{ actor.postsCount }}
            </span>
        </div>

        <div v-if="$slots.actions" class="mt-3 flex flex-wrap gap-2">
            <slot name="actions" />
        </div>

        <div v-if="$slots.details" class="mt-3 space-y-3">
            <slot name="details" />
        </div>
    </article>
</template>
