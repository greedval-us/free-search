import { computed, onMounted, reactive, ref } from 'vue';
import type { Translate } from '@/composables/useI18n';
import {
    getRepeatQueryParams,
    isRepeatAutorunEnabled,
    readRepeatQueryInt,
    readRepeatQueryParam,
} from '@/composables/useRepeatQuery';
import { apiRequest, resolveApiErrorMessage } from '@/lib/api';
import { videos } from '@/routes/youtube/search';
import type { YouTubeSearchPayload, YouTubeVideo } from '../types';

export const SEARCH_LIMIT = { min: 1, max: 10 };
export const SEARCH_TYPES = ['video', 'channel', 'playlist'] as const;

const repeatTextFields = [
    'q',
    'channelId',
    'order',
    'publishedAfter',
    'publishedBefore',
    'regionCode',
    'relevanceLanguage',
    'safeSearch',
    'videoDuration',
    'videoDefinition',
    'videoCaption',
] as const;

export function useYouTubeSearch(t: Translate, resetComments: () => void) {
    const form = reactive({
        q: '',
        type: 'video' as YouTubeVideo['type'],
        channelId: '',
        order: 'relevance',
        publishedAfter: '',
        publishedBefore: '',
        regionCode: '',
        relevanceLanguage: '',
        safeSearch: 'moderate',
        videoDuration: 'any',
        videoDefinition: 'any',
        videoCaption: 'any',
        limit: SEARCH_LIMIT.max,
    });
    const loading = ref(false);
    const loadingMore = ref(false);
    const error = ref<string | null>(null);
    const result = ref<YouTubeSearchPayload | null>(null);
    const canSearch = computed(() => form.q.trim().length > 0);
    let searchVersion = 0;

    function clampLimit() {
        const value = Number(form.limit);
        form.limit = Number.isFinite(value)
            ? Math.min(
                  SEARCH_LIMIT.max,
                  Math.max(SEARCH_LIMIT.min, Math.trunc(value))
              )
            : SEARCH_LIMIT.max;
    }

    async function runSearch(append = false) {
        if (
            append &&
            (loading.value ||
                loadingMore.value ||
                !result.value?.pagination.nextPageToken)
        ) {
            return;
        }

        if (append) {
            loadingMore.value = true;
        } else {
            searchVersion += 1;
            loading.value = true;
            loadingMore.value = false;
            error.value = null;
        }

        const version = searchVersion;
        clampLimit();
        const response = await apiRequest<YouTubeSearchPayload>(videos.url(), {
            query: {
                ...form,
                pageToken: append ? result.value?.pagination.nextPageToken : '',
            },
        });

        if (version !== searchVersion) {
            return;
        }

        loading.value = false;
        loadingMore.value = false;

        if (!response.ok) {
            error.value = resolveApiErrorMessage(
                response.message,
                t('youtube.common.requestFailed')
            );

            return;
        }

        if (append && result.value) {
            result.value = {
                ...response.data,
                items: [...result.value.items, ...response.data.items],
            };
        } else {
            result.value = response.data;
            resetComments();
        }
    }

    onMounted(() => {
        const params = getRepeatQueryParams();

        if (!params) {
            return;
        }

        const tab = readRepeatQueryParam(params, ['tab']);

        if (tab && tab !== 'search') {
            return;
        }

        for (const field of repeatTextFields) {
            const value = readRepeatQueryParam(params, [field]);

            if (value !== '') {
                form[field] = value;
            }
        }

        const type = readRepeatQueryParam(params, ['type']);
        form.type = SEARCH_TYPES.find((value) => value === type) ?? form.type;
        const limit = readRepeatQueryInt(params, 'limit');

        if (limit !== null) {
            form.limit = limit;
            clampLimit();
        }

        if (isRepeatAutorunEnabled(params) && canSearch.value) {
            void runSearch();
        }
    });

    return {
        form,
        canSearch,
        result,
        loading,
        loadingMore,
        error,
        clampLimit,
        runSearch,
    };
}
