<script setup lang="ts">
import { computed, ref } from 'vue';
import { LoaderCircle } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';
import ShifrFormCard from '../components/ShifrFormCard.vue';
import ShifrResultCard from '../components/ShifrResultCard.vue';
import { useShifrRequest } from '../composables/useShifrRequest';

const { locale, t } = useI18n();
const input = ref('');
const algorithm = ref<'md5' | 'sha1' | 'sha256' | 'sha512'>('sha256');
const hmacKey = ref('');

const { loading, error, result, canRun, run: runRequest } = useShifrRequest('/shifr/hash', () => t('shifr.errors.requestFailed'), computed(() => input.value.trim().length > 0));

const run = async (): Promise<void> => {
  const params = new URLSearchParams({ text: input.value, algorithm: algorithm.value, locale: locale.value });
  if (hmacKey.value.trim() !== '') params.set('hmac_key', hmacKey.value);
  await runRequest(params);
};
</script>

<template>
  <div class="flex min-h-0 flex-1 flex-col gap-4">
    <ShifrFormCard :title="t('shifr.tabs.hash')" :help-text="t('shifr.help.input')">
      <label class="block text-xs font-medium text-muted-foreground">{{ t('shifr.input.label') }}</label>
      <textarea v-model="input" rows="5" class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" :placeholder="t('shifr.input.placeholder')" />

      <div class="mt-3 grid gap-3 md:grid-cols-2">
        <label>
          <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('shifr.hash.algorithm') }}</span>
          <select v-model="algorithm" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
            <option value="md5">MD5</option><option value="sha1">SHA1</option><option value="sha256">SHA256</option><option value="sha512">SHA512</option>
          </select>
        </label>
        <label>
          <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('shifr.hash.hmacKey') }}</span>
          <input v-model="hmacKey" type="text" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
        </label>
      </div>

      <div class="mt-3 flex items-center gap-3">
        <button :disabled="!canRun" class="inline-flex h-10 items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:opacity-60" @click="run">
          <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />{{ loading ? t('shifr.actions.running') : t('shifr.actions.run') }}
        </button>
      </div>
      <p v-if="error" class="mt-2 text-sm text-destructive">{{ error }}</p>
    </ShifrFormCard>

    <ShifrResultCard :title="t('shifr.result.title')" :help-text="t('shifr.help.result')" :result="result" :empty-text="t('shifr.result.empty')" />
  </div>
</template>
