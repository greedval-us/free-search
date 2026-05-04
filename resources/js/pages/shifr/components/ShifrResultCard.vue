<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from '@/composables/useI18n';
import ShifrHelpHint from './ShifrHelpHint.vue';

const props = defineProps<{ title: string; helpText: string; result: Record<string, unknown> | null; emptyText: string }>();
const { t } = useI18n();

const mode = ref<'readable' | 'json'>('readable');
const hasResult = computed(() => props.result !== null);
const isPrimitive = (value: unknown): boolean =>
  value === null || ['string', 'number', 'boolean'].includes(typeof value);

const keyLabel = (key: string): string => {
  const translationKey = `shifr.result.fields.${key}`;
  const translated = t(translationKey);
  if (translated !== translationKey) {
    return translated;
  }

  const humanized = key
    .replace(/([a-z])([A-Z])/g, '$1 $2')
    .replace(/_/g, ' ')
    .trim();

  return humanized.charAt(0).toUpperCase() + humanized.slice(1);
};
</script>

<template>
  <section class="flex min-h-0 flex-1 flex-col rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
    <div class="mb-2 flex items-center justify-between gap-2">
      <div class="flex items-center gap-2">
        <p class="text-xs font-medium text-muted-foreground">{{ title }}</p>
        <ShifrHelpHint :text="helpText" />
      </div>
      <div v-if="hasResult" class="inline-flex rounded-md border border-sidebar-border/70 bg-background/40 p-0.5 text-xs">
        <button
          type="button"
          class="rounded px-2 py-1"
          :class="mode === 'readable' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground'"
          @click="mode = 'readable'"
        >
          {{ t('shifr.result.readable') }}
        </button>
        <button
          type="button"
          class="rounded px-2 py-1"
          :class="mode === 'json' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground'"
          @click="mode = 'json'"
        >
          {{ t('shifr.result.json') }}
        </button>
      </div>
    </div>
    <div class="telegram-scroll min-h-0 flex-1 overflow-auto rounded-md border border-sidebar-border/60 bg-background/40 p-3">
      <div v-if="result && mode === 'readable'" class="space-y-2 text-sm">
        <div
          v-for="(value, key) in result"
          :key="String(key)"
          class="rounded-md border border-sidebar-border/60 bg-background/60 p-2"
        >
          <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ keyLabel(String(key)) }}</p>
          <p v-if="isPrimitive(value)" class="mt-1 break-words">{{ value }}</p>
          <ul v-else-if="Array.isArray(value)" class="mt-1 list-disc space-y-1 pl-4">
            <li v-for="(item, index) in value" :key="index" class="break-words">
              {{ typeof item === 'object' ? JSON.stringify(item) : item }}
            </li>
          </ul>
          <pre v-else class="mt-1 whitespace-pre-wrap break-words text-xs leading-5">{{ JSON.stringify(value, null, 2) }}</pre>
        </div>
      </div>
      <pre v-else-if="result" class="text-xs leading-6">{{ JSON.stringify(result, null, 2) }}</pre>
      <p v-else class="text-sm text-muted-foreground">{{ emptyText }}</p>
    </div>
  </section>
</template>
