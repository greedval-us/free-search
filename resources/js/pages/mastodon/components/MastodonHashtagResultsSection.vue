<script setup lang="ts">
import { ExternalLink } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';
import MastodonStatusCard from './MastodonStatusCard.vue';
import type { MastodonHashtag, MastodonHashtagDetailsState } from '../types';

defineProps<{
    hashtags: MastodonHashtag[];
    formatDate: (value: string) => string;
    mastodonText: (key: string, fallback: string) => string;
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
        <div
            class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
        >
            {{ t('mastodon.sections.hashtags') }}
        </div>
        <article
            v-for="hashtag in hashtags"
            :key="hashtag.name"
            class="rounded-lg border border-border/80 bg-background/70 p-3"
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
                    class="cursor-pointer rounded-full border border-input px-2 py-1 text-xs text-primary hover:bg-accent"
                >
                    <ExternalLink class="mr-1 inline h-3 w-3" />
                    {{ t('mastodon.common.openTag') }}
                </a>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                <button
                    type="button"
                    class="cursor-pointer rounded-full border border-input px-2 py-1 text-xs text-foreground hover:bg-accent"
                    @click="toggleHashtagStatuses(hashtag.name)"
                >
                    {{
                        ensureHashtagDetailsState(hashtag.name).open
                            ? mastodonText(
                                  'mastodon.hashtags.hidePosts',
                                  'Скрыть посты'
                              )
                            : mastodonText(
                                  'mastodon.hashtags.showPosts',
                                  'Посты по хэштегу'
                              )
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
                    class="rounded-md border border-border/70 bg-card/60 p-2 text-xs text-muted-foreground"
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
                class="mt-3 rounded-lg border border-border/70 bg-card/60 p-3"
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
                    <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-4">
                        <div
                            class="rounded-md border border-border/70 bg-background/70 p-2 text-xs"
                        >
                            <div class="text-muted-foreground">
                                {{
                                    mastodonText(
                                        'mastodon.hashtags.uniqueAccounts',
                                        'Уникальные аккаунты в загруженной выборке'
                                    )
                                }}
                            </div>
                            <div
                                class="mt-1 text-sm font-semibold text-foreground"
                            >
                                {{
                                    ensureHashtagDetailsState(hashtag.name)
                                        .uniqueAccountsCount
                                }}
                            </div>
                        </div>
                        <div
                            class="rounded-md border border-border/70 bg-background/70 p-2 text-xs"
                        >
                            <div class="text-muted-foreground">
                                {{
                                    mastodonText(
                                        'mastodon.hashtags.uniqueInstances',
                                        'Уникальные инстансы в загруженной выборке'
                                    )
                                }}
                            </div>
                            <div
                                class="mt-1 text-sm font-semibold text-foreground"
                            >
                                {{
                                    ensureHashtagDetailsState(hashtag.name)
                                        .uniqueInstancesCount
                                }}
                            </div>
                        </div>
                        <div
                            class="rounded-md border border-border/70 bg-background/70 p-2 text-xs"
                        >
                            <div class="text-muted-foreground">
                                {{
                                    mastodonText(
                                        'mastodon.hashtags.postsWithMedia',
                                        'Посты с медиа в загруженной выборке'
                                    )
                                }}
                            </div>
                            <div
                                class="mt-1 text-sm font-semibold text-foreground"
                            >
                                {{
                                    ensureHashtagDetailsState(hashtag.name)
                                        .postsWithMediaCount
                                }}
                            </div>
                        </div>
                        <div
                            class="rounded-md border border-border/70 bg-background/70 p-2 text-xs"
                        >
                            <div class="text-muted-foreground">
                                {{
                                    mastodonText(
                                        'mastodon.hashtags.postsWithLinks',
                                        'Посты со ссылками в загруженной выборке'
                                    )
                                }}
                            </div>
                            <div
                                class="mt-1 text-sm font-semibold text-foreground"
                            >
                                {{
                                    ensureHashtagDetailsState(hashtag.name)
                                        .postsWithLinksCount
                                }}
                            </div>
                        </div>
                    </div>

                    <p class="text-[11px] text-muted-foreground">
                        {{
                            mastodonText(
                                'mastodon.hashtags.sampleNote',
                                'Эта сводка считается только по постам, которые уже загружены ниже, а не по всей истории хэштега.'
                            )
                        }}
                    </p>

                    <div
                        v-if="
                            ensureHashtagDetailsState(hashtag.name)
                                .uniqueAccounts.length > 0
                        "
                        class="space-y-2"
                    >
                        <div
                            class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            {{
                                mastodonText(
                                    'mastodon.hashtags.accountsSection',
                                    'Кто использовал'
                                )
                            }}
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
                                class="rounded-full border border-input px-2 py-1 text-xs text-primary hover:bg-accent"
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
                        <div
                            class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            {{
                                mastodonText(
                                    'mastodon.hashtags.instancesSection',
                                    'Инстансы'
                                )
                            }}
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="domain in ensureHashtagDetailsState(
                                    hashtag.name
                                ).instanceDomains"
                                :key="`${hashtag.name}-domain-${domain}`"
                                class="rounded-full border border-cyan-500/30 bg-cyan-500/10 px-2 py-1 text-xs text-cyan-700"
                            >
                                {{ domain }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div
                            class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            {{
                                mastodonText(
                                    'mastodon.hashtags.postsSection',
                                    'Посты по хэштегу'
                                )
                            }}
                        </div>

                        <p
                            v-if="
                                ensureHashtagDetailsState(hashtag.name).statuses
                                    .length === 0
                            "
                            class="text-xs text-muted-foreground"
                        >
                            {{
                                mastodonText(
                                    'mastodon.hashtags.emptyPosts',
                                    'Посты не найдены.'
                                )
                            }}
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
                                class="cursor-pointer rounded-md border border-input bg-background px-3 py-2 text-xs font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
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
