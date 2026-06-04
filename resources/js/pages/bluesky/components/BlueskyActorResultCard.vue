<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import type { BlueskyActor, BlueskyActorDetailsState } from '../types';
import BlueskyActorProfileCard from './BlueskyActorProfileCard.vue';
import BlueskyCompactPostCard from './BlueskyCompactPostCard.vue';

defineProps<{
    actor: BlueskyActor;
    state: BlueskyActorDetailsState;
    formatDate: (value: string) => string;
    toggleActorPosts: (actor: BlueskyActor) => void | Promise<void>;
    toggleActorFollowers: (actor: BlueskyActor) => void | Promise<void>;
    toggleActorFollows: (actor: BlueskyActor) => void | Promise<void>;
    loadActorPosts: (
        actor: BlueskyActor,
        append?: boolean
    ) => void | Promise<void>;
    loadActorFollowers: (
        actor: BlueskyActor,
        append?: boolean
    ) => void | Promise<void>;
    loadActorFollows: (
        actor: BlueskyActor,
        append?: boolean
    ) => void | Promise<void>;
}>();

const { t } = useI18n();
</script>

<template>
    <BlueskyActorProfileCard :actor="actor">
        <template #actions>
            <button
                type="button"
                class="intel-action"
                @click="toggleActorPosts(actor)"
            >
                {{
                    state.postsOpen
                        ? t('bluesky.accounts.hidePosts')
                        : t('bluesky.accounts.showPosts')
                }}
            </button>
            <button
                type="button"
                class="intel-action"
                @click="toggleActorFollowers(actor)"
            >
                {{
                    state.followersOpen
                        ? t('bluesky.accounts.hideFollowers')
                        : t('bluesky.accounts.showFollowers')
                }}
            </button>
            <button
                type="button"
                class="intel-action"
                @click="toggleActorFollows(actor)"
            >
                {{
                    state.followsOpen
                        ? t('bluesky.accounts.hideFollows')
                        : t('bluesky.accounts.showFollows')
                }}
            </button>
        </template>

        <template #details>
            <div v-if="state.postsOpen" class="intel-subsection">
                <p
                    v-if="state.postsLoading"
                    class="text-xs text-muted-foreground"
                >
                    {{ t('bluesky.engagement.loading') }}
                </p>
                <p
                    v-else-if="state.postsError"
                    class="text-xs text-destructive"
                >
                    {{ state.postsError }}
                </p>
                <div v-else class="space-y-2">
                    <div class="intel-subsection-title">
                        {{ t('bluesky.accounts.postsSection') }}
                    </div>
                    <p
                        v-if="state.posts.length === 0"
                        class="text-xs text-muted-foreground"
                    >
                        {{ t('bluesky.accounts.emptyPosts') }}
                    </p>
                    <BlueskyCompactPostCard
                        v-for="post in state.posts"
                        :key="`${actor.did}-post-${post.id}`"
                        :post="post"
                        :format-date="formatDate"
                    />
                    <div v-if="state.postsHasMore" class="pt-1">
                        <button
                            type="button"
                            :disabled="state.postsLoadingMore"
                            class="cursor-pointer rounded-md border border-input bg-background px-3 py-2 text-xs font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                            @click="loadActorPosts(actor, true)"
                        >
                            {{
                                state.postsLoadingMore
                                    ? t('bluesky.search.loadingMore')
                                    : t('bluesky.search.loadMore')
                            }}
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="state.followersOpen" class="intel-subsection">
                <p
                    v-if="state.followersLoading"
                    class="text-xs text-muted-foreground"
                >
                    {{ t('bluesky.engagement.loading') }}
                </p>
                <p
                    v-else-if="state.followersError"
                    class="text-xs text-destructive"
                >
                    {{ state.followersError }}
                </p>
                <div v-else class="space-y-2">
                    <div class="intel-subsection-title">
                        {{ t('bluesky.accounts.followersSection') }}
                    </div>
                    <p
                        v-if="state.followers.length === 0"
                        class="text-xs text-muted-foreground"
                    >
                        {{ t('bluesky.accounts.emptyFollowers') }}
                    </p>
                    <BlueskyActorProfileCard
                        v-for="follower in state.followers"
                        :key="`${actor.did}-follower-${follower.did}`"
                        :actor="follower"
                    />
                    <div v-if="state.followersHasMore" class="pt-1">
                        <button
                            type="button"
                            :disabled="state.followersLoadingMore"
                            class="cursor-pointer rounded-md border border-input bg-background px-3 py-2 text-xs font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                            @click="loadActorFollowers(actor, true)"
                        >
                            {{
                                state.followersLoadingMore
                                    ? t('bluesky.search.loadingMore')
                                    : t('bluesky.search.loadMore')
                            }}
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="state.followsOpen" class="intel-subsection">
                <p
                    v-if="state.followsLoading"
                    class="text-xs text-muted-foreground"
                >
                    {{ t('bluesky.engagement.loading') }}
                </p>
                <p
                    v-else-if="state.followsError"
                    class="text-xs text-destructive"
                >
                    {{ state.followsError }}
                </p>
                <div v-else class="space-y-2">
                    <div class="intel-subsection-title">
                        {{ t('bluesky.accounts.followsSection') }}
                    </div>
                    <p
                        v-if="state.follows.length === 0"
                        class="text-xs text-muted-foreground"
                    >
                        {{ t('bluesky.accounts.emptyFollows') }}
                    </p>
                    <BlueskyActorProfileCard
                        v-for="follow in state.follows"
                        :key="`${actor.did}-follow-${follow.did}`"
                        :actor="follow"
                    />
                    <div v-if="state.followsHasMore" class="pt-1">
                        <button
                            type="button"
                            :disabled="state.followsLoadingMore"
                            class="cursor-pointer rounded-md border border-input bg-background px-3 py-2 text-xs font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                            @click="loadActorFollows(actor, true)"
                        >
                            {{
                                state.followsLoadingMore
                                    ? t('bluesky.search.loadingMore')
                                    : t('bluesky.search.loadMore')
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </BlueskyActorProfileCard>
</template>
