<script setup lang="ts">
import { ChevronDown, ChevronUp, ExternalLink, Search, Settings } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import IntelAdvancedFilters from '@/components/ui/IntelAdvancedFilters.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue';
import { useI18n } from '@/composables/useI18n';
import { apiRequest } from '@/lib/api';
import type { MastodonSearchPayload } from '../types';

const { t } = useI18n();

const LIMIT_MIN = 1;
const LIMIT_MAX = 20;

const form = ref({
    q: '',
    type: 'statuses',
    limit: 10,
    resolve: false,
});

const loading = ref(false);
const loadingMore = ref(false);
const error = ref<string | null>(null);
const result = ref<MastodonSearchPayload | null>(null);
const showAdvanced = ref(false);
const searchPanelCollapsed = ref(false);

const canSearch = computed(() => form.value.q.trim().length > 0);
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
            class="mt-3 flex flex-wrap items-end gap-3"
        >
            <div class="grid min-w-0 flex-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
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
            </div>

            <div class="flex w-full flex-wrap items-end gap-2 lg:w-auto">
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
            content-class="md:grid-cols-2"
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

            <label class="flex items-center gap-3 rounded-md border border-input bg-background px-3 py-2 text-sm">
                <input v-model="form.resolve" type="checkbox" class="h-4 w-4" />
                <span>{{ t('mastodon.search.resolve') }}</span>
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
                                @{{ status.account.acct }} | {{ formatDate(status.createdAt) }}
                            </div>
                        </div>
                    </div>

                    <p class="text-sm leading-relaxed text-foreground">
                        {{ status.content || t('mastodon.search.noText') }}
                    </p>

                    <div class="mt-3 flex flex-wrap gap-3 text-xs text-muted-foreground">
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
                                @{{ account.acct }}
                            </div>
                            <p class="mt-2 text-sm leading-relaxed text-foreground">
                                {{ account.note || t('mastodon.search.noBio') }}
                            </p>
                            <div class="mt-3 flex flex-wrap gap-3 text-xs text-muted-foreground">
                                <span>{{ t('mastodon.metrics.followers') }}: {{ account.followersCount }}</span>
                                <span>{{ t('mastodon.metrics.following') }}: {{ account.followingCount }}</span>
                                <span>{{ t('mastodon.metrics.posts') }}: {{ account.statusesCount }}</span>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
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
