<script setup lang="ts">
import IntelAdvancedFilters from '@/components/ui/IntelAdvancedFilters.vue';
import SearchTabLayout from '@/components/ui/search/SearchTabLayout.vue';
import { useI18n } from '@/composables/useI18n';
import BlueskySearchResults from '../components/BlueskySearchResults.vue';
import { useBlueskySearch } from '../composables/useBlueskySearch';
import { BlueskySearchSort, BlueskySearchType } from '../types';

const { t } = useI18n();

const searchTypes = BlueskySearchType;
const searchSorts = BlueskySearchSort;

const {
    limitMax,
    form,
    loading,
    loadingMore,
    error,
    result,
    showAdvanced,
    searchPanelCollapsed,
    canSearch,
    totalShown,
    hasMore,
    clampLimit,
    runSearch,
    formatDate,
    ensureLikesState,
    ensureRepostsState,
    ensureThreadState,
    ensureActorDetailsState,
    toggleLikes,
    toggleReposts,
    toggleThread,
    loadLikes,
    loadReposts,
    toggleActorPosts,
    toggleActorFollowers,
    toggleActorFollows,
    loadActorPosts,
    loadActorFollowers,
    loadActorFollows,
} = useBlueskySearch(t);
</script>

<template>
    <SearchTabLayout
        :title="t('bluesky.search.title')"
        :help-label="t('bluesky.help.label')"
        :help-text="t('bluesky.search.hint')"
        :subtitle="t('bluesky.search.filters')"
        :collapsed-text="t('bluesky.search.collapsed')"
        :collapsed="searchPanelCollapsed"
        :show-advanced="showAdvanced"
        :loading="loading"
        :can-search="canSearch"
        :advanced-show-aria="t('bluesky.search.advancedAriaShow')"
        :advanced-hide-aria="t('bluesky.search.advancedAriaHide')"
        :submit-label="t('bluesky.search.submit')"
        :searching-label="t('bluesky.search.searching')"
        :error="error"
        @update:collapsed="searchPanelCollapsed = $event"
        @update:show-advanced="showAdvanced = $event"
        @submit="runSearch(false)"
    >
        <template #fields>
            <div class="grid min-w-0 gap-3 md:grid-cols-2 xl:grid-cols-4">
                <label class="intel-field xl:col-span-2">
                    <span class="intel-label">{{
                        t('bluesky.search.query')
                    }}</span>
                    <input
                        v-model="form.q"
                        type="text"
                        class="intel-input"
                        :placeholder="t('bluesky.search.placeholder')"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('bluesky.search.type')
                    }}</span>
                    <select v-model="form.type" class="intel-select">
                        <option :value="searchTypes.Posts">
                            {{ t('bluesky.options.type.posts') }}
                        </option>
                        <option :value="searchTypes.Actors">
                            {{ t('bluesky.options.type.actors') }}
                        </option>
                        <option :value="searchTypes.All">
                            {{ t('bluesky.options.type.all') }}
                        </option>
                    </select>
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('bluesky.search.sort')
                    }}</span>
                    <select v-model="form.sort" class="intel-select">
                        <option :value="searchSorts.Latest">
                            {{ t('bluesky.options.sort.latest') }}
                        </option>
                        <option :value="searchSorts.Top">
                            {{ t('bluesky.options.sort.top') }}
                        </option>
                    </select>
                </label>
            </div>
        </template>
        <template #advanced>
            <IntelAdvancedFilters
                :open="!searchPanelCollapsed && showAdvanced"
                content-class="md:grid-cols-2 xl:grid-cols-4"
            >
                <label class="intel-field">
                    <span class="intel-label">{{
                        t('bluesky.search.limit')
                    }}</span>
                    <input
                        v-model.number="form.limit"
                        type="number"
                        min="1"
                        :max="limitMax"
                        class="intel-input"
                        @input="clampLimit"
                        @blur="clampLimit"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('bluesky.search.language')
                    }}</span>
                    <input
                        v-model="form.language"
                        type="text"
                        maxlength="12"
                        class="intel-input"
                        :placeholder="t('bluesky.search.languagePlaceholder')"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('bluesky.search.author')
                    }}</span>
                    <input
                        v-model="form.author"
                        type="text"
                        class="intel-input"
                        :placeholder="t('bluesky.search.authorPlaceholder')"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('bluesky.search.mentions')
                    }}</span>
                    <input
                        v-model="form.mentions"
                        type="text"
                        class="intel-input"
                        :placeholder="t('bluesky.search.mentionsPlaceholder')"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('bluesky.search.domain')
                    }}</span>
                    <input
                        v-model="form.domain"
                        type="text"
                        class="intel-input"
                        :placeholder="t('bluesky.search.domainPlaceholder')"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('bluesky.search.url')
                    }}</span>
                    <input
                        v-model="form.url"
                        type="text"
                        class="intel-input"
                        :placeholder="t('bluesky.search.urlPlaceholder')"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('bluesky.search.tag')
                    }}</span>
                    <input
                        v-model="form.tag"
                        type="text"
                        class="intel-input"
                        :placeholder="t('bluesky.search.tagPlaceholder')"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('bluesky.search.since')
                    }}</span>
                    <input
                        v-model="form.since"
                        type="datetime-local"
                        class="intel-input"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('bluesky.search.until')
                    }}</span>
                    <input
                        v-model="form.until"
                        type="datetime-local"
                        class="intel-input"
                    />
                </label>
            </IntelAdvancedFilters>
        </template>
        <template #results>
            <BlueskySearchResults
                :result="result"
                :loading="loading"
                :total-shown="totalShown"
                :format-date="formatDate"
                :ensure-likes-state="ensureLikesState"
                :ensure-reposts-state="ensureRepostsState"
                :ensure-thread-state="ensureThreadState"
                :ensure-actor-details-state="ensureActorDetailsState"
                :toggle-likes="toggleLikes"
                :toggle-reposts="toggleReposts"
                :toggle-thread="toggleThread"
                :load-likes="loadLikes"
                :load-reposts="loadReposts"
                :toggle-actor-posts="toggleActorPosts"
                :toggle-actor-followers="toggleActorFollowers"
                :toggle-actor-follows="toggleActorFollows"
                :load-actor-posts="loadActorPosts"
                :load-actor-followers="loadActorFollowers"
                :load-actor-follows="loadActorFollows"
            />
        </template>
        <template #footer>
            <div v-if="hasMore" class="mt-4 flex justify-center">
                <button
                    :disabled="loadingMore"
                    class="cursor-pointer rounded-md border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                    @click="runSearch(true)"
                >
                    {{
                        loadingMore
                            ? t('bluesky.search.loadingMore')
                            : t('bluesky.search.loadMore')
                    }}
                </button>
            </div>
        </template>
    </SearchTabLayout>
</template>
