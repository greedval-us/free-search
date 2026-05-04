<script setup lang="ts">
import ShifrHelpHint from './ShifrHelpHint.vue';

defineProps<{ title: string; helpText: string }>();
</script>

<template>
  <section class="relative z-20 shrink-0 overflow-visible rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
    <div class="mb-3 flex items-center gap-2">
      <p class="text-sm font-semibold">{{ title }}</p>
      <ShifrHelpHint :text="helpText" />
    </div>

    <slot />
  </section>
</template>
