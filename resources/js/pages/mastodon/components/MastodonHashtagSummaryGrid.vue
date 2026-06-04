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
        <div class="intel-stat">
            <div class="intel-stat-label">
                {{ t('mastodon.hashtags.uniqueAccounts') }}
            </div>
            <div class="intel-stat-value">
                {{ state.uniqueAccountsCount }}
            </div>
        </div>

        <div class="intel-stat">
            <div class="intel-stat-label">
                {{ t('mastodon.hashtags.uniqueInstances') }}
            </div>
            <div class="intel-stat-value">
                {{ state.uniqueInstancesCount }}
            </div>
        </div>

        <div class="intel-stat">
            <div class="intel-stat-label">
                {{ t('mastodon.hashtags.postsWithMedia') }}
            </div>
            <div class="intel-stat-value">
                {{ state.postsWithMediaCount }}
            </div>
        </div>

        <div class="intel-stat">
            <div class="intel-stat-label">
                {{ t('mastodon.hashtags.postsWithLinks') }}
            </div>
            <div class="intel-stat-value">
                {{ state.postsWithLinksCount }}
            </div>
        </div>
    </div>
</template>
