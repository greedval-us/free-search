<script setup lang="ts">
import SearchResultsPanel from '@/components/ui/SearchResultsPanel.vue';
import { useI18n } from '@/composables/useI18n';
import type {
    MastodonAccount,
    MastodonAccountDetailsState,
    MastodonHashtagDetailsState,
    MastodonSearchPayload,
    MastodonStatusContextState,
} from '../types';
import MastodonAccountResultsSection from './MastodonAccountResultsSection.vue';
import MastodonHashtagResultsSection from './MastodonHashtagResultsSection.vue';
import MastodonStatusResultsSection from './MastodonStatusResultsSection.vue';

defineProps<{
    result: MastodonSearchPayload | null;
    loading: boolean;
    totalShown: number;
    formatDate: (value: string) => string;
    ensureContextState: (statusId: string) => MastodonStatusContextState;
    ensureAccountDetailsState: (
        accountId: string
    ) => MastodonAccountDetailsState;
    ensureHashtagDetailsState: (
        hashtagName: string
    ) => MastodonHashtagDetailsState;
    toggleContext: (statusId: string) => void | Promise<void>;
    toggleAccountStatuses: (account: MastodonAccount) => void | Promise<void>;
    toggleAccountFollowers: (account: MastodonAccount) => void | Promise<void>;
    loadMoreAccountStatuses: (accountId: string) => void | Promise<void>;
    loadMoreAccountFollowers: (accountId: string) => void | Promise<void>;
    toggleHashtagStatuses: (hashtagName: string) => void | Promise<void>;
    loadMoreHashtagStatuses: (hashtagName: string) => void | Promise<void>;
}>();

const { t } = useI18n();
</script>

<template>
    <SearchResultsPanel
        :title="t('mastodon.search.resultTitle')"
        :shown-label="t('mastodon.search.shown')"
        :total-shown="totalShown"
        :loading="loading"
        :has-result="result !== null"
        :has-matches="
            result
                ? result.statuses.length > 0 ||
                  result.accounts.length > 0 ||
                  result.hashtags.length > 0
                : false
        "
        :loading-text="t('mastodon.search.searching')"
        :empty-text="t('mastodon.search.empty')"
        :no-matches-text="t('mastodon.search.noMatches')"
    >
        <MastodonStatusResultsSection
            :statuses="result?.statuses ?? []"
            :format-date="formatDate"
            :ensure-context-state="ensureContextState"
            :toggle-context="toggleContext"
        />

        <MastodonAccountResultsSection
            :accounts="result?.accounts ?? []"
            :format-date="formatDate"
            :ensure-account-details-state="ensureAccountDetailsState"
            :toggle-account-statuses="toggleAccountStatuses"
            :toggle-account-followers="toggleAccountFollowers"
            :load-more-account-statuses="loadMoreAccountStatuses"
            :load-more-account-followers="loadMoreAccountFollowers"
        />

        <MastodonHashtagResultsSection
            :hashtags="result?.hashtags ?? []"
            :format-date="formatDate"
            :ensure-hashtag-details-state="ensureHashtagDetailsState"
            :toggle-hashtag-statuses="toggleHashtagStatuses"
            :load-more-hashtag-statuses="loadMoreHashtagStatuses"
        />
    </SearchResultsPanel>
</template>
