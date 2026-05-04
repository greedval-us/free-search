<script setup lang="ts">
import { computed, ref } from 'vue';
import { LoaderCircle } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';
import ShifrFormCard from '../components/ShifrFormCard.vue';
import ShifrResultCard from '../components/ShifrResultCard.vue';
import { useShifrRequest } from '../composables/useShifrRequest';

const { locale, t } = useI18n();
const input = ref('');
const operation = ref<'base64_encode' | 'base64_decode' | 'hex_encode' | 'hex_decode' | 'url_encode' | 'url_decode'>('base64_encode');

const { loading, error, result, canRun, run: runRequest } = useShifrRequest('/shifr/transform', () => t('shifr.errors.requestFailed'), computed(() => input.value.trim().length > 0));

const run = async (): Promise<void> => {
  const params = new URLSearchParams({ text: input.value, operation: operation.value, locale: locale.value });
  await runRequest(params);
};
</script>

<template>
  <div class="flex min-h-0 flex-1 flex-col gap-4">
    <ShifrFormCard :title="t('shifr.tabs.transform')" :help-text="t('shifr.help.input')">
      <label class="block text-xs font-medium text-muted-foreground">{{ t('shifr.input.label') }}</label>
      <textarea v-model="input" rows="5" class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" :placeholder="t('shifr.input.placeholder')" />
      <label class="mt-3 block">
        <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('shifr.transform.operation') }}</span>
        <select v-model="operation" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
          <option value="base64_encode">{{ t('shifr.transform.base64Encode') }}</option>
          <option value="base64_decode">{{ t('shifr.transform.base64Decode') }}</option>
          <option value="hex_encode">{{ t('shifr.transform.hexEncode') }}</option>
          <option value="hex_decode">{{ t('shifr.transform.hexDecode') }}</option>
          <option value="url_encode">{{ t('shifr.transform.urlEncode') }}</option>
          <option value="url_decode">{{ t('shifr.transform.urlDecode') }}</option>
        </select>
      </label>

      <button :disabled="!canRun" class="mt-3 inline-flex h-10 items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:opacity-60" @click="run">
        <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />{{ loading ? t('shifr.actions.running') : t('shifr.actions.run') }}
      </button>
      <p v-if="error" class="mt-2 text-sm text-destructive">{{ error }}</p>
    </ShifrFormCard>

    <ShifrResultCard :title="t('shifr.result.title')" :help-text="t('shifr.help.result')" :result="result" :empty-text="t('shifr.result.empty')" />
  </div>
</template>
