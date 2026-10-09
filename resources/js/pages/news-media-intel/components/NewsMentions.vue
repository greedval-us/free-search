<script setup lang="ts">
import { computed } from 'vue';
import InfoCard from '@/components/ui/InfoCard.vue';
import { useI18n } from '@/composables/useI18n';
import { resolveArticleUrl } from '../articleUrl';
import type { NewsMention } from '../types';
const props = defineProps<{ mentions: NewsMention[] }>();
const { t, locale } = useI18n();
const cards = computed(() =>
    props.mentions.slice(0, 100).map((mention) => ({
        ...mention,
        href: resolveArticleUrl(mention.link),
    }))
);
const dateLabel = (raw: string | null) => {
    const date = raw ? new Date(raw) : null;

    return !date || Number.isNaN(date.getTime())
        ? t('newsMediaIntel.mentions.dateUnknown')
        : new Intl.DateTimeFormat(locale.value, {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          }).format(date);
};
</script>
<template>
    <InfoCard :title="t('newsMediaIntel.mentions.title')">
        <p class="mb-3 text-xs leading-5 text-muted-foreground">
            {{ t('newsMediaIntel.help.mentions') }}
        </p>
        <p v-if="!cards.length" class="intel-empty">
            {{ t('newsMediaIntel.mentions.none') }}
        </p>
        <div v-else class="space-y-2">
            <article
                v-for="(item, index) in cards"
                :key="`${item.link}-${index}`"
                class="intel-surface"
            >
                <p class="text-sm font-medium break-words">{{ item.title }}</p>
                <p
                    class="mt-1 text-xs leading-5 break-words text-muted-foreground"
                >
                    {{ item.snippet }}
                </p>
                <p
                    class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted-foreground"
                >
                    <span v-if="item.publisher">{{ item.publisher }}</span
                    ><span>{{ dateLabel(item.publishedAt) }}</span>
                    <span
                        v-for="category in item.categories ??
                        (item.category ? [item.category] : [])"
                        :key="category"
                        >{{ t(`newsMediaIntel.filters.${category}`) }}</span
                    >
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ t('newsMediaIntel.sources.searxng')
                    }}<template v-if="item.engines?.length">
                        · {{ item.engines.join(', ') }}</template
                    >
                </p>
                <a
                    v-if="item.href"
                    :href="item.href"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-1 block text-xs break-all text-primary hover:underline"
                    >{{ item.link }}</a
                >
            </article>
        </div>
    </InfoCard>
</template>
