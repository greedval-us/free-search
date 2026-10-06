<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import { displayDate, sourceUrl } from './presentation';
import type { Material } from './types';
defineProps<{ material: Material; timezone: string }>();
const { t, locale } = useI18n();
</script>
<template>
    <article
        class="grid min-w-0 gap-3 rounded-xl border border-sidebar-border/70 p-4 sm:p-5"
    >
        <div
            class="flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground"
        >
            <span
                >{{ t(`monitoring.platform.${material.platform}`) }} ·
                {{ material.author ?? '—' }}</span
            ><time>{{
                displayDate(material.published_at, timezone, locale)
            }}</time>
        </div>
        <h2 v-if="material.title" class="font-semibold break-words">
            {{ material.title }}
        </h2>
        <p class="text-sm leading-6 break-words whitespace-pre-wrap">
            {{ material.text }}
        </p>
        <dl class="flex flex-wrap gap-4 text-xs text-muted-foreground">
            <div
                v-for="(value, key) in material.metrics"
                :key="key"
                class="flex gap-1"
            >
                <dt>{{ t(`monitoring.metric.${key}`) }}:</dt>
                <dd>{{ value }}</dd>
            </div>
        </dl>
        <a
            v-if="sourceUrl(material.url)"
            :href="sourceUrl(material.url)"
            target="_blank"
            rel="noopener noreferrer"
            class="justify-self-start text-sm text-primary underline underline-offset-4"
            >{{ t('monitoring.original') }}</a
        >
    </article>
</template>
