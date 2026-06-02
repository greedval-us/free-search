<script setup lang="ts">
import { ExternalLink } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';
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
        <article
            v-for="account in accounts"
            :key="account.id"
            class="rounded-lg border border-border/80 bg-background/70 p-3"
        >
            <div class="flex items-start gap-3">
                <img
                    v-if="account.avatar"
                    :src="account.avatar"
                    :alt="account.displayName || account.acct"
                    class="h-12 w-12 rounded-full object-cover"
                    loading="lazy"
                />
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-semibold">
                        {{ account.displayName || account.username }}
                    </div>
                    <div class="truncate text-xs text-muted-foreground">
                        @{{ account.acct }} | {{ account.instanceDomain }} |
                        {{ formatDate(account.createdAt) }}
                    </div>
                    <p class="mt-2 text-sm leading-relaxed text-foreground">
                        {{ account.note || t('mastodon.search.noBio') }}
                    </p>
                    <div
                        class="mt-3 flex flex-wrap gap-3 text-xs text-muted-foreground"
                    >
                        <span
                            >{{ t('mastodon.metrics.followers') }}:
                            {{ account.followersCount }}</span
                        >
                        <span
                            >{{ t('mastodon.metrics.following') }}:
                            {{ account.followingCount }}</span
                        >
                        <span
                            >{{ t('mastodon.metrics.posts') }}:
                            {{ account.statusesCount }}</span
                        >
                        <span v-if="account.bot">{{
                            t('mastodon.metrics.bot')
                        }}</span>
                        <span v-if="account.group">{{
                            t('mastodon.metrics.group')
                        }}</span>
                    </div>
                    <div
                        v-if="account.fields.length > 0"
                        class="mt-3 grid gap-2 md:grid-cols-2"
                    >
                        <div
                            v-for="field in account.fields"
                            :key="`${account.id}-${field.name}-${field.value}`"
                            class="rounded-md border border-border/70 bg-card/60 p-2 text-xs"
                        >
                            <div class="font-medium text-foreground">
                                {{ field.name }}
                            </div>
                            <div class="break-all text-muted-foreground">
                                {{ field.value }}
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="cursor-pointer rounded-full border border-input px-2 py-1 text-xs text-foreground hover:bg-accent"
                            @click="toggleAccountStatuses(account)"
                        >
                            {{
                                ensureAccountDetailsState(account.id)
                                    .statusesOpen
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
                                ensureAccountDetailsState(account.id)
                                    .followersOpen
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
                        <a
                            v-if="account.url"
                            :href="account.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="cursor-pointer rounded-full border border-input px-2 py-1 text-xs text-primary hover:bg-accent"
                        >
                            <ExternalLink class="mr-1 inline h-3 w-3" />
                            {{ t('mastodon.common.openProfile') }}
                        </a>
                    </div>
                    <div
                        v-if="
                            ensureAccountDetailsState(account.id).statusesOpen
                        "
                        class="mt-3 rounded-lg border border-border/70 bg-card/60 p-3"
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
                                ensureAccountDetailsState(account.id)
                                    .statusesError
                            "
                            class="text-xs text-destructive"
                        >
                            {{
                                ensureAccountDetailsState(account.id)
                                    .statusesError
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
                                    ensureAccountDetailsState(account.id)
                                        .statuses.length === 0
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
                            <article
                                v-for="status in ensureAccountDetailsState(
                                    account.id
                                ).statuses"
                                :key="`${account.id}-status-${status.id}`"
                                class="rounded-md border border-border/70 bg-background/70 p-3"
                            >
                                <div class="mb-2 flex items-center gap-3">
                                    <img
                                        v-if="status.account.avatar"
                                        :src="status.account.avatar"
                                        :alt="
                                            status.account.displayName ||
                                            status.account.acct
                                        "
                                        class="h-8 w-8 rounded-full object-cover"
                                        loading="lazy"
                                    />
                                    <div class="min-w-0">
                                        <div
                                            class="truncate text-xs font-semibold text-foreground"
                                        >
                                            {{
                                                status.account.displayName ||
                                                status.account.username
                                            }}
                                        </div>
                                        <div
                                            class="truncate text-[11px] text-muted-foreground"
                                        >
                                            @{{ status.account.acct }} |
                                            {{
                                                status.instanceDomain ||
                                                status.account.instanceDomain
                                            }}
                                            |
                                            {{ formatDate(status.createdAt) }}
                                        </div>
                                    </div>
                                </div>

                                <p
                                    v-if="status.spoilerText"
                                    class="mb-2 rounded-md border border-amber-500/30 bg-amber-500/10 px-2 py-1 text-[11px] text-amber-700"
                                >
                                    {{ status.spoilerText }}
                                </p>

                                <p
                                    class="text-xs leading-relaxed text-foreground"
                                >
                                    {{
                                        status.content ||
                                        t('mastodon.search.noText')
                                    }}
                                </p>

                                <div
                                    class="mt-2 flex flex-wrap gap-3 text-[11px] text-muted-foreground"
                                >
                                    <span
                                        >{{ t('mastodon.metrics.postType') }}:
                                        {{
                                            t(
                                                `mastodon.postTypes.${status.postType}`
                                            )
                                        }}</span
                                    >
                                    <span
                                        >{{ t('mastodon.metrics.replies') }}:
                                        {{ status.repliesCount }}</span
                                    >
                                    <span
                                        >{{ t('mastodon.metrics.reblogs') }}:
                                        {{ status.reblogsCount }}</span
                                    >
                                    <span
                                        >{{ t('mastodon.metrics.favourites') }}:
                                        {{ status.favouritesCount }}</span
                                    >
                                </div>

                                <div class="mt-2 flex flex-wrap gap-2">
                                    <a
                                        v-if="status.url"
                                        :href="status.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="cursor-pointer rounded-full border border-input px-2 py-1 text-[11px] text-primary hover:bg-accent"
                                    >
                                        <ExternalLink
                                            class="mr-1 inline h-3 w-3"
                                        />
                                        {{ t('mastodon.common.open') }}
                                    </a>

                                    <span
                                        v-for="tag in status.tags"
                                        :key="`${account.id}-inner-tag-${status.id}-${tag}`"
                                        class="rounded-full border border-input px-2 py-1 text-[11px] text-muted-foreground"
                                    >
                                        #{{ tag }}
                                    </span>

                                    <span
                                        v-for="domain in status.domains"
                                        :key="`${account.id}-inner-domain-${status.id}-${domain}`"
                                        class="rounded-full border border-cyan-500/30 bg-cyan-500/10 px-2 py-1 text-[11px] text-cyan-700"
                                    >
                                        {{ domain }}
                                    </span>
                                </div>

                                <div
                                    v-if="
                                        status.mentions.length > 0 ||
                                        status.links.length > 0
                                    "
                                    class="mt-2 space-y-2 text-[11px]"
                                >
                                    <div
                                        v-if="status.mentions.length > 0"
                                        class="flex flex-wrap gap-2"
                                    >
                                        <span class="text-muted-foreground"
                                            >{{
                                                t('mastodon.metrics.mentions')
                                            }}:</span
                                        >
                                        <a
                                            v-for="mention in status.mentions"
                                            :key="`${account.id}-inner-mention-${status.id}-${mention.acct}`"
                                            :href="mention.url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="rounded-full border border-input px-2 py-1 text-primary hover:bg-accent"
                                        >
                                            @{{ mention.acct }}
                                        </a>
                                    </div>

                                    <div
                                        v-if="status.links.length > 0"
                                        class="space-y-1"
                                    >
                                        <div class="text-muted-foreground">
                                            {{ t('mastodon.metrics.links') }}:
                                        </div>
                                        <a
                                            v-for="link in status.links"
                                            :key="`${account.id}-inner-link-${status.id}-${link}`"
                                            :href="link"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="block truncate text-primary hover:underline"
                                        >
                                            {{ link }}
                                        </a>
                                    </div>
                                </div>
                            </article>

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
                        v-if="
                            ensureAccountDetailsState(account.id).followersOpen
                        "
                        class="mt-3 rounded-lg border border-border/70 bg-card/60 p-3"
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
                                ensureAccountDetailsState(account.id)
                                    .followersError
                            "
                            class="text-xs text-destructive"
                        >
                            {{
                                ensureAccountDetailsState(account.id)
                                    .followersError
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
                                    ensureAccountDetailsState(account.id)
                                        .followers.length === 0
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
                            <article
                                v-for="follower in ensureAccountDetailsState(
                                    account.id
                                ).followers"
                                :key="`${account.id}-follower-${follower.id}`"
                                class="rounded-md border border-border/70 bg-background/70 p-3"
                            >
                                <div class="flex items-start gap-3">
                                    <img
                                        v-if="follower.avatar"
                                        :src="follower.avatar"
                                        :alt="
                                            follower.displayName ||
                                            follower.acct
                                        "
                                        class="h-10 w-10 rounded-full object-cover"
                                        loading="lazy"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="truncate text-xs font-semibold text-foreground"
                                        >
                                            {{
                                                follower.displayName ||
                                                follower.username
                                            }}
                                        </div>
                                        <div
                                            class="truncate text-[11px] text-muted-foreground"
                                        >
                                            @{{ follower.acct }} |
                                            {{ follower.instanceDomain }} |
                                            {{ formatDate(follower.createdAt) }}
                                        </div>
                                        <p
                                            class="mt-2 text-xs leading-relaxed text-foreground"
                                        >
                                            {{
                                                follower.note ||
                                                t('mastodon.search.noBio')
                                            }}
                                        </p>
                                        <div
                                            class="mt-2 flex flex-wrap gap-3 text-[11px] text-muted-foreground"
                                        >
                                            <span
                                                >{{
                                                    t(
                                                        'mastodon.metrics.followers'
                                                    )
                                                }}:
                                                {{
                                                    follower.followersCount
                                                }}</span
                                            >
                                            <span
                                                >{{
                                                    t(
                                                        'mastodon.metrics.following'
                                                    )
                                                }}:
                                                {{
                                                    follower.followingCount
                                                }}</span
                                            >
                                            <span
                                                >{{
                                                    t('mastodon.metrics.posts')
                                                }}:
                                                {{
                                                    follower.statusesCount
                                                }}</span
                                            >
                                            <span v-if="follower.bot">{{
                                                t('mastodon.metrics.bot')
                                            }}</span>
                                            <span v-if="follower.group">{{
                                                t('mastodon.metrics.group')
                                            }}</span>
                                        </div>
                                        <div
                                            v-if="follower.fields.length > 0"
                                            class="mt-2 grid gap-2 md:grid-cols-2"
                                        >
                                            <div
                                                v-for="field in follower.fields"
                                                :key="`${follower.id}-${field.name}-${field.value}`"
                                                class="rounded-md border border-border/70 bg-card/60 p-2 text-[11px]"
                                            >
                                                <div
                                                    class="font-medium text-foreground"
                                                >
                                                    {{ field.name }}
                                                </div>
                                                <div
                                                    class="break-all text-muted-foreground"
                                                >
                                                    {{ field.value }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <a
                                                v-if="follower.url"
                                                :href="follower.url"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="cursor-pointer rounded-full border border-input px-2 py-1 text-[11px] text-primary hover:bg-accent"
                                            >
                                                <ExternalLink
                                                    class="mr-1 inline h-3 w-3"
                                                />
                                                {{
                                                    t(
                                                        'mastodon.common.openProfile'
                                                    )
                                                }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </article>

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
                                    @click="
                                        loadMoreAccountFollowers(account.id)
                                    "
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
                </div>
            </div>
        </article>
    </section>
</template>
