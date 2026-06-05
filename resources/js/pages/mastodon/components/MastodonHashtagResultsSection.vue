<script setup lang="ts">
import { ExternalLink } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';
import type { MastodonHashtag, MastodonHashtagDetailsState } from '../types';
import MastodonHashtagSummaryGrid from './MastodonHashtagSummaryGrid.vue';
import MastodonStatusCard from './MastodonStatusCard.vue';

defineProps<{
    hashtags: MastodonHashtag[];
    formatDate: (value: string) => string;
    ensureHashtagDetailsState: (
        hashtagName: string
    ) => MastodonHashtagDetailsState;
    toggleHashtagStatuses: (hashtagName: string) => void | Promise<void>;
    loadMoreHashtagStatuses: (hashtagName: string) => void | Promise<void>;
}>();

const { t } = useI18n();
</script>

<template>
    <section v-if="hashtags.length > 0" class="space-y-3">
        <div class="intel-subsection-title">
            {{ t('mastodon.sections.hashtags') }}
        </div>

        <article
            v-for="hashtag in hashtags"
            :key="hashtag.name"
            class="intel-result-card"
        >
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold">#{{ hashtag.name }}</div>
                    <div class="text-xs text-muted-foreground">
                        {{ t('mastodon.metrics.historyPoints') }}:
                        {{ hashtag.history.length }}
                    </div>
                </div>

                <a
                    v-if="hashtag.url"
                    :href="hashtag.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="intel-link-action"
                >
                    <ExternalLink class="mr-1 inline h-3 w-3" />
                    {{ t('mastodon.common.openTag') }}
                </a>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                <button
                    type="button"
                    class="intel-action"
                    @click="toggleHashtagStatuses(hashtag.name)"
                >
                    {{
                        ensureHashtagDetailsState(hashtag.name).open
                            ? t('mastodon.hashtags.hidePosts')
                            : t('mastodon.hashtags.showPosts')
                    }}
                </button>
            </div>

            <div
                v-if="hashtag.history.length > 0"
                class="mt-3 grid gap-2 md:grid-cols-2"
            >
                <div
                    v-for="point in hashtag.history"
                    :key="`${hashtag.name}-${point.day}`"
                    class="intel-stat text-muted-foreground"
                >
                    <div>{{ point.day }}</div>
                    <div>
                        {{ t('mastodon.metrics.uses') }}: {{ point.uses }}
                    </div>
                    <div>
                        {{ t('mastodon.metrics.accounts') }}:
                        {{ point.accounts }}
                    </div>
                </div>
            </div>

            <div
                v-if="ensureHashtagDetailsState(hashtag.name).open"
                class="intel-subsection mt-3"
            >
                <p
                    v-if="ensureHashtagDetailsState(hashtag.name).loading"
                    class="text-xs text-muted-foreground"
                >
                    {{ t('mastodon.comments.loading') }}
                </p>

                <p
                    v-else-if="ensureHashtagDetailsState(hashtag.name).error"
                    class="text-xs text-destructive"
                >
                    {{ ensureHashtagDetailsState(hashtag.name).error }}
                </p>

                <div v-else class="space-y-3">
                    <MastodonHashtagSummaryGrid
                        :state="ensureHashtagDetailsState(hashtag.name)"
                    />

                    <p class="text-[11px] text-muted-foreground">
                        {{ t('mastodon.hashtags.sampleNote') }}
                    </p>

                    <div
                        v-if="
                            ensureHashtagDetailsState(hashtag.name)
                                .uniqueAccounts.length > 0
                        "
                        class="space-y-2"
                    >
                        <div class="intel-subsection-title">
                            {{ t('mastodon.hashtags.accountsSection') }}
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a
                                v-for="account in ensureHashtagDetailsState(
                                    hashtag.name
                                ).uniqueAccounts"
                                :key="`${hashtag.name}-account-${account.id}`"
                                :href="account.url || '#'"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="intel-link-action"
                            >
                                @{{ account.acct }}
                            </a>
                        </div>
                    </div>

                    <div
                        v-if="
                            ensureHashtagDetailsState(hashtag.name)
                                .instanceDomains.length > 0
                        "
                        class="space-y-2"
                    >
                        <div class="intel-subsection-title">
                            {{ t('mastodon.hashtags.instancesSection') }}
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="domain in ensureHashtagDetailsState(
                                    hashtag.name
                                ).instanceDomains"
                                :key="`${hashtag.name}-domain-${domain}`"
                                class="intel-pill-accent"
                            >
                                {{ domain }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="intel-subsection-title">
                            {{ t('mastodon.hashtags.postsSection') }}
                        </div>

                        <p
                            v-if="
                                ensureHashtagDetailsState(hashtag.name).statuses
                                    .length === 0
                            "
                            class="text-xs text-muted-foreground"
                        >
                            {{ t('mastodon.hashtags.emptyPosts') }}
                        </p>

                        <MastodonStatusCard
                            v-for="status in ensureHashtagDetailsState(
                                hashtag.name
                            ).statuses"
                            :key="`${hashtag.name}-status-${status.id}`"
                            :status="status"
                            :format-date="formatDate"
                            compact
                        />

                        <div
                            v-if="
                                ensureHashtagDetailsState(hashtag.name).hasMore
                            "
                            class="pt-1"
                        >
                            <button
                                :disabled="
                                    ensureHashtagDetailsState(hashtag.name)
                                        .loadingMore
                                "
                                class="intel-expand-button"
                                @click="loadMoreHashtagStatuses(hashtag.name)"
                            >
                                {{
                                    ensureHashtagDetailsState(hashtag.name)
                                        .loadingMore
                                        ? t('mastodon.search.loadingMore')
                                        : t('mastodon.search.loadMore')
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    </section>
</template>
