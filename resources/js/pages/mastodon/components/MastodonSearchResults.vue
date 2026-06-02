<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import MastodonAccountResultsSection from './MastodonAccountResultsSection.vue';
import MastodonHashtagResultsSection from './MastodonHashtagResultsSection.vue';
import MastodonStatusResultsSection from './MastodonStatusResultsSection.vue';
import type {
    MastodonAccount,
    MastodonAccountDetailsState,
    MastodonHashtagDetailsState,
    MastodonSearchPayload,
    MastodonStatusContextState,
} from '../types';

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
    <div class="mb-3 flex items-center justify-between">
        <h2 class="text-sm font-semibold">
            {{ t('mastodon.search.resultTitle') }}
        </h2>
        <p class="text-xs text-muted-foreground">
            {{ t('mastodon.search.shown') }}: {{ totalShown }}
        </p>
    </div>

    <div
        class="intel-scroll min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain pr-1"
    >
        <div v-if="!loading && !result" class="intel-empty">
            {{ t('mastodon.search.empty') }}
        </div>

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
    </div>
</template>
