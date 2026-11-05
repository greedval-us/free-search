<script setup lang="ts">
import { ref } from 'vue';
import IntelAdvancedFilters from '@/components/ui/IntelAdvancedFilters.vue';
import SearchTabLayout from '@/components/ui/search/SearchTabLayout.vue';
import { useI18n } from '@/composables/useI18n';
import { useYouTubeComments } from '../composables/useYouTubeComments';
import {
    SEARCH_LIMIT,
    SEARCH_TYPES,
    useYouTubeSearch,
} from '../composables/useYouTubeSearch';
import YouTubeSearchResult from './components/YouTubeSearchResult.vue';

const { t } = useI18n();
const {
    commentsByVideoId,
    resetComments,
    toggleComments,
    toggleReplies,
    loadMoreComments,
} = useYouTubeComments(t);
const {
    form,
    canSearch,
    result,
    loading,
    loadingMore,
    error,
    clampLimit,
    runSearch,
} = useYouTubeSearch(t, resetComments);
const searchPanelCollapsed = ref(false);
const showAdvanced = ref(false);
const searchOrders = ['relevance', 'date', 'viewCount', 'rating', 'title'];
const advancedSelects = [
    { field: 'safeSearch', options: ['moderate', 'none', 'strict'] },
    { field: 'videoDuration', options: ['any', 'short', 'medium', 'long'] },
    { field: 'videoDefinition', options: ['any', 'high', 'standard'] },
    { field: 'videoCaption', options: ['any', 'closedCaption', 'none'] },
] as const;
</script>

<template>
    <SearchTabLayout
        :title="t('youtube.search.title')"
        :help-label="t('youtube.help.label')"
        :help-text="t('youtube.search.hint')"
        :subtitle="t('youtube.search.filters')"
        :collapsed-text="t('youtube.search.collapsed')"
        :collapsed="searchPanelCollapsed"
        :show-advanced="showAdvanced"
        :loading="loading"
        :can-search="canSearch"
        :advanced-show-aria="t('youtube.search.advancedAriaShow')"
        :advanced-hide-aria="t('youtube.search.advancedAriaHide')"
        :submit-label="t('youtube.search.submit')"
        :searching-label="t('youtube.common.loading')"
        :error="error"
        @update:collapsed="searchPanelCollapsed = $event"
        @update:show-advanced="showAdvanced = $event"
        @submit="runSearch(false)"
    >
        <template #fields>
            <div
                class="grid min-w-0 flex-1 gap-3 md:grid-cols-2 xl:grid-cols-5"
            >
                <label class="intel-field xl:col-span-2">
                    <span class="intel-label">{{
                        t('youtube.search.query')
                    }}</span>
                    <input
                        v-model="form.q"
                        type="text"
                        class="intel-input"
                        :placeholder="t('youtube.search.placeholder')"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('youtube.search.type')
                    }}</span>
                    <select v-model="form.type" class="intel-select">
                        <option
                            v-for="type in SEARCH_TYPES"
                            :key="type"
                            :value="type"
                        >
                            {{ t(`youtube.options.searchType.${type}`) }}
                        </option>
                    </select>
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('youtube.search.channelFilter')
                    }}</span>
                    <input
                        v-model="form.channelId"
                        type="text"
                        class="intel-input"
                        :placeholder="t('youtube.search.channelPlaceholder')"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('youtube.search.order')
                    }}</span>
                    <select v-model="form.order" class="intel-select">
                        <option
                            v-for="order in searchOrders"
                            :key="order"
                            :value="order"
                        >
                            {{ t(`youtube.options.searchOrder.${order}`) }}
                        </option>
                    </select>
                </label>
            </div>
        </template>
        <template #toolbarLeading>
            <label class="intel-field min-w-[120px]">
                <span class="intel-label">{{ t('youtube.search.limit') }}</span>
                <input
                    v-model.number="form.limit"
                    type="number"
                    :min="SEARCH_LIMIT.min"
                    :max="SEARCH_LIMIT.max"
                    class="intel-input"
                    @input="clampLimit"
                    @blur="clampLimit"
                />
            </label>
        </template>
        <template #advanced>
            <IntelAdvancedFilters
                :open="!searchPanelCollapsed && showAdvanced"
                content-class="md:grid-cols-3 xl:grid-cols-6"
            >
                <label class="intel-field">
                    <span class="intel-label">{{
                        t('youtube.search.publishedAfter')
                    }}</span>
                    <input
                        v-model="form.publishedAfter"
                        type="date"
                        class="intel-input"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('youtube.search.publishedBefore')
                    }}</span>
                    <input
                        v-model="form.publishedBefore"
                        type="date"
                        class="intel-input"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('youtube.search.regionCode')
                    }}</span>
                    <input
                        v-model="form.regionCode"
                        maxlength="2"
                        class="intel-input uppercase"
                        :placeholder="t('youtube.search.regionPlaceholder')"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('youtube.search.relevanceLanguage')
                    }}</span>
                    <input
                        v-model="form.relevanceLanguage"
                        class="intel-input"
                        :placeholder="t('youtube.search.languagePlaceholder')"
                    />
                </label>

                <label
                    v-for="filter in advancedSelects"
                    :key="filter.field"
                    class="intel-field"
                >
                    <span class="intel-label">{{
                        t(`youtube.search.${filter.field}`)
                    }}</span>
                    <select
                        v-model="form[filter.field]"
                        :disabled="
                            filter.field !== 'safeSearch' &&
                            form.type !== 'video'
                        "
                        class="intel-select"
                    >
                        <option
                            v-for="option in filter.options"
                            :key="option"
                            :value="option"
                        >
                            {{ t(`youtube.options.${filter.field}.${option}`) }}
                        </option>
                    </select></label
                >

                <p class="intel-inline-note md:col-span-3 xl:col-span-6">
                    {{ t('youtube.search.publicDataHint') }}
                </p>
            </IntelAdvancedFilters>
        </template>
        <template #results>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold">
                    {{ t('youtube.search.resultTitle') }}
                </h2>
                <p class="text-xs text-muted-foreground">
                    {{ t('youtube.search.shown') }}:
                    {{ result?.items.length ?? 0 }} /
                    {{ result?.pagination.total ?? 0 }}
                </p>
            </div>

            <div
                class="intel-scroll min-h-0 flex-1 overflow-y-auto overscroll-contain pr-1"
            >
                <div
                    v-if="!loading && (!result || result.items.length === 0)"
                    class="intel-empty"
                >
                    {{ t('youtube.search.empty') }}
                </div>

                <div v-else class="space-y-3">
                    <YouTubeSearchResult
                        v-for="video in result?.items ?? []"
                        :key="video.id"
                        :video="video"
                        :comments="commentsByVideoId[video.id]"
                        @toggle-comments="toggleComments(video.id)"
                        @load-more-comments="loadMoreComments(video.id)"
                        @toggle-replies="toggleReplies(video.id, $event)"
                    />
                </div>
            </div>
        </template>
        <template #footer>
            <div
                v-if="result?.pagination.nextPageToken"
                class="mt-4 flex justify-center"
            >
                <button
                    :disabled="loadingMore"
                    class="intel-button-secondary h-auto px-4 py-2"
                    @click="runSearch(true)"
                >
                    {{
                        loadingMore
                            ? t('youtube.common.loading')
                            : t('youtube.common.loadMore')
                    }}
                </button>
            </div>
        </template>
    </SearchTabLayout>
</template>
