<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import MastodonAccountCard from './MastodonAccountCard.vue';
import MastodonStatusCard from './MastodonStatusCard.vue';
import type { MastodonAccount, MastodonAccountDetailsState } from '../types';

defineProps<{
    accounts: MastodonAccount[];
    formatDate: (value: string) => string;
    mastodonText: (key: string, fallback: string) => string;
    ensureAccountDetailsState: (
        accountId: string
    ) => MastodonAccountDetailsState;
    toggleAccountStatuses: (account: MastodonAccount) => void | Promise<void>;
    toggleAccountFollowers: (account: MastodonAccount) => void | Promise<void>;
    loadMoreAccountStatuses: (accountId: string) => void | Promise<void>;
    loadMoreAccountFollowers: (accountId: string) => void | Promise<void>;
}>();

const { t } = useI18n();
</script>

<template>
    <section v-if="accounts.length > 0" class="space-y-3">
        <div
            class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
        >
            {{ t('mastodon.sections.accounts') }}
        </div>
        <MastodonAccountCard
            v-for="account in accounts"
            :key="account.id"
            :account="account"
            :format-date="formatDate"
        >
            <template #actions>
                <button
                    type="button"
                    class="cursor-pointer rounded-full border border-input px-2 py-1 text-xs text-foreground hover:bg-accent"
                    @click="toggleAccountStatuses(account)"
                >
                    {{
                        ensureAccountDetailsState(account.id).statusesOpen
                            ? mastodonText(
                                  'mastodon.accounts.hidePosts',
                                  'Скрыть посты'
                              )
                            : mastodonText(
                                  'mastodon.accounts.showPosts',
                                  'Посты аккаунта'
                              )
                    }}
                </button>
                <button
                    type="button"
                    class="cursor-pointer rounded-full border border-input px-2 py-1 text-xs text-foreground hover:bg-accent"
                    @click="toggleAccountFollowers(account)"
                >
                    {{
                        ensureAccountDetailsState(account.id).followersOpen
                            ? mastodonText(
                                  'mastodon.accounts.hideFollowers',
                                  'Скрыть подписчиков'
                              )
                            : mastodonText(
                                  'mastodon.accounts.showFollowers',
                                  'Подписчики'
                              )
                    }}
                </button>
            </template>

            <template #details>
                <div
                    v-if="ensureAccountDetailsState(account.id).statusesOpen"
                    class="rounded-lg border border-border/70 bg-card/60 p-3"
                >
                    <p
                        v-if="
                            ensureAccountDetailsState(account.id)
                                .statusesLoading
                        "
                        class="text-xs text-muted-foreground"
                    >
                        {{ t('mastodon.comments.loading') }}
                    </p>
                    <p
                        v-else-if="
                            ensureAccountDetailsState(account.id).statusesError
                        "
                        class="text-xs text-destructive"
                    >
                        {{
                            ensureAccountDetailsState(account.id).statusesError
                        }}
                    </p>
                    <div v-else class="space-y-2">
                        <div
                            class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            {{
                                mastodonText(
                                    'mastodon.accounts.postsSection',
                                    'Посты аккаунта'
                                )
                            }}
                        </div>
                        <p
                            v-if="
                                ensureAccountDetailsState(account.id).statuses
                                    .length === 0
                            "
                            class="text-xs text-muted-foreground"
                        >
                            {{
                                mastodonText(
                                    'mastodon.accounts.emptyPosts',
                                    'Посты не найдены.'
                                )
                            }}
                        </p>
                        <MastodonStatusCard
                            v-for="status in ensureAccountDetailsState(
                                account.id
                            ).statuses"
                            :key="`${account.id}-status-${status.id}`"
                            :status="status"
                            :format-date="formatDate"
                            compact
                        />

                        <div
                            v-if="
                                ensureAccountDetailsState(account.id)
                                    .statusesHasMore
                            "
                            class="pt-1"
                        >
                            <button
                                :disabled="
                                    ensureAccountDetailsState(account.id)
                                        .statusesLoadingMore
                                "
                                class="cursor-pointer rounded-md border border-input bg-background px-3 py-2 text-xs font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                                @click="loadMoreAccountStatuses(account.id)"
                            >
                                {{
                                    ensureAccountDetailsState(account.id)
                                        .statusesLoadingMore
                                        ? t('mastodon.search.loadingMore')
                                        : t('mastodon.search.loadMore')
                                }}
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    v-if="ensureAccountDetailsState(account.id).followersOpen"
                    class="rounded-lg border border-border/70 bg-card/60 p-3"
                >
                    <p
                        v-if="
                            ensureAccountDetailsState(account.id)
                                .followersLoading
                        "
                        class="text-xs text-muted-foreground"
                    >
                        {{ t('mastodon.comments.loading') }}
                    </p>
                    <p
                        v-else-if="
                            ensureAccountDetailsState(account.id).followersError
                        "
                        class="text-xs text-destructive"
                    >
                        {{
                            ensureAccountDetailsState(account.id).followersError
                        }}
                    </p>
                    <div v-else class="space-y-2">
                        <div
                            class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            {{
                                mastodonText(
                                    'mastodon.accounts.followersSection',
                                    'Подписчики'
                                )
                            }}
                        </div>
                        <p
                            v-if="
                                ensureAccountDetailsState(account.id).followers
                                    .length === 0
                            "
                            class="text-xs text-muted-foreground"
                        >
                            {{
                                mastodonText(
                                    'mastodon.accounts.emptyFollowers',
                                    'Подписчики не найдены.'
                                )
                            }}
                        </p>
                        <MastodonAccountCard
                            v-for="follower in ensureAccountDetailsState(
                                account.id
                            ).followers"
                            :key="`${account.id}-follower-${follower.id}`"
                            :account="follower"
                            :format-date="formatDate"
                            compact
                        />

                        <div
                            v-if="
                                ensureAccountDetailsState(account.id)
                                    .followersHasMore
                            "
                            class="pt-1"
                        >
                            <button
                                :disabled="
                                    ensureAccountDetailsState(account.id)
                                        .followersLoadingMore
                                "
                                class="cursor-pointer rounded-md border border-input bg-background px-3 py-2 text-xs font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                                @click="loadMoreAccountFollowers(account.id)"
                            >
                                {{
                                    ensureAccountDetailsState(account.id)
                                        .followersLoadingMore
                                        ? t('mastodon.search.loadingMore')
                                        : t('mastodon.search.loadMore')
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </MastodonAccountCard>
    </section>
</template>
