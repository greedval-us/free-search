<script setup lang="ts">
import { onMounted } from 'vue';
import IntelAdvancedFilters from '@/components/ui/IntelAdvancedFilters.vue';
import SearchTabLayout from '@/components/ui/search/SearchTabLayout.vue';
import { useI18n } from '@/composables/useI18n';
import {
    getRepeatQueryParams,
    isRepeatAutorunEnabled,
    readRepeatQueryInt,
    readRepeatQueryParam,
} from '@/composables/useRepeatQuery';
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

const readRepeatBoolean = (value: string): boolean | null => {
    if (value === '1' || value === 'true') {
        return true;
    }

    if (value === '0' || value === 'false') {
        return false;
    }

    return null;
};

onMounted(() => {
    const params = getRepeatQueryParams();

    if (!params) {
        return;
    }

    const tab = readRepeatQueryParam(params, ['tab']);

    if (tab !== '' && tab !== 'search') {
        return;
    }

    const q = readRepeatQueryParam(params, ['q']);
    const type = readRepeatQueryParam(params, ['type']);
    const limit = readRepeatQueryInt(params, 'limit');
    const resolve = readRepeatQueryParam(params, ['resolve']);
    const author = readRepeatQueryParam(params, ['author']);
    const dateFrom = readRepeatQueryParam(params, ['dateFrom']);
    const dateTo = readRepeatQueryParam(params, ['dateTo']);
    const language = readRepeatQueryParam(params, ['language']);
    const hasMedia = readRepeatQueryParam(params, ['hasMedia']);
    const hasLinks = readRepeatQueryParam(params, ['hasLinks']);
    const hasReplies = readRepeatQueryParam(params, ['hasReplies']);
    const instanceDomain = readRepeatQueryParam(params, ['instanceDomain']);
    const resolveValue = readRepeatBoolean(resolve);

    if (q !== '') {
        form.value.q = q;
    }

    if (
        type === 'statuses' ||
        type === 'accounts' ||
        type === 'hashtags' ||
        type === 'all'
    ) {
        form.value.type = type;
    }

    if (limit !== null) {
        form.value.limit = limit;
        clampLimit();
    }

    if (resolveValue !== null) {
        form.value.resolve = resolveValue;
    }

    if (author !== '') {
        form.value.author = author;
    }

    if (dateFrom !== '') {
        form.value.dateFrom = dateFrom;
    }

    if (dateTo !== '') {
        form.value.dateTo = dateTo;
    }

    if (language !== '') {
        form.value.language = language;
    }

    if (hasMedia !== '') {
        form.value.hasMedia = hasMedia;
    }

    if (hasLinks !== '') {
        form.value.hasLinks = hasLinks;
    }

    if (hasReplies !== '') {
        form.value.hasReplies = hasReplies;
    }

    if (instanceDomain !== '') {
        form.value.instanceDomain = instanceDomain;
    }

    if (isRepeatAutorunEnabled(params) && canSearch.value) {
        void runSearch(false);
    }
});
</script>

<template>
    <SearchTabLayout
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
        :error="error"
        @update:collapsed="searchPanelCollapsed = $event"
        @update:show-advanced="showAdvanced = $event"
        @submit="runSearch(false)"
    >
        <template #fields>
            <div class="grid min-w-0 gap-3 md:grid-cols-2 xl:grid-cols-4">
                <label class="intel-field xl:col-span-2">
                    <span class="intel-label">{{
                        t('mastodon.search.query')
                    }}</span>
                    <input
                        v-model="form.q"
                        type="text"
                        class="intel-input"
                        :placeholder="t('mastodon.search.placeholder')"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('mastodon.search.type')
                    }}</span>
                    <select v-model="form.type" class="intel-select">
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
                    class="intel-field md:col-span-2 xl:col-span-1"
                >
                    <span class="intel-label">{{
                        t('mastodon.metrics.replies')
                    }}</span>
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
                <label class="intel-field">
                    <span class="intel-label">{{
                        t('mastodon.search.limit')
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
                        t('mastodon.search.resolve')
                    }}</span>
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

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('mastodon.search.language')
                    }}</span>
                    <input
                        v-model="form.language"
                        type="text"
                        maxlength="12"
                        class="intel-input"
                        :placeholder="t('mastodon.search.languagePlaceholder')"
                    />
                </label>

                <label v-if="showsStatusFilters" class="intel-field">
                    <span class="intel-label">{{
                        t('mastodon.search.author')
                    }}</span>
                    <input
                        v-model="form.author"
                        type="text"
                        class="intel-input"
                    />
                </label>

                <label v-if="showsStatusFilters" class="intel-field">
                    <span class="intel-label">{{
                        t('mastodon.search.dateFrom')
                    }}</span>
                    <input
                        v-model="form.dateFrom"
                        type="datetime-local"
                        class="intel-input"
                    />
                </label>

                <label v-if="showsStatusFilters" class="intel-field">
                    <span class="intel-label">{{
                        t('mastodon.search.dateTo')
                    }}</span>
                    <input
                        v-model="form.dateTo"
                        type="datetime-local"
                        class="intel-input"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('mastodon.search.instanceDomain')
                    }}</span>
                    <input
                        v-model="form.instanceDomain"
                        type="text"
                        class="intel-input"
                        :placeholder="t('mastodon.search.instancePlaceholder')"
                    />
                </label>

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('mastodon.search.hasMedia')
                    }}</span>
                    <select v-model="form.hasMedia" class="intel-select">
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

                <label class="intel-field">
                    <span class="intel-label">{{
                        t('mastodon.search.hasLinks')
                    }}</span>
                    <select v-model="form.hasLinks" class="intel-select">
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
        <template #results>
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
        </template>
    </SearchTabLayout>
</template>
