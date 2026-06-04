<script setup lang="ts">
import SearchResultsPanel from '@/components/ui/SearchResultsPanel.vue';
import { useI18n } from '@/composables/useI18n';
import type {
    BlueskyActor,
    BlueskyActorDetailsState,
    BlueskyInteractionState,
    BlueskyPost,
    BlueskySearchPayload,
    BlueskyThreadState,
} from '../types';
import BlueskyActorResultCard from './BlueskyActorResultCard.vue';
import BlueskyPostCard from './BlueskyPostCard.vue';

defineProps<{
    result: BlueskySearchPayload | null;
    loading: boolean;
    totalShown: number;
    formatDate: (value: string) => string;
    ensureLikesState: (postId: string) => BlueskyInteractionState;
    ensureRepostsState: (postId: string) => BlueskyInteractionState;
    ensureThreadState: (postId: string) => BlueskyThreadState;
    ensureActorDetailsState: (actorDid: string) => BlueskyActorDetailsState;
    toggleLikes: (post: BlueskyPost) => void | Promise<void>;
    toggleReposts: (post: BlueskyPost) => void | Promise<void>;
    toggleThread: (post: BlueskyPost) => void | Promise<void>;
    loadLikes: (post: BlueskyPost, append?: boolean) => void | Promise<void>;
    loadReposts: (post: BlueskyPost, append?: boolean) => void | Promise<void>;
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
    <SearchResultsPanel
        :title="t('bluesky.search.resultTitle')"
        :shown-label="t('bluesky.search.shown')"
        :total-shown="totalShown"
        :loading="loading"
        :has-result="result !== null"
        :has-matches="
            result ? result.posts.length > 0 || result.actors.length > 0 : false
        "
        :loading-text="t('bluesky.search.searching')"
        :empty-text="t('bluesky.search.empty')"
        :no-matches-text="t('bluesky.search.noMatches')"
    >
        <section v-if="result && result.posts.length > 0" class="space-y-3">
            <div class="flex items-center justify-between gap-3">
                <h3 class="intel-subsection-title">
                    {{ t('bluesky.sections.posts') }}
                </h3>
                <span class="text-xs text-muted-foreground">{{
                    result.posts.length
                }}</span>
            </div>

            <BlueskyPostCard
                v-for="post in result.posts"
                :key="post.id"
                :post="post"
                :format-date="formatDate"
                :likes-state="ensureLikesState(post.id)"
                :reposts-state="ensureRepostsState(post.id)"
                :thread-state="ensureThreadState(post.id)"
                :toggle-likes="toggleLikes"
                :toggle-reposts="toggleReposts"
                :toggle-thread="toggleThread"
                :load-likes="loadLikes"
                :load-reposts="loadReposts"
            />
        </section>

        <section v-if="result && result.actors.length > 0" class="space-y-3">
            <div class="flex items-center justify-between gap-3">
                <h3 class="intel-subsection-title">
                    {{ t('bluesky.sections.actors') }}
                </h3>
                <span class="text-xs text-muted-foreground">{{
                    result.actors.length
                }}</span>
            </div>

            <BlueskyActorResultCard
                v-for="actor in result.actors"
                :key="actor.did"
                :actor="actor"
                :state="ensureActorDetailsState(actor.did)"
                :format-date="formatDate"
                :toggle-actor-posts="toggleActorPosts"
                :toggle-actor-followers="toggleActorFollowers"
                :toggle-actor-follows="toggleActorFollows"
                :load-actor-posts="loadActorPosts"
                :load-actor-followers="loadActorFollowers"
                :load-actor-follows="loadActorFollows"
            />
        </section>
    </SearchResultsPanel>
</template>
