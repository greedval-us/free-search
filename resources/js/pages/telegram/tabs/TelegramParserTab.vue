<script setup lang="ts">
import { Construction } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();
</script>

<template>
    <section class="rounded-xl border border-sidebar-border/80 bg-card/75 p-6 shadow-xl backdrop-blur">
        <div class="mx-auto flex max-w-2xl flex-col items-center text-center">
            <div class="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-full border border-border/80 bg-background/80">
                <Construction class="h-7 w-7 text-cyan-400" />
            </div>
            <h2 class="text-lg font-semibold">{{ t('telegram.parser.title') }}</h2>
            <p class="mt-2 text-sm text-muted-foreground">
                {{ t('telegram.parser.description') }}
            </p>
            <span class="mt-4 rounded-full border border-amber-500/40 bg-amber-500/10 px-3 py-1 text-xs text-amber-300">
                {{ t('telegram.parser.status') }}
            </span>
        </div>
    </section>
</template>
