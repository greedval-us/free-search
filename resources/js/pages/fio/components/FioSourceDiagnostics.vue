<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import type { FioLookupResult } from '../types';

const props = defineProps<{
    result: FioLookupResult;
}>();

const { t } = useI18n();
</script>

<template>
    <section class="space-y-3">
        <div class="intel-section">
            <p>
                {{ t('fio.lookup.providers') }}:
                <span class="text-muted-foreground">{{
                    (props.result.source.providers ?? []).join(', ') || '-'
                }}</span>
            </p>
            <p class="mt-1">
                {{ t('fio.lookup.qualifier') }}:
                <span class="text-muted-foreground">{{
                    props.result.target.qualifier || '-'
                }}</span>
            </p>
            <p class="mt-1">
                {{ t('fio.lookup.qualifierTerms') }}:
                <span class="text-muted-foreground">{{
                    (props.result.target.qualifierTerms ?? []).join(', ') || '-'
                }}</span>
            </p>
            <p class="mt-1">
                {{ t('fio.lookup.qualifierMatches') }}:
                <span class="text-muted-foreground">{{
                    props.result.summary.qualifierMatches
                }}</span>
            </p>
        </div>

        <div class="intel-section">
            <p class="mb-2 font-semibold">
                {{ t('fio.lookup.sourceReliability') }}
            </p>
            <div
                v-if="(props.result.source.stats ?? []).length === 0"
                class="text-muted-foreground"
            >
                -
            </div>
            <div v-else class="space-y-2">
                <div
                    v-for="stat in props.result.source.stats"
                    :key="stat.source"
                >
                    <div class="mb-1 flex items-center justify-between gap-3">
                        <span>{{ stat.source }}</span>
                        <span class="text-right text-muted-foreground">
                            {{ t('fio.lookup.reliability') }}:
                            {{ Math.round(stat.reliability * 100) }}%,
                            {{ t('fio.lookup.matches') }}: {{ stat.matches }},
                            {{ t('fio.lookup.average') }}:
                            {{ stat.averageConfidence }}%
                        </span>
                    </div>
                    <div class="h-2 rounded bg-muted">
                        <div
                            class="h-2 rounded bg-primary"
                            :style="{
                                width: `${Math.round(stat.reliability * 100)}%`,
                            }"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="intel-section">
            <p class="mb-2 font-semibold">
                {{ t('fio.lookup.attemptedSources') }}
            </p>
            <div
                v-if="(props.result.source.attemptedSources ?? []).length === 0"
                class="text-muted-foreground"
            >
                -
            </div>
            <div v-else class="space-y-1">
                <p
                    v-for="attempt in props.result.source.attemptedSources"
                    :key="`attempt-${attempt.source}`"
                    class="text-muted-foreground"
                >
                    {{ attempt.source }}:
                    {{
                        attempt.ok
                            ? t('fio.lookup.ok')
                            : t('fio.lookup.failed')
                    }}, {{ t('fio.lookup.matches') }} {{ attempt.count }},
                    {{ t('fio.lookup.durationMs') }} {{ attempt.durationMs }}
                </p>
            </div>
        </div>

        <div class="intel-section">
            <p class="mb-2 font-semibold">
                {{ t('fio.lookup.sourceErrors') }}
            </p>
            <div
                v-if="(props.result.source.sourceErrors ?? []).length === 0"
                class="text-emerald-500 dark:text-emerald-300"
            >
                {{ t('fio.lookup.noErrors') }}
            </div>
            <div v-else class="space-y-1">
                <p
                    v-for="(sourceError, idx) in props.result.source
                        .sourceErrors"
                    :key="`src-error-${sourceError.source}-${idx}`"
                    class="text-destructive"
                >
                    {{ sourceError.source }}: {{ sourceError.message }} ({{
                        t('fio.lookup.durationMs')
                    }}
                    {{ sourceError.durationMs }})
                </p>
            </div>
        </div>
    </section>
</template>
