<script setup lang="ts">
import { computed, ref } from 'vue';
import { LoaderCircle } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';
import ShifrFormCard from '../components/ShifrFormCard.vue';
import ShifrResultCard from '../components/ShifrResultCard.vue';
import { useShifrRequest } from '../composables/useShifrRequest';

const { locale, t } = useI18n();
const input = ref('');
const cipher = ref<'caesar' | 'atbash'>('caesar');
const direction = ref<'encrypt' | 'decrypt'>('encrypt');
const shift = ref(3);

const { loading, error, result, canRun, run: runRequest } = useShifrRequest('/shifr/classic', () => t('shifr.errors.requestFailed'), computed(() => input.value.trim().length > 0));

const run = async (): Promise<void> => {
  const params = new URLSearchParams({
    text: input.value,
    cipher: cipher.value,
    direction: direction.value,
    shift: String(shift.value),
    locale: locale.value,
  });
  await runRequest(params);
};
</script>

<template>
  <div class="flex min-h-0 flex-1 flex-col gap-4">
    <ShifrFormCard :title="t('shifr.tabs.classic')" :help-text="t('shifr.help.input')">
      <label class="block text-xs font-medium text-muted-foreground">{{ t('shifr.input.label') }}</label>
      <textarea v-model="input" rows="5" class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" :placeholder="t('shifr.input.placeholder')" />

      <div class="mt-3 grid gap-3 md:grid-cols-3">
        <label>
          <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('shifr.classic.cipher') }}</span>
          <select v-model="cipher" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"><option value="caesar">Caesar</option><option value="atbash">Atbash</option></select>
        </label>
        <label>
          <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('shifr.classic.direction') }}</span>
          <select v-model="direction" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"><option value="encrypt">{{ t('shifr.classic.encrypt') }}</option><option value="decrypt">{{ t('shifr.classic.decrypt') }}</option></select>
        </label>
        <label>
          <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('shifr.classic.shift') }}</span>
          <input v-model.number="shift" type="number" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" :disabled="cipher==='atbash'" />
        </label>
      </div>

      <button :disabled="!canRun" class="mt-3 inline-flex h-10 items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:opacity-60" @click="run"><LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />{{ loading ? t('shifr.actions.running') : t('shifr.actions.run') }}</button>
      <p v-if="error" class="mt-2 text-sm text-destructive">{{ error }}</p>
    </ShifrFormCard>

    <ShifrResultCard :title="t('shifr.result.title')" :help-text="t('shifr.help.result')" :result="result" :empty-text="t('shifr.result.empty')" />
  </div>
</template>
