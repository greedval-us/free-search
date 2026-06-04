<script setup lang="ts">
import IntelAdvancedFilters from '@/components/ui/IntelAdvancedFilters.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import SearchControlPanel from '@/components/ui/search/SearchControlPanel.vue';
import { useI18n } from '@/composables/useI18n';
import MastodonSearchResults from '../components/MastodonSearchResults.vue';
import { useMastodonSearch } from '../composables/useMastodonSearch';

const { t } = useI18n();

const {
    limitMax,
    form,
    loading,
    error,
    result,
    showAdvanced,
    searchPanelCollapsed,
    canSearch,
    showsRepliesFilter,
    showsStatusFilters,
    totalShown,
    clampLimit,
    formatDate,
    ensureContextState,
    ensureAccountDetailsState,
    ensureHashtagDetailsState,
    runSearch,
    toggleContext,
    toggleAccountStatuses,
    toggleAccountFollowers,
    loadMoreAccountStatuses,
    loadMoreAccountFollowers,
    toggleHashtagStatuses,
    loadMoreHashtagStatuses,
} = useMastodonSearch(t);
</script>

<template>
    <SearchControlPanel
        :title="t('mastodon.search.title')"
        :help-label="t('mastodon.help.label')"
        :help-text="t('mastodon.search.hint')"
        :subtitle="t('mastodon.search.filters')"
        :collapsed-text="t('mastodon.search.collapsed')"
        :collapsed="searchPanelCollapsed"
        :show-advanced="showAdvanced"
        :loading="loading"
        :can-search="canSearch"
        :advanced-show-aria="t('mastodon.search.advancedAriaShow')"
        :advanced-hide-aria="t('mastodon.search.advancedAriaHide')"
        :submit-label="t('mastodon.search.submit')"
        :searching-label="t('mastodon.search.searching')"
        @update:collapsed="searchPanelCollapsed = $event"
        @update:show-advanced="showAdvanced = $event"
        @submit="runSearch(false)"
    >
        <template #fields>
            <div class="grid min-w-0 gap-3 md:grid-cols-2 xl:grid-cols-4">
                <label class="block min-w-0 xl:col-span-2">
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >
                        {{ t('mastodon.search.query') }}
                    </span>
                    <input
                        v-model="form.q"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('mastodon.search.placeholder')"
                    />
                </label>

                <label class="block min-w-0">
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >
                        {{ t('mastodon.search.type') }}
                    </span>
                    <select
                        v-model="form.type"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="statuses">
                            {{ t('mastodon.options.type.statuses') }}
                        </option>
                        <option value="accounts">
                            {{ t('mastodon.options.type.accounts') }}
                        </option>
                        <option value="hashtags">
                            {{ t('mastodon.options.type.hashtags') }}
                        </option>
                        <option value="all">
                            {{ t('mastodon.options.type.all') }}
                        </option>
                    </select>
                </label>

                <label
                    v-if="showsRepliesFilter"
                    class="block min-w-0 md:col-span-2 xl:col-span-1"
                >
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >
                        {{ t('mastodon.metrics.replies') }}
                    </span>
                    <span
                        class="flex h-10 items-center gap-3 rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <input
                            v-model="form.hasReplies"
                            type="checkbox"
                            class="h-4 w-4"
                            true-value="true"
                            false-value=""
                        />
                        <span>{{ t('mastodon.search.onlyWithReplies') }}</span>
                    </span>
                </label>
            </div>
        </template>
        <template #advanced>
            <IntelAdvancedFilters
                :open="!searchPanelCollapsed && showAdvanced"
                content-class="md:grid-cols-2 xl:grid-cols-4"
            >
                <label class="block min-w-0">
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >
                        {{ t('mastodon.search.limit') }}
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
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >
                        {{ t('mastodon.search.resolve') }}
                    </span>
                    <span
                        class="flex h-10 items-center gap-3 rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <input
                            v-model="form.resolve"
                            type="checkbox"
                            class="h-4 w-4"
                        />
                        <span>{{ t('mastodon.search.resolveRemote') }}</span>
                    </span>
                </label>

                <label class="block min-w-0">
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >
                        {{ t('mastodon.search.language') }}
                    </span>
                    <input
                        v-model="form.language"
                        type="text"
                        maxlength="12"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('mastodon.search.languagePlaceholder')"
                    />
                </label>

                <label v-if="showsStatusFilters" class="block min-w-0">
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >
                        {{ t('mastodon.search.author') }}
                    </span>
                    <input
                        v-model="form.author"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    />
                </label>

                <label v-if="showsStatusFilters" class="block min-w-0">
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >
                        {{ t('mastodon.search.dateFrom') }}
                    </span>
                    <input
                        v-model="form.dateFrom"
                        type="datetime-local"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    />
                </label>

                <label v-if="showsStatusFilters" class="block min-w-0">
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >
                        {{ t('mastodon.search.dateTo') }}
                    </span>
                    <input
                        v-model="form.dateTo"
                        type="datetime-local"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    />
                </label>

                <label class="block min-w-0">
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >
                        {{ t('mastodon.search.instanceDomain') }}
                    </span>
                    <input
                        v-model="form.instanceDomain"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('mastodon.search.instancePlaceholder')"
                    />
                </label>

                <label class="block min-w-0">
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >
                        {{ t('mastodon.search.hasMedia') }}
                    </span>
                    <select
                        v-model="form.hasMedia"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="">
                            {{ t('mastodon.options.any') }}
                        </option>
                        <option value="true">
                            {{ t('mastodon.options.yes') }}
                        </option>
                        <option value="false">
                            {{ t('mastodon.options.no') }}
                        </option>
                    </select>
                </label>

                <label class="block min-w-0">
                    <span
                        class="mb-1 block truncate text-xs font-medium text-muted-foreground"
                    >
                        {{ t('mastodon.search.hasLinks') }}
                    </span>
                    <select
                        v-model="form.hasLinks"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="">
                            {{ t('mastodon.options.any') }}
                        </option>
                        <option value="true">
                            {{ t('mastodon.options.yes') }}
                        </option>
                        <option value="false">
                            {{ t('mastodon.options.no') }}
                        </option>
                    </select>
                </label>
            </IntelAdvancedFilters>
        </template>
        <template #afterActions>
            <p v-if="error" class="mt-3 text-sm text-destructive">
                {{ error }}
            </p>
        </template>
    </SearchControlPanel>

    <IntelResultPanel>
        <MastodonSearchResults
            :result="result"
            :loading="loading"
            :total-shown="totalShown"
            :format-date="formatDate"
            :ensure-context-state="ensureContextState"
            :ensure-account-details-state="ensureAccountDetailsState"
            :ensure-hashtag-details-state="ensureHashtagDetailsState"
            :toggle-context="toggleContext"
            :toggle-account-statuses="toggleAccountStatuses"
            :toggle-account-followers="toggleAccountFollowers"
            :load-more-account-statuses="loadMoreAccountStatuses"
            :load-more-account-followers="loadMoreAccountFollowers"
            :toggle-hashtag-statuses="toggleHashtagStatuses"
            :load-more-hashtag-statuses="loadMoreHashtagStatuses"
        />
    </IntelResultPanel>
</template>
