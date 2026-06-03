import { computed, ref } from 'vue';
import { apiRequest } from '@/lib/api';
import type { BlueskySearchForm, BlueskySearchPayload } from '../types';

type TranslateFn = (key: string) => string;

const LIMIT_MIN = 1;
const LIMIT_MAX = 25;
const DEFAULT_LIMIT = 10;

const createSearchForm = (): BlueskySearchForm => ({
    q: '',
    type: 'posts',
    limit: DEFAULT_LIMIT,
    cursor: '',
    sort: 'latest',
    language: '',
    author: '',
    mentions: '',
    domain: '',
    url: '',
    tag: '',
    since: '',
    until: '',
});

export const useBlueskySearch = (t: TranslateFn) => {
    const form = ref<BlueskySearchForm>(createSearchForm());
    const loading = ref(false);
    const loadingMore = ref(false);
    const error = ref<string | null>(null);
    const result = ref<BlueskySearchPayload | null>(null);
    const showAdvanced = ref(false);
    const searchPanelCollapsed = ref(false);

    const canSearch = computed(() => form.value.q.trim().length > 0);
    const totalShown = computed(
        () => (result.value?.posts.length ?? 0) + (result.value?.actors.length ?? 0)
    );
    const hasMore = computed(() => {
        if (!result.value) {
            return false;
        }

        if (form.value.type === 'posts') {
            return result.value.pagination.posts.hasMore;
        }

        if (form.value.type === 'actors') {
            return result.value.pagination.actors.hasMore;
        }

        return (
            result.value.pagination.posts.hasMore ||
            result.value.pagination.actors.hasMore
        );
    });

    const clampLimit = () => {
        const value = Number(form.value.limit);
        form.value.limit = Number.isFinite(value)
            ? Math.min(LIMIT_MAX, Math.max(LIMIT_MIN, Math.trunc(value)))
            : DEFAULT_LIMIT;
    };

    const nextCursor = () => {
        if (!result.value) {
            return '';
        }

        if (form.value.type === 'posts') {
            return result.value.pagination.posts.nextCursor ?? '';
        }

        if (form.value.type === 'actors') {
            return result.value.pagination.actors.nextCursor ?? '';
        }

        return (
            result.value.pagination.posts.nextCursor ??
            result.value.pagination.actors.nextCursor ??
            ''
        );
    };

    const runSearch = async (append = false) => {
        if (append) {
            loadingMore.value = true;
        } else {
            loading.value = true;
            error.value = null;
            form.value.cursor = '';
        }

        const response = await apiRequest<BlueskySearchPayload>(
            '/bluesky/search',
            {
                query: {
                    q: form.value.q,
                    type: form.value.type,
                    limit: form.value.limit,
                    cursor: append ? nextCursor() || undefined : undefined,
                    sort: form.value.sort,
                    language: form.value.language || undefined,
                    author: form.value.author || undefined,
                    mentions: form.value.mentions || undefined,
                    domain: form.value.domain || undefined,
                    url: form.value.url || undefined,
                    tag: form.value.tag || undefined,
                    since: form.value.since || undefined,
                    until: form.value.until || undefined,
                },
            }
        );

        loading.value = false;
        loadingMore.value = false;

        if (!response.ok) {
            error.value = response.message ?? t('bluesky.errors.requestFailed');

            return;
        }

        if (append && result.value) {
            result.value = {
                ...response.data,
                posts: [...result.value.posts, ...response.data.posts],
                actors: [...result.value.actors, ...response.data.actors],
            };

            return;
        }

        result.value = response.data;
    };

    const formatDate = (value: string) =>
        value ? new Date(value).toLocaleString() : '-';

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
        totalShown,
        hasMore,
        clampLimit,
        runSearch,
        formatDate,
    };
};
