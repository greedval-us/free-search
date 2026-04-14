import { computed, reactive, ref } from 'vue';
import type { UsernameSearchResponse } from '../types';

type TranslateFn = (key: string) => string;

export const useUsernameSearch = (t: TranslateFn) => {
    const form = reactive({
        username: '',
    });

    const loading = ref(false);
    const error = ref<string | null>(null);
    const items = ref<UsernameSearchResponse['items']>([]);
    const checkedAt = ref<string | null>(null);
    const summary = ref<UsernameSearchResponse['summary'] | null>(null);

    const canSearch = computed(() => form.username.trim().length >= 2);

    const normalizedUsername = computed(() => form.username.trim().replace(/^@+/, ''));

    const search = async () => {
        if (!canSearch.value) {
            error.value = t('username.errors.usernameRequired');

            return;
        }

        loading.value = true;
        error.value = null;
        items.value = [];
        checkedAt.value = null;
        summary.value = null;

        try {
            const endpoint = `/username/search?username=${encodeURIComponent(normalizedUsername.value)}`;
            const response = await fetch(endpoint, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
            });

            const payload: UsernameSearchResponse = await response.json();

            if (!response.ok || !payload.ok) {
                error.value = t('username.errors.searchFailed');

                return;
            }

            items.value = payload.items;
            checkedAt.value = payload.checkedAt;
            summary.value = payload.summary;
        } catch (exception) {
            error.value = exception instanceof Error ? exception.message : t('username.errors.searchFailed');
        } finally {
            loading.value = false;
        }
    };

    return {
        form,
        loading,
        error,
        items,
        checkedAt,
        summary,
        canSearch,
        normalizedUsername,
        search,
    };
};
