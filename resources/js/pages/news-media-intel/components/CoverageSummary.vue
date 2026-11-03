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
        class="min-w-0 rounded-lg border px-3 py-2 text-xs leading-5"
        :class="
            partial
                ? 'border-amber-500/30 bg-amber-500/5'
                : 'border-border/70 bg-muted/20'
        "
    >
        <div
            class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1"
        >
            <p class="text-sm font-medium">
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
        </div>
        <p v-if="stopped" class="mt-1">{{ stopped }}</p>
        <p v-if="coverage.unresponsiveEngines?.length" class="break-words">
            {{ t('newsMediaIntel.coverage.unresponsive') }}:
            {{
                coverage.unresponsiveEngines
                    .map((engine) => engine.name)
                    .join(', ')
            }}
        </p>
        <details class="mt-1 text-muted-foreground">
            <summary
                class="w-fit cursor-pointer rounded-sm hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
            >
                {{ t('newsMediaIntel.coverage.engines')
                }}<template v-if="coverage.engines?.length"
                    >: {{ coverage.engines.length }}</template
                >
            </summary>
            <p v-if="coverage.engines?.length" class="mt-1 break-words">
                {{ coverage.engines.join(', ') }}
            </p>
            <p class="mt-1">{{ t('newsMediaIntel.coverage.sampleHelp') }}</p>
        </details>
    </div>
</template>
