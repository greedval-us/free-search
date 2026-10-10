<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { resolveArticleUrl } from '../articleUrl';
const props = defineProps<{ urls: string[] }>();
const { t } = useI18n();
const links = computed(() =>
    [...new Set(props.urls)].slice(0, 3).flatMap((url) => {
        const href = resolveArticleUrl(url);

        return href ? [{ href, host: new URL(href).hostname }] : [];
    })
);
</script>
<template>
    <div
        v-if="links.length"
        class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs"
    >
        <span class="text-muted-foreground"
            >{{ t('newsMediaIntel.analytics.evidence') }}:</span
        >
        <a
            v-for="(link, index) in links"
            :key="link.href"
            :href="link.href"
            target="_blank"
            rel="noopener noreferrer"
            class="break-all text-primary hover:underline"
            >{{ link.host }} <span class="sr-only">{{ index + 1 }}</span></a
        >
    </div>
</template>
