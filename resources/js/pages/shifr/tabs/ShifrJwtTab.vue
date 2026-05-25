<script setup lang="ts">
import { LoaderCircle } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from '@/composables/useI18n';
import {
    getRepeatQueryParams,
    isRepeatAutorunEnabled,
    readRepeatQueryParam,
} from '@/composables/useRepeatQuery';
import ShifrFormCard from '../components/ShifrFormCard.vue';
import ShifrResultCard from '../components/ShifrResultCard.vue';
import { useShifrRequest } from '../composables/useShifrRequest';

const { locale, t } = useI18n();
const token = ref('');
const secret = ref('');

const {
    loading,
    error,
    result,
    canRun,
    run: runRequest,
} = useShifrRequest(
    '/shifr/jwt-inspect',
    () => t('shifr.errors.requestFailed'),
    computed(() => token.value.trim().length > 0)
);

const run = async (): Promise<void> => {
    const params = new URLSearchParams({
        token: token.value,
        locale: locale.value,
    });

    if (secret.value.trim() !== '') {
        params.set('secret', secret.value);
    }

    await runRequest(params);
};

onMounted(() => {
    const params = getRepeatQueryParams();

    if (!params) {
        return;
    }

    const tab = readRepeatQueryParam(params, ['tab']);

    if (tab !== 'jwt') {
        return;
    }

    const tokenValue = readRepeatQueryParam(params, ['token']);
    const secretValue = readRepeatQueryParam(params, ['secret']);

    if (tokenValue !== '') {
        token.value = tokenValue;
    }

    if (secretValue !== '') {
        secret.value = secretValue;
    }

    if (isRepeatAutorunEnabled(params) && canRun.value) {
        void run();
    }
});
</script>

<template>
    <div class="flex min-h-0 flex-1 flex-col gap-4">
        <ShifrFormCard
            :title="t('shifr.tabs.jwt')"
            :help-text="t('shifr.help.input')"
        >
            <label class="block text-xs font-medium text-muted-foreground">{{
                t('shifr.jwt.token')
            }}</label>
            <textarea
                v-model="token"
                rows="5"
                class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                :placeholder="t('shifr.jwt.tokenPlaceholder')"
            />

            <label class="mt-3 block">
                <span
                    class="mb-1 block text-xs font-medium text-muted-foreground"
                    >{{ t('shifr.jwt.secret') }}</span
                >
                <input
                    v-model="secret"
                    type="text"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    :placeholder="t('shifr.jwt.secretPlaceholder')"
                />
            </label>

            <p class="mt-2 text-xs text-muted-foreground">
                {{ t('shifr.hints.jwt.inspect') }}
            </p>

            <button
                :disabled="!canRun"
                class="mt-3 inline-flex h-10 items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:opacity-60"
                @click="run"
            >
                <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />{{
                    loading
                        ? t('shifr.actions.running')
                        : t('shifr.actions.run')
                }}
            </button>
            <p v-if="error" class="mt-2 text-sm text-destructive">
                {{ error }}
            </p>
        </ShifrFormCard>

        <ShifrResultCard
            :title="t('shifr.result.title')"
            :help-text="t('shifr.help.result')"
            :result="result"
            :empty-text="t('shifr.result.empty')"
        />
    </div>
</template>
