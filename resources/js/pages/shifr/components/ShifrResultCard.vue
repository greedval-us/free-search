<script setup lang="ts">
import ShifrHelpHint from './ShifrHelpHint.vue';

defineProps<{ title: string; helpText: string; result: Record<string, unknown> | null; emptyText: string }>();
</script>

<template>
  <section class="flex min-h-0 flex-1 flex-col rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
    <div class="mb-2 flex items-center gap-2">
      <p class="text-xs font-medium text-muted-foreground">{{ title }}</p>
      <ShifrHelpHint :text="helpText" />
    </div>
    <div class="telegram-scroll min-h-0 flex-1 overflow-auto rounded-md border border-sidebar-border/60 bg-background/40 p-3">
      <pre v-if="result" class="text-xs leading-6">{{ JSON.stringify(result, null, 2) }}</pre>
      <p v-else class="text-sm text-muted-foreground">{{ emptyText }}</p>
    </div>
  </section>
</template>
