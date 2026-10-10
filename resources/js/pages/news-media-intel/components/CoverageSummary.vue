<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import type { SearchCoverage } from '../types';
const props = defineProps<{ coverage: SearchCoverage; label?: string }>();
const { t } = useI18n();
const partial = computed(
    () =>
        props.coverage.status === 'unavailable' ||
        props.coverage.truncated ||
        (props.coverage.unresponsiveEngines?.length ?? 0) > 0
);
const stopped = computed(() => {
    const reason = props.coverage.stopReason;

    if (!reason) {
        return '';
    }

    const key = `newsMediaIntel.coverage.reasons.${reason}`;
    const value = t(key);

    return value === key ? t('newsMediaIntel.coverage.reasons.other') : value;
});
</script>

<template>
    <div
        class="rounded-xl border px-3 py-2.5 text-xs leading-5"
        :class="
            partial
                ? 'border-amber-500/30 bg-amber-500/5'
                : 'border-border/70 bg-muted/20'
        "
    >
        <p class="font-medium">
            {{ label }}
            {{
                t(
                    coverage.status === 'unavailable'
                        ? 'newsMediaIntel.coverage.unavailable'
                        : partial
                          ? 'newsMediaIntel.coverage.partial'
                          : 'newsMediaIntel.coverage.loaded'
                )
            }}
        </p>
        <p>
            {{
                t('newsMediaIntel.coverage.pages', {
                    loaded: coverage.pagesLoaded ?? 0,
                    requested: coverage.pagesRequested ?? 0,
                })
            }}
        </p>
        <p v-if="coverage.engines?.length">
            {{ t('newsMediaIntel.coverage.engines') }}:
            {{ coverage.engines.join(', ') }}
        </p>
        <p v-if="stopped">{{ stopped }}</p>
        <p v-if="coverage.unresponsiveEngines?.length">
            {{ t('newsMediaIntel.coverage.unresponsive') }}:
            {{
                coverage.unresponsiveEngines
                    .map((engine) => engine.name)
                    .join(', ')
            }}
        </p>
        <p class="mt-1 text-muted-foreground">
            {{ t('newsMediaIntel.coverage.sampleHelp') }}
        </p>
    </div>
</template>
