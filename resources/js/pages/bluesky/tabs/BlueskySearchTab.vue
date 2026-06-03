<script setup lang="ts">
import { ChevronDown, ChevronUp, Search, Settings } from 'lucide-vue-next';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import IntelAdvancedFilters from '@/components/ui/IntelAdvancedFilters.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue';
import { useI18n } from '@/composables/useI18n';
import BlueskySearchResults from '../components/BlueskySearchResults.vue';
import { useBlueskySearch } from '../composables/useBlueskySearch';

const { t } = useI18n();

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
    toggleLikes,
    toggleReposts,
    toggleThread,
    loadLikes,
    loadReposts,
} = useBlueskySearch(t);
</script>

<template>
    <IntelSearchPanel>
        <div class="flex items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <Search class="h-4 w-4 text-cyan-400" />
                    <span>{{ t('bluesky.search.title') }}</span>
                    <HelpTooltip
                        :label="t('bluesky.help.label')"
                        :text="t('bluesky.search.hint')"
                    />
                </div>
                <p class="text-xs text-muted-foreground">
                    {{
                        searchPanelCollapsed
                            ? t('bluesky.search.collapsed')
                            : t('bluesky.search.filters')
                    }}
                </p>
            </div>

            <button
                type="button"
                class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-input text-sm text-foreground transition hover:bg-accent"
                @click="searchPanelCollapsed = !searchPanelCollapsed"
            >
                <ChevronDown v-if="searchPanelCollapsed" class="h-4 w-4" />
                <ChevronUp v-else class="h-4 w-4" />
            </button>
        </div>

        <div
            v-if="!searchPanelCollapsed"
            class="mt-3 grid gap-3 xl:grid-cols-[minmax(0,1fr)_auto]"
        >
            <div class="grid min-w-0 gap-3 md:grid-cols-2 xl:grid-cols-4">
                <label class="block min-w-0 xl:col-span-2">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                        {{ t('bluesky.search.query') }}
                    </span>
                    <input
                        v-model="form.q"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('bluesky.search.placeholder')"
                    />
                </label>

                <label class="block min-w-0">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                        {{ t('bluesky.search.type') }}
                    </span>
                    <select
                        v-model="form.type"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="posts">
                            {{ t('bluesky.options.type.posts') }}
                        </option>
                        <option value="actors">
                            {{ t('bluesky.options.type.actors') }}
                        </option>
                        <option value="all">
                            {{ t('bluesky.options.type.all') }}
                        </option>
                    </select>
                </label>

                <label class="block min-w-0">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                        {{ t('bluesky.search.sort') }}
                    </span>
                    <select
                        v-model="form.sort"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="latest">
                            {{ t('bluesky.options.sort.latest') }}
                        </option>
                        <option value="top">
                            {{ t('bluesky.options.sort.top') }}
                        </option>
                    </select>
                </label>
            </div>

            <div class="flex flex-wrap items-end gap-2 xl:justify-end">
                <button
                    type="button"
                    :aria-label="
                        showAdvanced
                            ? t('bluesky.search.advancedAriaHide')
                            : t('bluesky.search.advancedAriaShow')
                    "
                    class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-md border border-slate-700 bg-slate-900/80 text-slate-200 transition hover:border-cyan-300/40 hover:text-cyan-100"
                    :class="{
                        'border-cyan-400/50 bg-cyan-400/20 text-cyan-300':
                            showAdvanced,
                    }"
                    @click="showAdvanced = !showAdvanced"
                >
                    <Settings class="h-4 w-4" />
                </button>

                <button
                    :disabled="loading || !canSearch"
                    class="h-10 cursor-pointer rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                    @click="runSearch(false)"
                >
                    {{
                        loading
                            ? t('bluesky.search.searching')
                            : t('bluesky.search.submit')
                    }}
                </button>
            </div>
        </div>

        <IntelAdvancedFilters
            :open="!searchPanelCollapsed && showAdvanced"
            content-class="md:grid-cols-2 xl:grid-cols-4"
        >
            <label class="block min-w-0">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                    {{ t('bluesky.search.limit') }}
                </span>
                <input
                    v-model.number="form.limit"
                    type="number"
                    min="1"
                    :max="limitMax"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    @input="clampLimit"
                    @blur="clampLimit"
                />
            </label>

            <label class="block min-w-0">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                    {{ t('bluesky.search.language') }}
                </span>
                <input
                    v-model="form.language"
                    type="text"
                    maxlength="12"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    :placeholder="t('bluesky.search.languagePlaceholder')"
                />
            </label>

            <label class="block min-w-0">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                    {{ t('bluesky.search.author') }}
                </span>
                <input
                    v-model="form.author"
                    type="text"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    :placeholder="t('bluesky.search.authorPlaceholder')"
                />
            </label>

            <label class="block min-w-0">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                    {{ t('bluesky.search.mentions') }}
                </span>
                <input
                    v-model="form.mentions"
                    type="text"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    :placeholder="t('bluesky.search.mentionsPlaceholder')"
                />
            </label>

            <label class="block min-w-0">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                    {{ t('bluesky.search.domain') }}
                </span>
                <input
                    v-model="form.domain"
                    type="text"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    :placeholder="t('bluesky.search.domainPlaceholder')"
                />
            </label>

            <label class="block min-w-0">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                    {{ t('bluesky.search.url') }}
                </span>
                <input
                    v-model="form.url"
                    type="text"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    :placeholder="t('bluesky.search.urlPlaceholder')"
                />
            </label>

            <label class="block min-w-0">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                    {{ t('bluesky.search.tag') }}
                </span>
                <input
                    v-model="form.tag"
                    type="text"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    :placeholder="t('bluesky.search.tagPlaceholder')"
                />
            </label>

            <label class="block min-w-0">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                    {{ t('bluesky.search.since') }}
                </span>
                <input
                    v-model="form.since"
                    type="datetime-local"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                />
            </label>

            <label class="block min-w-0">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                    {{ t('bluesky.search.until') }}
                </span>
                <input
                    v-model="form.until"
                    type="datetime-local"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                />
            </label>
        </IntelAdvancedFilters>

        <p v-if="error" class="mt-3 text-sm text-destructive">{{ error }}</p>
    </IntelSearchPanel>

    <IntelResultPanel>
        <BlueskySearchResults
            :result="result"
            :loading="loading"
            :total-shown="totalShown"
            :format-date="formatDate"
            :ensure-likes-state="ensureLikesState"
            :ensure-reposts-state="ensureRepostsState"
            :ensure-thread-state="ensureThreadState"
            :toggle-likes="toggleLikes"
            :toggle-reposts="toggleReposts"
            :toggle-thread="toggleThread"
            :load-likes="loadLikes"
            :load-reposts="loadReposts"
        />

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
    </IntelResultPanel>
</template>
