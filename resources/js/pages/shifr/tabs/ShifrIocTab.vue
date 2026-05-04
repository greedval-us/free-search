<script setup lang="ts">
import { computed, ref } from 'vue';
import { LoaderCircle } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';
import ShifrFormCard from '../components/ShifrFormCard.vue';
import ShifrResultCard from '../components/ShifrResultCard.vue';
import { useShifrRequest } from '../composables/useShifrRequest';

const { locale, t } = useI18n();
const input = ref('');
const state = useShifrRequest('/shifr/ioc-extract', () => t('shifr.errors.requestFailed'), computed(() => input.value.trim().length > 0));

const run = async (): Promise<void> => {
  const params = new URLSearchParams({ text: input.value, locale: locale.value });
  await state.run(params);
};
</script>

<template>
  <div class="flex min-h-0 flex-1 flex-col gap-4">
    <ShifrFormCard :title="t('shifr.tabs.ioc')" :help-text="t('shifr.help.input')">
      <label class="block text-xs font-medium text-muted-foreground">{{ t('shifr.input.label') }}</label>
      <textarea v-model="input" rows="6" class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" :placeholder="t('shifr.input.placeholder')" />

      <p class="mt-2 text-xs text-muted-foreground">{{ t('shifr.hints.ioc.extract') }}</p>

      <button :disabled="!state.canRun" class="mt-3 inline-flex h-10 items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:opacity-60" @click="run">
        <LoaderCircle v-if="state.loading" class="h-4 w-4 animate-spin" />{{ state.loading ? t('shifr.actions.running') : t('shifr.actions.run') }}
      </button>
      <p v-if="state.error" class="mt-2 text-sm text-destructive">{{ state.error }}</p>
    </ShifrFormCard>

    <ShifrResultCard :title="t('shifr.result.title')" :help-text="t('shifr.help.result')" :result="state.result" :empty-text="t('shifr.result.empty')" />
  </div>
</template>
