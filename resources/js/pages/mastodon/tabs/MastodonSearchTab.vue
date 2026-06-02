<script setup lang="ts">
import { ChevronDown, ChevronUp, Search, Settings } from 'lucide-vue-next';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import IntelAdvancedFilters from '@/components/ui/IntelAdvancedFilters.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue';
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
    <IntelSearchPanel>
        <div class="flex items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <Search class="h-4 w-4 text-cyan-400" />
                    <span>{{ t('mastodon.search.title') }}</span>
                    <HelpTooltip
                        :label="t('mastodon.help.label')"
                        :text="t('mastodon.search.hint')"
                    />
                </div>
                <p class="text-xs text-muted-foreground">
                    {{
                        searchPanelCollapsed
                            ? t('mastodon.search.collapsed')
                            : t('mastodon.search.filters')
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

            <div class="flex flex-wrap items-end gap-2 xl:justify-end">
                <button
                    type="button"
                    :aria-label="
                        showAdvanced
                            ? t('mastodon.search.advancedAriaHide')
                            : t('mastodon.search.advancedAriaShow')
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
                            ? t('mastodon.search.searching')
                            : t('mastodon.search.submit')
                    }}
                </button>
            </div>
        </div>

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
                    {{ t('telegram.comments.author') }}
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
                    {{ t('telegram.search.dateFrom') }}
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
                    {{ t('telegram.search.dateTo') }}
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
                    <option value="">{{ t('mastodon.options.any') }}</option>
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
                    <option value="">{{ t('mastodon.options.any') }}</option>
                    <option value="true">
                        {{ t('mastodon.options.yes') }}
                    </option>
                    <option value="false">
                        {{ t('mastodon.options.no') }}
                    </option>
                </select>
            </label>
        </IntelAdvancedFilters>

        <p v-if="error" class="mt-3 text-sm text-destructive">{{ error }}</p>
    </IntelSearchPanel>

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
