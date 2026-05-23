<script setup lang="ts">
import { LoaderCircle } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { getRepeatQueryParams, isRepeatAutorunEnabled, readRepeatQueryParam } from '@/composables/useRepeatQuery';
import { useI18n } from '@/composables/useI18n';
import ShifrFormCard from '../components/ShifrFormCard.vue';
import ShifrResultCard from '../components/ShifrResultCard.vue';
import { useShifrRequest } from '../composables/useShifrRequest';

const { locale, t } = useI18n();
const input = ref('');
const algorithmOptions = [
  { value: 'md5', label: 'MD5' },
  { value: 'sha1', label: 'SHA1' },
  { value: 'sha224', label: 'SHA224' },
  { value: 'sha256', label: 'SHA256' },
  { value: 'sha384', label: 'SHA384' },
  { value: 'sha512', label: 'SHA512' },
  { value: 'sha512/224', label: 'SHA512/224' },
  { value: 'sha512/256', label: 'SHA512/256' },
  { value: 'sha3-224', label: 'SHA3-224' },
  { value: 'sha3-256', label: 'SHA3-256' },
  { value: 'sha3-384', label: 'SHA3-384' },
  { value: 'sha3-512', label: 'SHA3-512' },
  { value: 'blake2s256', label: 'BLAKE2s-256' },
  { value: 'blake2b512', label: 'BLAKE2b-512' },
  { value: 'ripemd128', label: 'RIPEMD-128' },
  { value: 'ripemd160', label: 'RIPEMD-160' },
  { value: 'ripemd256', label: 'RIPEMD-256' },
  { value: 'ripemd320', label: 'RIPEMD-320' },
  { value: 'whirlpool', label: 'WHIRLPOOL' },
  { value: 'xxh32', label: 'XXH32' },
  { value: 'xxh64', label: 'XXH64' },
  { value: 'xxh3', label: 'XXH3' },
  { value: 'xxh128', label: 'XXH128' },
  { value: 'crc32', label: 'CRC32' },
  { value: 'crc32b', label: 'CRC32B' },
  { value: 'adler32', label: 'ADLER32' },
] as const;
type HashAlgorithm = (typeof algorithmOptions)[number]['value'];

const algorithm = ref<HashAlgorithm>('sha256');
const hmacKey = ref('');
const algorithmMenuOpen = ref(false);
const algorithmMenuRef = ref<HTMLElement | null>(null);

const stateHint = computed(() => {
  if (hmacKey.value.trim() !== '') {
    return t('shifr.hints.hash.hmacMode');
  }

  return t('shifr.hints.hash.hashMode');
});

const { loading, error, result, canRun, run: runRequest } = useShifrRequest('/shifr/hash', () => t('shifr.errors.requestFailed'), computed(() => input.value.trim().length > 0));

const run = async (): Promise<void> => {
  const params = new URLSearchParams({ text: input.value, algorithm: algorithm.value, locale: locale.value });

  if (hmacKey.value.trim() !== '') {
params.set('hmac_key', hmacKey.value);
}

  await runRequest(params);
};

const onOutsideClick = (event: MouseEvent): void => {
  if (!algorithmMenuRef.value) {
return;
}

  if (event.target instanceof Node && !algorithmMenuRef.value.contains(event.target)) {
    algorithmMenuOpen.value = false;
  }
};

const selectAlgorithm = (value: (typeof algorithmOptions)[number]['value']): void => {
  algorithm.value = value;
  algorithmMenuOpen.value = false;
};

onMounted(() => document.addEventListener('click', onOutsideClick));
onBeforeUnmount(() => document.removeEventListener('click', onOutsideClick));

onMounted(() => {
  const params = getRepeatQueryParams();

  if (!params) {
    return;
  }

  const tab = readRepeatQueryParam(params, ['tab']);
  if (tab !== 'hash') {
    return;
  }

  const text = readRepeatQueryParam(params, ['text']);
  const algo = readRepeatQueryParam(params, ['algorithm']);
  const hmac = readRepeatQueryParam(params, ['hmac_key']);

  if (text !== '') input.value = text;
  if (algo !== '' && algorithmOptions.some((item) => item.value === algo)) {
    algorithm.value = algo as HashAlgorithm;
  }
  if (hmac !== '') hmacKey.value = hmac;

  if (isRepeatAutorunEnabled(params) && canRun.value) {
    void run();
  }
});
</script>

<template>
  <div class="flex min-h-0 flex-1 flex-col gap-4">
    <ShifrFormCard :title="t('shifr.tabs.hash')" :help-text="t('shifr.help.input')">
      <label class="block text-xs font-medium text-muted-foreground">{{ t('shifr.input.label') }}</label>
      <textarea v-model="input" rows="5" class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" :placeholder="t('shifr.input.placeholder')" />

      <div class="mt-3 grid gap-3 md:grid-cols-2">
        <label>
          <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('shifr.hash.algorithm') }}</span>
          <div ref="algorithmMenuRef" class="relative z-30 overflow-visible">
            <button type="button" class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 text-sm" @click="algorithmMenuOpen = !algorithmMenuOpen">
              <span>{{ algorithm.toUpperCase() }}</span>
              <span class="text-xs text-muted-foreground">&#9662;</span>
            </button>
            <div v-if="algorithmMenuOpen" class="shifr-hash-algorithm-select absolute z-[120] mt-1 max-h-52 w-full overflow-y-auto rounded-md border border-input bg-background p-1 shadow-2xl">
              <button
                v-for="item in algorithmOptions"
                :key="item.value"
                type="button"
                class="block w-full rounded px-2 py-1.5 text-left text-sm hover:bg-muted"
                :class="{ 'bg-muted': algorithm === item.value }"
                @click="selectAlgorithm(item.value)"
              >
                {{ item.label }}
              </button>
            </div>
          </div>
        </label>
        <label>
          <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('shifr.hash.hmacKey') }}</span>
          <input v-model="hmacKey" type="text" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
        </label>
      </div>

      <p class="mt-2 text-xs text-muted-foreground">{{ stateHint }}</p>

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

<style scoped>
.shifr-hash-algorithm-select {
  scrollbar-width: thin;
  scrollbar-color: hsl(var(--muted-foreground) / 0.35) hsl(var(--muted) / 0.2);
}

.shifr-hash-algorithm-select::-webkit-scrollbar {
  width: 10px;
}

.shifr-hash-algorithm-select::-webkit-scrollbar-track {
  background: hsl(var(--muted) / 0.25);
  border-radius: 9999px;
}

.shifr-hash-algorithm-select::-webkit-scrollbar-thumb {
  background: hsl(var(--muted-foreground) / 0.4);
  border-radius: 9999px;
  border: 2px solid hsl(var(--background));
}

.shifr-hash-algorithm-select::-webkit-scrollbar-thumb:hover {
  background: hsl(var(--muted-foreground) / 0.55);
}
</style>

