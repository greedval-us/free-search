import { computed, ref } from 'vue';
import { apiRequest } from '@/lib/api';
import type {
    MastodonAccount,
    MastodonAccountDetailsState,
    MastodonAccountFollowersPayload,
    MastodonAccountStatusesPayload,
    MastodonHashtagDetailsState,
    MastodonSearchForm,
    MastodonSearchPayload,
    MastodonStatusContextPayload,
    MastodonStatusContextState,
    MastodonTagTimelinePayload,
} from '../types';

type TranslateFn = (key: string) => string;

const LIMIT_MIN = 1;
const LIMIT_MAX = 20;
const DEFAULT_LIMIT = 10;

const createSearchForm = (): MastodonSearchForm => ({
    q: '',
    type: 'statuses',
    limit: DEFAULT_LIMIT,
    resolve: false,
    author: '',
    dateFrom: '',
    dateTo: '',
    language: '',
    hasMedia: '',
    hasLinks: '',
    hasReplies: '',
    instanceDomain: '',
});

const createContextState = (): MastodonStatusContextState => ({
    open: false,
    loading: false,
    error: null,
    ancestors: [],
    descendants: [],
    descendantsTree: [],
});

const createAccountDetailsState = (): MastodonAccountDetailsState => ({
    statusesOpen: false,
    followersOpen: false,
    statusesLoading: false,
    followersLoading: false,
    statusesLoadingMore: false,
    followersLoadingMore: false,
    statusesError: null,
    followersError: null,
    statuses: [],
    followers: [],
    statusesHasMore: false,
    followersHasMore: false,
    statusesNextMaxId: null,
    followersNextMaxId: null,
});

const createHashtagDetailsState = (): MastodonHashtagDetailsState => ({
    open: false,
    loading: false,
    loadingMore: false,
    error: null,
    statuses: [],
    uniqueAccountsCount: 0,
    uniqueAccounts: [],
    uniqueInstancesCount: 0,
    instanceDomains: [],
    postsWithMediaCount: 0,
    postsWithLinksCount: 0,
    hasMore: false,
    nextMaxId: null,
});

const normalizeBooleanFilter = (value: string): string | undefined => {
    if (value === 'true' || value === 'false') {
        return value;
    }

    return undefined;
};

const mergeUniqueById = <T extends { id: string }>(
    current: T[],
    incoming: T[]
): T[] => {
    const seen = new Set<string>();

    return [...current, ...incoming].filter((item) => {
        if (seen.has(item.id)) {
            return false;
        }

        seen.add(item.id);

        return true;
    });
};

export const useMastodonSearch = (t: TranslateFn) => {
    const form = ref<MastodonSearchForm>(createSearchForm());
    const loading = ref(false);
    const loadingMore = ref(false);
    const error = ref<string | null>(null);
    const result = ref<MastodonSearchPayload | null>(null);
    const showAdvanced = ref(false);
    const searchPanelCollapsed = ref(false);
    const contextByStatusId = ref<Record<string, MastodonStatusContextState>>(
        {}
    );
    const accountDetailsById = ref<Record<string, MastodonAccountDetailsState>>(
        {}
    );
    const hashtagDetailsByName = ref<
        Record<string, MastodonHashtagDetailsState>
    >({});

    const canSearch = computed(() => form.value.q.trim().length > 0);
    const showsRepliesFilter = computed(() => form.value.type === 'statuses');
    const showsStatusFilters = computed(() => form.value.type === 'statuses');
    const totalShown = computed(
        () =>
            (result.value?.statuses.length ?? 0) +
            (result.value?.accounts.length ?? 0) +
            (result.value?.hashtags.length ?? 0)
    );

    const clampLimit = () => {
        const value = Number(form.value.limit);
        form.value.limit = Number.isFinite(value)
            ? Math.min(LIMIT_MAX, Math.max(LIMIT_MIN, Math.trunc(value)))
            : DEFAULT_LIMIT;
    };

    const formatDate = (value: string) =>
        value ? new Date(value).toLocaleString() : '-';

    const ensureContextState = (statusId: string) => {
        if (!contextByStatusId.value[statusId]) {
            contextByStatusId.value[statusId] = createContextState();
        }

        return contextByStatusId.value[statusId];
    };

    const ensureAccountDetailsState = (accountId: string) => {
        if (!accountDetailsById.value[accountId]) {
            accountDetailsById.value[accountId] = createAccountDetailsState();
        }

        return accountDetailsById.value[accountId];
    };

    const ensureHashtagDetailsState = (hashtagName: string) => {
        if (!hashtagDetailsByName.value[hashtagName]) {
            hashtagDetailsByName.value[hashtagName] =
                createHashtagDetailsState();
        }

        return hashtagDetailsByName.value[hashtagName];
    };

    const runSearch = async (append = false) => {
        if (append) {
            loadingMore.value = true;
        } else {
            loading.value = true;
            error.value = null;
        }

        const response = await apiRequest<MastodonSearchPayload>(
            '/mastodon/search',
            {
                query: {
                    q: form.value.q,
                    type: form.value.type,
                    limit: form.value.limit,
                    resolve: form.value.resolve ? 'true' : undefined,
                    language: form.value.language || undefined,
                    author: showsStatusFilters.value
                        ? form.value.author || undefined
                        : undefined,
                    dateFrom: showsStatusFilters.value
                        ? form.value.dateFrom || undefined
                        : undefined,
                    dateTo: showsStatusFilters.value
                        ? form.value.dateTo || undefined
                        : undefined,
                    hasMedia: normalizeBooleanFilter(form.value.hasMedia),
                    hasLinks: normalizeBooleanFilter(form.value.hasLinks),
                    hasReplies: normalizeBooleanFilter(form.value.hasReplies),
                    instanceDomain: form.value.instanceDomain || undefined,
                    offset: append
                        ? (result.value?.pagination.nextOffset ?? 0)
                        : 0,
                },
            }
        );

        loading.value = false;
        loadingMore.value = false;

        if (!response.ok) {
            error.value =
                response.message ?? t('mastodon.errors.requestFailed');

            return;
        }

        if (append && result.value) {
            result.value = {
                ...response.data,
                statuses: [...result.value.statuses, ...response.data.statuses],
                accounts: [...result.value.accounts, ...response.data.accounts],
                hashtags: [...result.value.hashtags, ...response.data.hashtags],
            };

            return;
        }

        result.value = response.data;
    };

    const loadContext = async (statusId: string) => {
        const state = ensureContextState(statusId);

        if (state.loading) {
            return;
        }

        state.loading = true;
        state.error = null;

        const response = await apiRequest<MastodonStatusContextPayload>(
            `/mastodon/statuses/${statusId}/context`
        );

        state.loading = false;

        if (!response.ok) {
            state.error =
                response.message ?? t('mastodon.errors.contextFailed');

            return;
        }

        state.ancestors = response.data.ancestors;
        state.descendants = response.data.descendants;
        state.descendantsTree = response.data.descendantsTree;
    };

    const toggleContext = async (statusId: string) => {
        const state = ensureContextState(statusId);
        state.open = !state.open;

        if (
            !state.open ||
            state.loading ||
            state.ancestors.length > 0 ||
            state.descendantsTree.length > 0
        ) {
            return;
        }

        await loadContext(statusId);
    };

    const loadAccountStatuses = async (accountId: string, append = false) => {
        const state = ensureAccountDetailsState(accountId);

        if (
            (append && state.statusesLoadingMore) ||
            (!append && state.statusesLoading)
        ) {
            return;
        }

        if (append) {
            state.statusesLoadingMore = true;
        } else {
            state.statusesLoading = true;
            state.statusesError = null;
        }

        const response = await apiRequest<MastodonAccountStatusesPayload>(
            `/mastodon/accounts/${accountId}/statuses`,
            {
                query: {
                    limit: form.value.limit,
                    maxId: append
                        ? (state.statusesNextMaxId ?? undefined)
                        : undefined,
                },
            }
        );

        state.statusesLoading = false;
        state.statusesLoadingMore = false;

        if (!response.ok) {
            state.statusesError =
                response.message ?? t('mastodon.errors.requestFailed');

            return;
        }

        state.statuses = append
            ? mergeUniqueById(state.statuses, response.data.statuses)
            : response.data.statuses;
        state.statusesHasMore = response.data.pagination.hasMore;
        state.statusesNextMaxId = response.data.pagination.nextMaxId;
    };

    const loadAccountFollowers = async (accountId: string, append = false) => {
        const state = ensureAccountDetailsState(accountId);

        if (
            (append && state.followersLoadingMore) ||
            (!append && state.followersLoading)
        ) {
            return;
        }

        if (append) {
            state.followersLoadingMore = true;
        } else {
            state.followersLoading = true;
            state.followersError = null;
        }

        const response = await apiRequest<MastodonAccountFollowersPayload>(
            `/mastodon/accounts/${accountId}/followers`,
            {
                query: {
                    limit: form.value.limit,
                    maxId: append
                        ? (state.followersNextMaxId ?? undefined)
                        : undefined,
                },
            }
        );

        state.followersLoading = false;
        state.followersLoadingMore = false;

        if (!response.ok) {
            state.followersError =
                response.message ?? t('mastodon.errors.requestFailed');

            return;
        }

        state.followers = append
            ? mergeUniqueById(state.followers, response.data.accounts)
            : response.data.accounts;
        state.followersHasMore = response.data.pagination.hasMore;
        state.followersNextMaxId = response.data.pagination.nextMaxId;
    };

    const toggleAccountStatuses = async (account: MastodonAccount) => {
        const state = ensureAccountDetailsState(account.id);
        state.statusesOpen = !state.statusesOpen;

        if (
            !state.statusesOpen ||
            state.statusesLoading ||
            state.statuses.length > 0
        ) {
            return;
        }

        await loadAccountStatuses(account.id);
    };

    const toggleAccountFollowers = async (account: MastodonAccount) => {
        const state = ensureAccountDetailsState(account.id);
        state.followersOpen = !state.followersOpen;

        if (
            !state.followersOpen ||
            state.followersLoading ||
            state.followers.length > 0
        ) {
            return;
        }

        await loadAccountFollowers(account.id);
    };

    const loadMoreAccountStatuses = async (accountId: string) => {
        await loadAccountStatuses(accountId, true);
    };

    const loadMoreAccountFollowers = async (accountId: string) => {
        await loadAccountFollowers(accountId, true);
    };

    const loadHashtagStatuses = async (hashtagName: string, append = false) => {
        const state = ensureHashtagDetailsState(hashtagName);

        if ((append && state.loadingMore) || (!append && state.loading)) {
            return;
        }

        if (append) {
            state.loadingMore = true;
        } else {
            state.loading = true;
            state.error = null;
        }

        const response = await apiRequest<MastodonTagTimelinePayload>(
            `/mastodon/tags/${encodeURIComponent(hashtagName)}/statuses`,
            {
                query: {
                    limit: form.value.limit,
                    maxId: append ? (state.nextMaxId ?? undefined) : undefined,
                },
            }
        );

        state.loading = false;
        state.loadingMore = false;

        if (!response.ok) {
            state.error =
                response.message ?? t('mastodon.errors.requestFailed');

            return;
        }

        state.statuses = append
            ? mergeUniqueById(state.statuses, response.data.statuses)
            : response.data.statuses;
        state.uniqueAccountsCount = response.data.analytics.uniqueAccountsCount;
        state.uniqueAccounts = response.data.analytics.uniqueAccounts;
        state.uniqueInstancesCount =
            response.data.analytics.uniqueInstancesCount;
        state.instanceDomains = response.data.analytics.instanceDomains;
        state.postsWithMediaCount = response.data.analytics.postsWithMediaCount;
        state.postsWithLinksCount = response.data.analytics.postsWithLinksCount;
        state.hasMore = response.data.pagination.hasMore;
        state.nextMaxId = response.data.pagination.nextMaxId;
    };

    const toggleHashtagStatuses = async (hashtagName: string) => {
        const state = ensureHashtagDetailsState(hashtagName);
        state.open = !state.open;

        if (!state.open || state.loading || state.statuses.length > 0) {
            return;
        }

        await loadHashtagStatuses(hashtagName);
    };

    const loadMoreHashtagStatuses = async (hashtagName: string) => {
        await loadHashtagStatuses(hashtagName, true);
    };

    return {
        limitMax: LIMIT_MAX,
        form,
        loading,
        loadingMore,
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
    };
};
