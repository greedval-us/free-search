import { computed, reactive, ref } from 'vue';
import type { Ref } from 'vue';
import type { EmailIntelResult } from '../types';

type TranslateFn = (key: string) => string;

export const useEmailIntelLookup = (t: TranslateFn, locale: Ref<'en' | 'ru'>) => {
    const form = reactive({
        email: '',
    });

    const loading = ref(false);
    const error = ref<string | null>(null);
    const result = ref<EmailIntelResult | null>(null);

    const canLookup = computed(() => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim()));

    const lookup = async () => {
        if (!canLookup.value) {
            error.value = t('emailIntel.errors.emailRequired');
            return;
        }

        loading.value = true;
        error.value = null;
        result.value = null;

        try {
            const query = new URLSearchParams({
                email: form.email.trim(),
                locale: locale.value,
            });
            const response = await fetch(`/email-intel/lookup?${query.toString()}`, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
            });
            const payload = await safeJson(response);
            const apiError = resolveApiError(payload);

            if (!response.ok || !payload?.ok) {
                error.value = apiError ?? t('emailIntel.errors.lookupFailed');
                return;
            }

            result.value = payload.data as EmailIntelResult;
        } catch (exception) {
            error.value = exception instanceof Error ? exception.message : t('emailIntel.errors.lookupFailed');
        } finally {
            loading.value = false;
        }
    };

    return {
        form,
        loading,
        error,
        result,
        canLookup,
        lookup,
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
