<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import type { MastodonHashtagDetailsState } from '../types';

defineProps<{
    state: MastodonHashtagDetailsState;
}>();

const { t } = useI18n();
</script>

<template>
    <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-4">
        <div
            class="rounded-md border border-border/70 bg-background/70 p-2 text-xs"
        >
            <div class="text-muted-foreground">
                {{ t('mastodon.hashtags.uniqueAccounts') }}
            </div>
            <div class="mt-1 text-sm font-semibold text-foreground">
                {{ state.uniqueAccountsCount }}
            </div>
        </div>

        <div
            class="rounded-md border border-border/70 bg-background/70 p-2 text-xs"
        >
            <div class="text-muted-foreground">
                {{ t('mastodon.hashtags.uniqueInstances') }}
            </div>
            <div class="mt-1 text-sm font-semibold text-foreground">
                {{ state.uniqueInstancesCount }}
            </div>
        </div>

        <div
            class="rounded-md border border-border/70 bg-background/70 p-2 text-xs"
        >
            <div class="text-muted-foreground">
                {{ t('mastodon.hashtags.postsWithMedia') }}
            </div>
            <div class="mt-1 text-sm font-semibold text-foreground">
                {{ state.postsWithMediaCount }}
            </div>
        </div>

        <div
            class="rounded-md border border-border/70 bg-background/70 p-2 text-xs"
        >
            <div class="text-muted-foreground">
                {{ t('mastodon.hashtags.postsWithLinks') }}
            </div>
            <div class="mt-1 text-sm font-semibold text-foreground">
                {{ state.postsWithLinksCount }}
            </div>
        </div>
    </div>
</template>
