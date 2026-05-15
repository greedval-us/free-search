<script setup lang="ts">
import ShifrHelpHint from './ShifrHelpHint.vue';

defineProps<{ title: string; helpText: string }>();
</script>

<template>
  <section class="relative z-20 shrink-0 overflow-visible intel-panel">
    <div class="mb-3 flex items-center gap-2">
      <p class="text-sm font-semibold">{{ title }}</p>
      <ShifrHelpHint :text="helpText" />
    </div>

    <slot />
  </section>
</template>

