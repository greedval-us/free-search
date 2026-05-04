<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { LoaderCircle } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';
import ShifrFormCard from '../components/ShifrFormCard.vue';
import ShifrResultCard from '../components/ShifrResultCard.vue';
import { useShifrRequest } from '../composables/useShifrRequest';

const { locale, t } = useI18n();
const input = ref('');
type CipherType = 'caesar' | 'atbash' | 'rot13' | 'rot47' | 'rot5' | 'vigenere' | 'rail_fence' | 'xor' | 'affine' | 'playfair' | 'columnar' | 'morse';
const cipher = ref<CipherType>('caesar');
const direction = ref<'encrypt' | 'decrypt' | 'transform'>('encrypt');
const shift = ref(3);
const key = ref('');
const rails = ref(3);
const xorKey = ref('');
const affineA = ref(5);
const affineB = ref(8);
const playfairKey = ref('');
const columnKey = ref('');
const morseSeparator = ref('/');

const transformCiphers: CipherType[] = ['rot13', 'rot47', 'rot5'];
const hintByCipher: Record<CipherType, string> = {
  caesar: 'shifr.hints.classic.caesar',
  atbash: 'shifr.hints.classic.atbash',
  rot13: 'shifr.hints.classic.rot13',
  rot47: 'shifr.hints.classic.rot47',
  rot5: 'shifr.hints.classic.rot5',
  vigenere: 'shifr.hints.classic.vigenere',
  rail_fence: 'shifr.hints.classic.railFence',
  xor: 'shifr.hints.classic.xor',
  affine: 'shifr.hints.classic.affine',
  playfair: 'shifr.hints.classic.playfair',
  columnar: 'shifr.hints.classic.columnar',
  morse: 'shifr.hints.classic.morse',
};

const classicHintKey = computed(() => hintByCipher[cipher.value]);

const isTransformCipher = computed(() => transformCiphers.includes(cipher.value));
const showShift = computed(() => cipher.value === 'caesar');
const showKey = computed(() => cipher.value === 'vigenere');
const showRails = computed(() => cipher.value === 'rail_fence');
const showXorKey = computed(() => cipher.value === 'xor');
const showAffine = computed(() => cipher.value === 'affine');
const showPlayfairKey = computed(() => cipher.value === 'playfair');
const showColumnKey = computed(() => cipher.value === 'columnar');
const showMorseSeparator = computed(() => cipher.value === 'morse');

const { loading, error, result, canRun, run: runRequest } = useShifrRequest('/shifr/classic', () => t('shifr.errors.requestFailed'), computed(() => input.value.trim().length > 0));

const run = async (): Promise<void> => {
  const params = new URLSearchParams({
    text: input.value,
    cipher: cipher.value,
    direction: direction.value,
    shift: String(shift.value),
    key: key.value,
    rails: String(rails.value),
    xor_key: xorKey.value,
    affine_a: String(affineA.value),
    affine_b: String(affineB.value),
    playfair_key: playfairKey.value,
    column_key: columnKey.value,
    morse_separator: morseSeparator.value,
    locale: locale.value,
  });
  await runRequest(params);
};

const resetSettings = (): void => {
  shift.value = 3;
  key.value = '';
  rails.value = 3;
  xorKey.value = '';
  affineA.value = 5;
  affineB.value = 8;
  playfairKey.value = '';
  columnKey.value = '';
  morseSeparator.value = '/';
};

watch(cipher, (nextCipher) => {
  resetSettings();

  if (transformCiphers.includes(nextCipher)) {
    direction.value = 'transform';
    return;
  }

  if (direction.value === 'transform') {
    direction.value = 'encrypt';
  }
});
</script>

<template>
  <div class="flex min-h-0 flex-1 flex-col gap-4">
    <ShifrFormCard :title="t('shifr.tabs.classic')" :help-text="t('shifr.help.input')">
      <label class="block text-xs font-medium text-muted-foreground">{{ t('shifr.input.label') }}</label>
      <textarea v-model="input" rows="5" class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" :placeholder="t('shifr.input.placeholder')" />

      <div class="mt-3 grid gap-3 md:grid-cols-3">
        <label>
          <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('shifr.classic.cipher') }}</span>
          <select v-model="cipher" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"><option value="caesar">{{ t('shifr.classic.caesar') }}</option><option value="atbash">{{ t('shifr.classic.atbash') }}</option><option value="rot13">{{ t('shifr.classic.rot13') }}</option><option value="rot47">{{ t('shifr.classic.rot47') }}</option><option value="rot5">{{ t('shifr.classic.rot5') }}</option><option value="vigenere">{{ t('shifr.classic.vigenere') }}</option><option value="rail_fence">{{ t('shifr.classic.railFence') }}</option><option value="xor">{{ t('shifr.classic.xor') }}</option><option value="affine">{{ t('shifr.classic.affine') }}</option><option value="playfair">{{ t('shifr.classic.playfair') }}</option><option value="columnar">{{ t('shifr.classic.columnar') }}</option><option value="morse">{{ t('shifr.classic.morse') }}</option></select>
        </label>
        <label>
          <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('shifr.classic.direction') }}</span>
          <select v-model="direction" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
            <option v-if="!isTransformCipher" value="encrypt">{{ t('shifr.classic.encrypt') }}</option>
            <option v-if="!isTransformCipher" value="decrypt">{{ t('shifr.classic.decrypt') }}</option>
            <option v-if="isTransformCipher" value="transform">{{ t('shifr.classic.transform') }}</option>
          </select>
        </label>
        <label v-if="showShift">
          <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('shifr.classic.shift') }}</span>
          <input v-model.number="shift" type="number" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
        </label>
      </div>

      <div class="mt-3 grid gap-3 md:grid-cols-2">
        <label v-if="showKey">
          <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('shifr.classic.key') }}</span>
          <input v-model="key" type="text" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
        </label>
        <label v-if="showRails">
          <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('shifr.classic.rails') }}</span>
          <input v-model.number="rails" type="number" min="2" max="20" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
        </label>
        <label v-if="showXorKey">
          <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('shifr.classic.xorKey') }}</span>
          <input v-model="xorKey" type="text" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
        </label>
        <label v-if="showAffine">
          <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('shifr.classic.affineA') }}</span>
          <input v-model.number="affineA" type="number" min="1" max="1000" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
        </label>
        <label v-if="showAffine">
          <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('shifr.classic.affineB') }}</span>
          <input v-model.number="affineB" type="number" min="-1000" max="1000" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
        </label>
        <label v-if="showPlayfairKey">
          <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('shifr.classic.playfairKey') }}</span>
          <input v-model="playfairKey" type="text" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
        </label>
        <label v-if="showColumnKey">
          <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('shifr.classic.columnKey') }}</span>
          <input v-model="columnKey" type="text" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
        </label>
        <label v-if="showMorseSeparator">
          <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('shifr.classic.morseSeparator') }}</span>
          <input v-model="morseSeparator" type="text" maxlength="5" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
        </label>
      </div>

      <p class="mt-2 text-xs text-muted-foreground">{{ t(classicHintKey) }}</p>

      <button :disabled="!canRun" class="mt-3 inline-flex h-10 items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:opacity-60" @click="run"><LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />{{ loading ? t('shifr.actions.running') : t('shifr.actions.run') }}</button>
      <p v-if="error" class="mt-2 text-sm text-destructive">{{ error }}</p>
    </ShifrFormCard>

    <ShifrResultCard :title="t('shifr.result.title')" :help-text="t('shifr.help.result')" :result="result" :empty-text="t('shifr.result.empty')" />
  </div>
</template>
