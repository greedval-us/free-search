import { computed, reactive, ref } from 'vue';
import type { SiteHealthResult } from '../types';

type TranslateFn = (key: string) => string;

export const useSiteHealth = (t: TranslateFn) => {
    const form = reactive({
        target: '',
    });

    const loading = ref(false);
    const error = ref<string | null>(null);
    const result = ref<SiteHealthResult | null>(null);

    const canCheck = computed(() => form.target.trim().length >= 3);

    const check = async () => {
        if (!canCheck.value) {
            error.value = t('siteIntel.siteHealth.errors.targetRequired');
            return;
        }

        loading.value = true;
        error.value = null;
        result.value = null;

        try {
            const query = new URLSearchParams({
                target: form.target.trim(),
            });
            const response = await fetch(`/site-intel/site-health?${query.toString()}`, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
            });

            const payload = await safeJson(response);
            const apiError = resolveApiError(payload);

            if (!response.ok || !payload?.ok) {
                error.value = apiError ?? t('siteIntel.siteHealth.errors.checkFailed');
                return;
            }

            result.value = payload.data as SiteHealthResult;
        } catch (exception) {
            error.value = exception instanceof Error ? exception.message : t('siteIntel.siteHealth.errors.checkFailed');
        } finally {
            loading.value = false;
        }
    };

    return {
        form,
        loading,
        error,
        result,
        canCheck,
        check,
    };
};

const safeJson = async (response: Response): Promise<Record<string, unknown> | null> => {
    const contentType = response.headers.get('content-type') ?? '';
    if (!contentType.includes('application/json')) {
        return null;
    }

    try {
        return (await response.json()) as Record<string, unknown>;
    } catch {
        return null;
    }
};

const resolveApiError = (payload: Record<string, unknown> | null): string | null => {
    if (!payload || typeof payload !== 'object') {
        return null;
    }

    const message = payload.message;
    if (typeof message === 'string' && message.trim() !== '') {
        return message;
    }

    const errors = payload.errors;
    if (errors && typeof errors === 'object') {
        for (const value of Object.values(errors as Record<string, unknown>)) {
            if (Array.isArray(value) && value.length > 0 && typeof value[0] === 'string') {
                return value[0];
            }
        }
    }

    return null;
};

