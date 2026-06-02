<script setup lang="ts">
import { ChevronDown, ChevronUp, ExternalLink, Search, Settings } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import IntelAdvancedFilters from '@/components/ui/IntelAdvancedFilters.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue';
import { useI18n } from '@/composables/useI18n';
import { apiRequest } from '@/lib/api';
import MastodonReplyThread from '../components/MastodonReplyThread.vue';
import type {
    MastodonAccount,
    MastodonAccountFollowersPayload,
    MastodonAccountStatusesPayload,
    MastodonSearchPayload,
    MastodonStatus,
    MastodonStatusContextPayload,
    MastodonStatusThreadNode,
} from '../types';

const { t } = useI18n();

const LIMIT_MIN = 1;
const LIMIT_MAX = 20;

const form = ref({
    q: '',
    type: 'statuses',
    limit: 10,
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

const loading = ref(false);
const loadingMore = ref(false);
const error = ref<string | null>(null);
const result = ref<MastodonSearchPayload | null>(null);
const showAdvanced = ref(false);
const searchPanelCollapsed = ref(false);
const contextByStatusId = ref<
    Record<
        string,
        {
            open: boolean;
            loading: boolean;
            error: string | null;
            ancestors: MastodonStatus[];
            descendants: MastodonStatus[];
            descendantsTree: MastodonStatusThreadNode[];
        }
    >
>({});
const accountDetailsById = ref<
    Record<
        string,
        {
            statusesOpen: boolean;
            followersOpen: boolean;
            statusesLoading: boolean;
            followersLoading: boolean;
            statusesLoadingMore: boolean;
            followersLoadingMore: boolean;
            statusesError: string | null;
            followersError: string | null;
            statuses: MastodonStatus[];
            followers: MastodonAccount[];
            statusesHasMore: boolean;
            followersHasMore: boolean;
            statusesNextMaxId: string | null;
            followersNextMaxId: string | null;
        }
    >
>({});

const canSearch = computed(() => form.value.q.trim().length > 0);
const showsRepliesFilter = computed(() => form.value.type === 'statuses');
const showsStatusFilters = computed(() => form.value.type === 'statuses');
const resolveRemoteLabel = computed(() => {
    const label = t('mastodon.search.resolveRemote');

    return label === 'mastodon.search.resolveRemote'
        ? 'Искать на других инстансах'
        : label;
});
const mastodonText = (key: string, fallback: string) => {
    const label = t(key);

    return label === key ? fallback : label;
};
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
        : 10;
};

const formatDate = (value: string) =>
    value ? new Date(value).toLocaleString() : '-';

const ensureContextState = (statusId: string) => {
    if (!contextByStatusId.value[statusId]) {
        contextByStatusId.value[statusId] = {
            open: false,
            loading: false,
            error: null,
            ancestors: [],
            descendants: [],
            descendantsTree: [],
        };
    }

    return contextByStatusId.value[statusId];
};

const ensureAccountDetailsState = (accountId: string) => {
    if (!accountDetailsById.value[accountId]) {
        accountDetailsById.value[accountId] = {
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
        };
    }

    return accountDetailsById.value[accountId];
};

const normalizeBooleanFilter = (value: string): string | undefined => {
    if (value === 'true' || value === 'false') {
        return value;
    }

    return undefined;
};

const mergeUniqueById = <T extends { id: string }>(current: T[], incoming: T[]): T[] => {
    const seen = new Set<string>();

    return [...current, ...incoming].filter((item) => {
        if (seen.has(item.id)) {
            return false;
        }

        seen.add(item.id);

        return true;
    });
};

const runSearch = async (append = false) => {
    if (append) {
        loadingMore.value = true;
    } else {
        loading.value = true;
        error.value = null;
    }

    const response = await apiRequest<MastodonSearchPayload>('/mastodon/search', {
        query: {
            q: form.value.q,
            type: form.value.type,
            limit: form.value.limit,
            resolve: form.value.resolve ? 'true' : undefined,
            language: form.value.language || undefined,
            author: showsStatusFilters.value ? form.value.author || undefined : undefined,
            dateFrom: showsStatusFilters.value ? form.value.dateFrom || undefined : undefined,
            dateTo: showsStatusFilters.value ? form.value.dateTo || undefined : undefined,
            hasMedia: normalizeBooleanFilter(form.value.hasMedia),
            hasLinks: normalizeBooleanFilter(form.value.hasLinks),
            hasReplies: normalizeBooleanFilter(form.value.hasReplies),
            instanceDomain: form.value.instanceDomain || undefined,
            offset: append ? result.value?.pagination.nextOffset ?? 0 : 0,
        },
    });

    loading.value = false;
    loadingMore.value = false;

    if (!response.ok) {
        error.value = response.message ?? t('mastodon.errors.requestFailed');

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
        state.error = response.message ?? t('mastodon.errors.contextFailed');

        return;
    }

    state.ancestors = response.data.ancestors;
    state.descendants = response.data.descendants;
    state.descendantsTree = response.data.descendantsTree;
};

const toggleContext = async (statusId: string) => {
    const state = ensureContextState(statusId);
    state.open = !state.open;

    if (!state.open || state.loading || state.ancestors.length > 0 || state.descendantsTree.length > 0) {
        return;
    }

    await loadContext(statusId);
};

const loadAccountStatuses = async (accountId: string, append = false) => {
    const state = ensureAccountDetailsState(accountId);

    if ((append && state.statusesLoadingMore) || (!append && state.statusesLoading)) {
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
                maxId: append ? state.statusesNextMaxId ?? undefined : undefined,
            },
        }
    );

    state.statusesLoading = false;
    state.statusesLoadingMore = false;

    if (!response.ok) {
        state.statusesError = response.message ?? mastodonText('mastodon.errors.requestFailed', 'Ошибка запроса.');

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

    if ((append && state.followersLoadingMore) || (!append && state.followersLoading)) {
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
                maxId: append ? state.followersNextMaxId ?? undefined : undefined,
            },
        }
    );

    state.followersLoading = false;
    state.followersLoadingMore = false;

    if (!response.ok) {
        state.followersError = response.message ?? mastodonText('mastodon.errors.requestFailed', 'Ошибка запроса.');

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

    if (!state.statusesOpen || state.statusesLoading || state.statuses.length > 0) {
        return;
    }

    await loadAccountStatuses(account.id);
};

const toggleAccountFollowers = async (account: MastodonAccount) => {
    const state = ensureAccountDetailsState(account.id);
    state.followersOpen = !state.followersOpen;

    if (!state.followersOpen || state.followersLoading || state.followers.length > 0) {
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
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
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
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                        {{ t('mastodon.search.type') }}
                    </span>
                    <select
                        v-model="form.type"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="statuses">{{ t('mastodon.options.type.statuses') }}</option>
                        <option value="accounts">{{ t('mastodon.options.type.accounts') }}</option>
                        <option value="hashtags">{{ t('mastodon.options.type.hashtags') }}</option>
                        <option value="all">{{ t('mastodon.options.type.all') }}</option>
                    </select>
                </label>

                <label
                    v-if="showsRepliesFilter"
                    class="block min-w-0 md:col-span-2 xl:col-span-1"
                >
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
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
                        'border-cyan-400/50 bg-cyan-400/20 text-cyan-300': showAdvanced,
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
                    {{ loading ? t('mastodon.search.searching') : t('mastodon.search.submit') }}
                </button>
            </div>
        </div>

        <IntelAdvancedFilters
            :open="!searchPanelCollapsed && showAdvanced"
            content-class="md:grid-cols-2 xl:grid-cols-4"
        >
            <label class="block min-w-0">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                    {{ t('mastodon.search.limit') }}
                </span>
                <input
                    v-model.number="form.limit"
                    type="number"
                    min="1"
                    :max="LIMIT_MAX"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    @input="clampLimit"
                    @blur="clampLimit"
                />
            </label>

            <label class="block min-w-0">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                    {{ t('mastodon.search.resolve') }}
                </span>
                <span
                    class="flex h-10 items-center gap-3 rounded-md border border-input bg-background px-3 text-sm"
                >
                    <input v-model="form.resolve" type="checkbox" class="h-4 w-4" />
                    <span>{{ resolveRemoteLabel }}</span>
                </span>
            </label>

            <label class="block min-w-0">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
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
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                    {{ t('telegram.comments.author') }}
                </span>
                <input
                    v-model="form.author"
                    type="text"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                />
            </label>

            <label v-if="showsStatusFilters" class="block min-w-0">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                    {{ t('telegram.search.dateFrom') }}
                </span>
                <input
                    v-model="form.dateFrom"
                    type="datetime-local"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                />
            </label>

            <label v-if="showsStatusFilters" class="block min-w-0">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                    {{ t('telegram.search.dateTo') }}
                </span>
                <input
                    v-model="form.dateTo"
                    type="datetime-local"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                />
            </label>

            <label class="block min-w-0">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
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
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                    {{ t('mastodon.search.hasMedia') }}
                </span>
                <select
                    v-model="form.hasMedia"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option value="">{{ t('mastodon.options.any') }}</option>
                    <option value="true">{{ t('mastodon.options.yes') }}</option>
                    <option value="false">{{ t('mastodon.options.no') }}</option>
                </select>
            </label>

            <label class="block min-w-0">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                    {{ t('mastodon.search.hasLinks') }}
                </span>
                <select
                    v-model="form.hasLinks"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option value="">{{ t('mastodon.options.any') }}</option>
                    <option value="true">{{ t('mastodon.options.yes') }}</option>
                    <option value="false">{{ t('mastodon.options.no') }}</option>
                </select>
            </label>

        </IntelAdvancedFilters>

        <p v-if="error" class="mt-3 text-sm text-destructive">{{ error }}</p>
    </IntelSearchPanel>

    <IntelResultPanel>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold">
                {{ t('mastodon.search.resultTitle') }}
            </h2>
            <p class="text-xs text-muted-foreground">
                {{ t('mastodon.search.shown') }}: {{ totalShown }}
            </p>
        </div>

        <div class="intel-scroll min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain pr-1">
            <div v-if="!loading && !result" class="intel-empty">
                {{ t('mastodon.search.empty') }}
            </div>

            <section v-if="result && result.statuses.length > 0" class="space-y-3">
                <div class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    {{ t('mastodon.sections.statuses') }}
                </div>
                <article
                    v-for="status in result.statuses"
                    :key="status.id"
                    class="rounded-lg border border-border/80 bg-background/70 p-3"
                >
                    <div class="mb-2 flex items-center gap-3">
                        <img
                            v-if="status.account.avatar"
                            :src="status.account.avatar"
                            :alt="status.account.displayName || status.account.acct"
                            class="h-10 w-10 rounded-full object-cover"
                            loading="lazy"
                        />
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold">
                                {{ status.account.displayName || status.account.username }}
                            </div>
                            <div class="truncate text-xs text-muted-foreground">
                                @{{ status.account.acct }} | {{ status.instanceDomain || status.account.instanceDomain }} | {{ formatDate(status.createdAt) }}
                            </div>
                        </div>
                    </div>

                    <p
                        v-if="status.spoilerText"
                        class="mb-2 rounded-md border border-amber-500/30 bg-amber-500/10 px-2 py-1 text-xs text-amber-700"
                    >
                        {{ status.spoilerText }}
                    </p>

                    <p class="text-sm leading-relaxed text-foreground">
                        {{ status.content || t('mastodon.search.noText') }}
                    </p>

                    <div class="mt-3 flex flex-wrap gap-3 text-xs text-muted-foreground">
                        <span>{{ t('mastodon.metrics.postType') }}: {{ t(`mastodon.postTypes.${status.postType}`) }}</span>
                        <span>{{ t('mastodon.metrics.language') }}: {{ status.language || '-' }}</span>
                        <span>{{ t('mastodon.metrics.replies') }}: {{ status.repliesCount }}</span>
                        <span>{{ t('mastodon.metrics.reblogs') }}: {{ status.reblogsCount }}</span>
                        <span>{{ t('mastodon.metrics.favourites') }}: {{ status.favouritesCount }}</span>
                        <span>{{ t('mastodon.metrics.media') }}: {{ status.mediaAttachmentsCount }}</span>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <a
                            v-if="status.url"
                            :href="status.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="cursor-pointer rounded-full border border-input px-2 py-1 text-xs text-primary hover:bg-accent"
                        >
                            <ExternalLink class="mr-1 inline h-3 w-3" />
                            {{ t('mastodon.common.open') }}
                        </a>

                        <span
                            v-for="tag in status.tags"
                            :key="`${status.id}-${tag}`"
                            class="rounded-full border border-input px-2 py-1 text-xs text-muted-foreground"
                        >
                            #{{ tag }}
                        </span>

                        <span
                            v-for="domain in status.domains"
                            :key="`${status.id}-domain-${domain}`"
                            class="rounded-full border border-cyan-500/30 bg-cyan-500/10 px-2 py-1 text-xs text-cyan-700"
                        >
                            {{ domain }}
                        </span>

                        <button
                            v-if="status.repliesCount > 0"
                            type="button"
                            class="cursor-pointer rounded-full border border-input px-3 py-1 text-xs font-medium text-foreground hover:bg-accent"
                            @click="toggleContext(status.id)"
                        >
                            {{
                                ensureContextState(status.id).open
                                    ? t('mastodon.comments.hide')
                                    : `${t('mastodon.comments.show')} (${status.repliesCount})`
                            }}
                        </button>
                    </div>

                    <div
                        v-if="status.mentions.length > 0 || status.links.length > 0"
                        class="mt-3 space-y-2 text-xs"
                    >
                        <div v-if="status.mentions.length > 0" class="flex flex-wrap gap-2">
                            <span class="text-muted-foreground">{{ t('mastodon.metrics.mentions') }}:</span>
                            <a
                                v-for="mention in status.mentions"
                                :key="`${status.id}-mention-${mention.acct}`"
                                :href="mention.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="rounded-full border border-input px-2 py-1 text-primary hover:bg-accent"
                            >
                                @{{ mention.acct }}
                            </a>
                        </div>

                        <div v-if="status.links.length > 0" class="space-y-1">
                            <div class="text-muted-foreground">{{ t('mastodon.metrics.links') }}:</div>
                            <a
                                v-for="link in status.links"
                                :key="`${status.id}-link-${link}`"
                                :href="link"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="block truncate text-primary hover:underline"
                            >
                                {{ link }}
                            </a>
                        </div>
                    </div>

                    <div
                        v-if="ensureContextState(status.id).open"
                        class="mt-3 rounded-lg border border-border/70 bg-card/60 p-3"
                    >
                        <p
                            v-if="ensureContextState(status.id).loading"
                            class="text-xs text-muted-foreground"
                        >
                            {{ t('mastodon.comments.loading') }}
                        </p>

                        <p
                            v-else-if="ensureContextState(status.id).error"
                            class="text-xs text-destructive"
                        >
                            {{ ensureContextState(status.id).error }}
                        </p>

                        <div v-else class="space-y-3">
                            <div
                                v-if="ensureContextState(status.id).ancestors.length > 0"
                                class="space-y-2"
                            >
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                    {{ t('mastodon.comments.ancestors') }}
                                </div>
                                <article
                                    v-for="item in ensureContextState(status.id).ancestors"
                                    :key="`${status.id}-ancestor-${item.id}`"
                                    class="rounded-md border border-border/70 bg-background/70 p-2"
                                >
                                    <div class="mb-1 text-[11px] text-muted-foreground">
                                        @{{ item.account.acct }} | {{ formatDate(item.createdAt) }}
                                    </div>
                                    <p class="text-xs leading-relaxed text-foreground">
                                        {{ item.content || t('mastodon.search.noText') }}
                                    </p>
                                </article>
                            </div>

                            <div class="space-y-2">
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                    {{ t('mastodon.comments.replies') }}
                                </div>

                                <p
                                    v-if="ensureContextState(status.id).descendants.length === 0"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ t('mastodon.comments.empty') }}
                                </p>

                                <MastodonReplyThread
                                    v-else
                                    :items="ensureContextState(status.id).descendantsTree"
                                    :no-text-label="t('mastodon.search.noText')"
                                />
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <section v-if="result && result.accounts.length > 0" class="space-y-3">
                <div class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    {{ t('mastodon.sections.accounts') }}
                </div>
                <article
                    v-for="account in result.accounts"
                    :key="account.id"
                    class="rounded-lg border border-border/80 bg-background/70 p-3"
                >
                    <div class="flex items-start gap-3">
                        <img
                            v-if="account.avatar"
                            :src="account.avatar"
                            :alt="account.displayName || account.acct"
                            class="h-12 w-12 rounded-full object-cover"
                            loading="lazy"
                        />
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-semibold">
                                {{ account.displayName || account.username }}
                            </div>
                            <div class="truncate text-xs text-muted-foreground">
                                @{{ account.acct }} | {{ account.instanceDomain }} | {{ formatDate(account.createdAt) }}
                            </div>
                            <p class="mt-2 text-sm leading-relaxed text-foreground">
                                {{ account.note || t('mastodon.search.noBio') }}
                            </p>
                            <div class="mt-3 flex flex-wrap gap-3 text-xs text-muted-foreground">
                                <span>{{ t('mastodon.metrics.followers') }}: {{ account.followersCount }}</span>
                                <span>{{ t('mastodon.metrics.following') }}: {{ account.followingCount }}</span>
                                <span>{{ t('mastodon.metrics.posts') }}: {{ account.statusesCount }}</span>
                                <span v-if="account.bot">{{ t('mastodon.metrics.bot') }}</span>
                                <span v-if="account.group">{{ t('mastodon.metrics.group') }}</span>
                            </div>
                            <div v-if="account.fields.length > 0" class="mt-3 grid gap-2 md:grid-cols-2">
                                <div
                                    v-for="field in account.fields"
                                    :key="`${account.id}-${field.name}-${field.value}`"
                                    class="rounded-md border border-border/70 bg-card/60 p-2 text-xs"
                                >
                                    <div class="font-medium text-foreground">{{ field.name }}</div>
                                    <div class="break-all text-muted-foreground">{{ field.value }}</div>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="cursor-pointer rounded-full border border-input px-2 py-1 text-xs text-foreground hover:bg-accent"
                                    @click="toggleAccountStatuses(account)"
                                >
                                    {{
                                        ensureAccountDetailsState(account.id).statusesOpen
                                            ? mastodonText('mastodon.accounts.hidePosts', 'Скрыть посты')
                                            : mastodonText('mastodon.accounts.showPosts', 'Посты аккаунта')
                                    }}
                                </button>
                                <button
                                    type="button"
                                    class="cursor-pointer rounded-full border border-input px-2 py-1 text-xs text-foreground hover:bg-accent"
                                    @click="toggleAccountFollowers(account)"
                                >
                                    {{
                                        ensureAccountDetailsState(account.id).followersOpen
                                            ? mastodonText('mastodon.accounts.hideFollowers', 'Скрыть подписчиков')
                                            : mastodonText('mastodon.accounts.showFollowers', 'Подписчики')
                                    }}
                                </button>
                                <a
                                    v-if="account.url"
                                    :href="account.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="cursor-pointer rounded-full border border-input px-2 py-1 text-xs text-primary hover:bg-accent"
                                >
                                    <ExternalLink class="mr-1 inline h-3 w-3" />
                                    {{ t('mastodon.common.openProfile') }}
                                </a>
                            </div>
                            <div
                                v-if="ensureAccountDetailsState(account.id).statusesOpen"
                                class="mt-3 rounded-lg border border-border/70 bg-card/60 p-3"
                            >
                                <p
                                    v-if="ensureAccountDetailsState(account.id).statusesLoading"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ mastodonText('mastodon.comments.loading', 'Загрузка...') }}
                                </p>
                                <p
                                    v-else-if="ensureAccountDetailsState(account.id).statusesError"
                                    class="text-xs text-destructive"
                                >
                                    {{ ensureAccountDetailsState(account.id).statusesError }}
                                </p>
                                <div v-else class="space-y-2">
                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                        {{ mastodonText('mastodon.accounts.postsSection', 'Посты аккаунта') }}
                                    </div>
                                    <p
                                        v-if="ensureAccountDetailsState(account.id).statuses.length === 0"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ mastodonText('mastodon.accounts.emptyPosts', 'Посты не найдены.') }}
                                    </p>
                                    <article
                                        v-for="status in ensureAccountDetailsState(account.id).statuses"
                                        :key="`${account.id}-status-${status.id}`"
                                        class="rounded-md border border-border/70 bg-background/70 p-3"
                                    >
                                        <div class="mb-2 flex items-center gap-3">
                                            <img
                                                v-if="status.account.avatar"
                                                :src="status.account.avatar"
                                                :alt="status.account.displayName || status.account.acct"
                                                class="h-8 w-8 rounded-full object-cover"
                                                loading="lazy"
                                            />
                                            <div class="min-w-0">
                                                <div class="truncate text-xs font-semibold text-foreground">
                                                    {{ status.account.displayName || status.account.username }}
                                                </div>
                                                <div class="truncate text-[11px] text-muted-foreground">
                                                    @{{ status.account.acct }} | {{ status.instanceDomain || status.account.instanceDomain }} | {{ formatDate(status.createdAt) }}
                                                </div>
                                            </div>
                                        </div>

                                        <p
                                            v-if="status.spoilerText"
                                            class="mb-2 rounded-md border border-amber-500/30 bg-amber-500/10 px-2 py-1 text-[11px] text-amber-700"
                                        >
                                            {{ status.spoilerText }}
                                        </p>

                                        <p class="text-xs leading-relaxed text-foreground">
                                            {{ status.content || t('mastodon.search.noText') }}
                                        </p>

                                        <div class="mt-2 flex flex-wrap gap-3 text-[11px] text-muted-foreground">
                                            <span>{{ t('mastodon.metrics.postType') }}: {{ t(`mastodon.postTypes.${status.postType}`) }}</span>
                                            <span>{{ t('mastodon.metrics.replies') }}: {{ status.repliesCount }}</span>
                                            <span>{{ t('mastodon.metrics.reblogs') }}: {{ status.reblogsCount }}</span>
                                            <span>{{ t('mastodon.metrics.favourites') }}: {{ status.favouritesCount }}</span>
                                        </div>

                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <a
                                                v-if="status.url"
                                                :href="status.url"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="cursor-pointer rounded-full border border-input px-2 py-1 text-[11px] text-primary hover:bg-accent"
                                            >
                                                <ExternalLink class="mr-1 inline h-3 w-3" />
                                                {{ t('mastodon.common.open') }}
                                            </a>

                                            <span
                                                v-for="tag in status.tags"
                                                :key="`${account.id}-inner-tag-${status.id}-${tag}`"
                                                class="rounded-full border border-input px-2 py-1 text-[11px] text-muted-foreground"
                                            >
                                                #{{ tag }}
                                            </span>

                                            <span
                                                v-for="domain in status.domains"
                                                :key="`${account.id}-inner-domain-${status.id}-${domain}`"
                                                class="rounded-full border border-cyan-500/30 bg-cyan-500/10 px-2 py-1 text-[11px] text-cyan-700"
                                            >
                                                {{ domain }}
                                            </span>
                                        </div>

                                        <div
                                            v-if="status.mentions.length > 0 || status.links.length > 0"
                                            class="mt-2 space-y-2 text-[11px]"
                                        >
                                            <div v-if="status.mentions.length > 0" class="flex flex-wrap gap-2">
                                                <span class="text-muted-foreground">{{ t('mastodon.metrics.mentions') }}:</span>
                                                <a
                                                    v-for="mention in status.mentions"
                                                    :key="`${account.id}-inner-mention-${status.id}-${mention.acct}`"
                                                    :href="mention.url"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="rounded-full border border-input px-2 py-1 text-primary hover:bg-accent"
                                                >
                                                    @{{ mention.acct }}
                                                </a>
                                            </div>

                                            <div v-if="status.links.length > 0" class="space-y-1">
                                                <div class="text-muted-foreground">{{ t('mastodon.metrics.links') }}:</div>
                                                <a
                                                    v-for="link in status.links"
                                                    :key="`${account.id}-inner-link-${status.id}-${link}`"
                                                    :href="link"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="block truncate text-primary hover:underline"
                                                >
                                                    {{ link }}
                                                </a>
                                            </div>
                                        </div>
                                    </article>

                                    <div
                                        v-if="ensureAccountDetailsState(account.id).statusesHasMore"
                                        class="pt-1"
                                    >
                                        <button
                                            :disabled="ensureAccountDetailsState(account.id).statusesLoadingMore"
                                            class="cursor-pointer rounded-md border border-input bg-background px-3 py-2 text-xs font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                                            @click="loadMoreAccountStatuses(account.id)"
                                        >
                                            {{
                                                ensureAccountDetailsState(account.id).statusesLoadingMore
                                                    ? t('mastodon.search.loadingMore')
                                                    : t('mastodon.search.loadMore')
                                            }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="ensureAccountDetailsState(account.id).followersOpen"
                                class="mt-3 rounded-lg border border-border/70 bg-card/60 p-3"
                            >
                                <p
                                    v-if="ensureAccountDetailsState(account.id).followersLoading"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ mastodonText('mastodon.comments.loading', 'Загрузка...') }}
                                </p>
                                <p
                                    v-else-if="ensureAccountDetailsState(account.id).followersError"
                                    class="text-xs text-destructive"
                                >
                                    {{ ensureAccountDetailsState(account.id).followersError }}
                                </p>
                                <div v-else class="space-y-2">
                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                        {{ mastodonText('mastodon.accounts.followersSection', 'Подписчики') }}
                                    </div>
                                    <p
                                        v-if="ensureAccountDetailsState(account.id).followers.length === 0"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ mastodonText('mastodon.accounts.emptyFollowers', 'Подписчики не найдены.') }}
                                    </p>
                                    <article
                                        v-for="follower in ensureAccountDetailsState(account.id).followers"
                                        :key="`${account.id}-follower-${follower.id}`"
                                        class="rounded-md border border-border/70 bg-background/70 p-3"
                                    >
                                        <div class="flex items-start gap-3">
                                            <img
                                                v-if="follower.avatar"
                                                :src="follower.avatar"
                                                :alt="follower.displayName || follower.acct"
                                                class="h-10 w-10 rounded-full object-cover"
                                                loading="lazy"
                                            />
                                            <div class="min-w-0 flex-1">
                                                <div class="truncate text-xs font-semibold text-foreground">
                                                    {{ follower.displayName || follower.username }}
                                                </div>
                                                <div class="truncate text-[11px] text-muted-foreground">
                                                    @{{ follower.acct }} | {{ follower.instanceDomain }} | {{ formatDate(follower.createdAt) }}
                                                </div>
                                                <p class="mt-2 text-xs leading-relaxed text-foreground">
                                                    {{ follower.note || t('mastodon.search.noBio') }}
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-3 text-[11px] text-muted-foreground">
                                                    <span>{{ t('mastodon.metrics.followers') }}: {{ follower.followersCount }}</span>
                                                    <span>{{ t('mastodon.metrics.following') }}: {{ follower.followingCount }}</span>
                                                    <span>{{ t('mastodon.metrics.posts') }}: {{ follower.statusesCount }}</span>
                                                    <span v-if="follower.bot">{{ t('mastodon.metrics.bot') }}</span>
                                                    <span v-if="follower.group">{{ t('mastodon.metrics.group') }}</span>
                                                </div>
                                                <div v-if="follower.fields.length > 0" class="mt-2 grid gap-2 md:grid-cols-2">
                                                    <div
                                                        v-for="field in follower.fields"
                                                        :key="`${follower.id}-${field.name}-${field.value}`"
                                                        class="rounded-md border border-border/70 bg-card/60 p-2 text-[11px]"
                                                    >
                                                        <div class="font-medium text-foreground">{{ field.name }}</div>
                                                        <div class="break-all text-muted-foreground">{{ field.value }}</div>
                                                    </div>
                                                </div>
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    <a
                                                        v-if="follower.url"
                                                        :href="follower.url"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="cursor-pointer rounded-full border border-input px-2 py-1 text-[11px] text-primary hover:bg-accent"
                                                    >
                                                        <ExternalLink class="mr-1 inline h-3 w-3" />
                                                        {{ t('mastodon.common.openProfile') }}
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </article>

                                    <div
                                        v-if="ensureAccountDetailsState(account.id).followersHasMore"
                                        class="pt-1"
                                    >
                                        <button
                                            :disabled="ensureAccountDetailsState(account.id).followersLoadingMore"
                                            class="cursor-pointer rounded-md border border-input bg-background px-3 py-2 text-xs font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                                            @click="loadMoreAccountFollowers(account.id)"
                                        >
                                            {{
                                                ensureAccountDetailsState(account.id).followersLoadingMore
                                                    ? t('mastodon.search.loadingMore')
                                                    : t('mastodon.search.loadMore')
                                            }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <section v-if="result && result.hashtags.length > 0" class="space-y-3">
                <div class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    {{ t('mastodon.sections.hashtags') }}
                </div>
                <article
                    v-for="hashtag in result.hashtags"
                    :key="hashtag.name"
                    class="rounded-lg border border-border/80 bg-background/70 p-3"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold">#{{ hashtag.name }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ t('mastodon.metrics.historyPoints') }}: {{ hashtag.history.length }}
                            </div>
                        </div>
                        <a
                            v-if="hashtag.url"
                            :href="hashtag.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="cursor-pointer rounded-full border border-input px-2 py-1 text-xs text-primary hover:bg-accent"
                        >
                            <ExternalLink class="mr-1 inline h-3 w-3" />
                            {{ t('mastodon.common.openTag') }}
                        </a>
                    </div>

                    <div
                        v-if="hashtag.history.length > 0"
                        class="mt-3 grid gap-2 md:grid-cols-2"
                    >
                        <div
                            v-for="point in hashtag.history"
                            :key="`${hashtag.name}-${point.day}`"
                            class="rounded-md border border-border/70 bg-card/60 p-2 text-xs text-muted-foreground"
                        >
                            <div>{{ point.day }}</div>
                            <div>{{ t('mastodon.metrics.uses') }}: {{ point.uses }}</div>
                            <div>{{ t('mastodon.metrics.accounts') }}: {{ point.accounts }}</div>
                        </div>
                    </div>
                </article>
            </section>
        </div>

        <div v-if="result?.pagination.hasMore" class="mt-4 flex justify-center">
            <button
                :disabled="loadingMore"
                class="cursor-pointer rounded-md border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                @click="runSearch(true)"
            >
                {{ loadingMore ? t('mastodon.search.loadingMore') : t('mastodon.search.loadMore') }}
            </button>
        </div>
    </IntelResultPanel>
</template>
