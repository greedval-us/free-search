<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import { useFioLabels } from '../composables/useFioLabels';
import type { FioMatch } from '../types';

const props = defineProps<{
    matches: FioMatch[];
}>();

const { t } = useI18n();
const { ageBucketLabel, regionLabel } = useFioLabels();
</script>

<template>
    <section class="intel-section">
        <p class="mb-2 font-semibold">{{ t('fio.lookup.publicMatches') }}</p>
        <p v-if="props.matches.length === 0" class="text-muted-foreground">
            {{ t('fio.lookup.noMatches') }}
        </p>
        <div v-else class="space-y-2">
            <article
                v-for="(item, idx) in props.matches"
                :key="`${item.url}-${idx}`"
                class="rounded-md border border-border/70 bg-background/70 p-2"
            >
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <p
                            class="font-semibold [overflow-wrap:anywhere] break-words"
                        >
                            {{ item.title }}
                        </p>
                        <a
                            :href="item.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="[overflow-wrap:anywhere] break-words text-primary hover:underline"
                        >
                            {{ item.url }}
                        </a>
                    </div>
                    <span
                        class="rounded-full border border-primary/30 bg-primary/10 px-2 py-0.5 text-[11px]"
                    >
                        {{ t('fio.lookup.confidence') }}: {{ item.confidence }}%
                    </span>
                </div>
                <p
                    class="mt-2 [overflow-wrap:anywhere] break-words text-muted-foreground"
                >
                    {{ item.snippet || '-' }}
                </p>
                <div
                    class="mt-2 flex flex-wrap items-center gap-3 text-muted-foreground"
                >
                    <span
                        >{{ t('fio.lookup.region') }}:
                        {{ regionLabel(item.region) }}</span
                    >
                    <span
                        >{{ t('fio.lookup.age') }}: {{ item.age ?? '-' }}</span
                    >
                    <span
                        >{{ t('fio.lookup.ageBucket') }}:
                        {{ ageBucketLabel(item.ageBucket) }}</span
                    >
                    <span
                        >{{ t('fio.lookup.domain') }}:
                        {{ item.domain ?? '-' }}</span
                    >
                    <span>{{ t('fio.lookup.source') }}: {{ item.source }}</span>
                    <span
                        >{{ t('fio.lookup.reliability') }}:
                        {{ Math.round(item.sourceReliability * 100) }}%</span
                    >
                    <span
                        >{{ t('fio.lookup.qualifierMatched') }}:
                        {{
                            item.qualifierMatched
                                ? t('fio.lookup.yes')
                                : t('fio.lookup.no')
                        }}</span
                    >
                </div>
            </article>
        </div>
    </section>
</template>
