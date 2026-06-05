<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import type { MastodonAccount, MastodonAccountDetailsState } from '../types';
import MastodonAccountCard from './MastodonAccountCard.vue';
import MastodonStatusCard from './MastodonStatusCard.vue';

defineProps<{
    accounts: MastodonAccount[];
    formatDate: (value: string) => string;
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
        <div class="intel-subsection-title">
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
                    class="intel-action"
                    @click="toggleAccountStatuses(account)"
                >
                    {{
                        ensureAccountDetailsState(account.id).statusesOpen
                            ? t('mastodon.accounts.hidePosts')
                            : t('mastodon.accounts.showPosts')
                    }}
                </button>

                <button
                    type="button"
                    class="intel-action"
                    @click="toggleAccountFollowers(account)"
                >
                    {{
                        ensureAccountDetailsState(account.id).followersOpen
                            ? t('mastodon.accounts.hideFollowers')
                            : t('mastodon.accounts.showFollowers')
                    }}
                </button>
            </template>

            <template #details>
                <div
                    v-if="ensureAccountDetailsState(account.id).statusesOpen"
                    class="intel-subsection"
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
                        <div class="intel-subsection-title">
                            {{ t('mastodon.accounts.postsSection') }}
                        </div>

                        <p
                            v-if="
                                ensureAccountDetailsState(account.id).statuses
                                    .length === 0
                            "
                            class="text-xs text-muted-foreground"
                        >
                            {{ t('mastodon.accounts.emptyPosts') }}
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
                    class="intel-subsection"
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
                        <div class="intel-subsection-title">
                            {{ t('mastodon.accounts.followersSection') }}
                        </div>

                        <p
                            v-if="
                                ensureAccountDetailsState(account.id).followers
                                    .length === 0
                            "
                            class="text-xs text-muted-foreground"
                        >
                            {{ t('mastodon.accounts.emptyFollowers') }}
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
