<script setup lang="ts">
import { computed } from 'vue';
import InfoCard from '@/components/ui/InfoCard.vue';
import { useI18n } from '@/composables/useI18n';
import { resolveArticleUrl } from '../articleUrl';
import type { NewsMention } from '../types';
const props = defineProps<{ mentions: NewsMention[] }>();
const { t, locale } = useI18n();
const publications = computed(() =>
    props.mentions.slice(0, 100).map((mention) => {
        const href = resolveArticleUrl(mention.link);

        return {
            ...mention,
            href,
            publisherLabel:
                mention.publisher || (href ? new URL(href).hostname : ''),
        };
    })
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
    <InfoCard
        :title="t('newsMediaIntel.mentions.title')"
        :help-label="t('newsMediaIntel.mentions.title')"
        :help-text="t('newsMediaIntel.help.mentions')"
    >
        <p v-if="!publications.length" class="intel-empty">
            {{ t('newsMediaIntel.mentions.none') }}
        </p>
        <div v-else class="divide-y divide-border/60">
            <article
                v-for="(item, index) in publications"
                :key="`${item.link}-${index}`"
                class="min-w-0 py-3 first:pt-0 last:pb-0"
            >
                <h4 class="text-sm leading-5 font-semibold break-words">
                    <a
                        v-if="item.href"
                        :href="item.href"
                        :title="item.link"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="rounded-sm text-primary underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        >{{ item.title || item.publisherLabel }}</a
                    >
                    <template v-else>{{ item.title }}</template>
                </h4>
                <p
                    v-if="item.snippet"
                    class="mt-1 max-w-prose text-sm leading-6 break-words text-muted-foreground"
                >
                    {{ item.snippet }}
                </p>
                <p
                    class="mt-1.5 flex flex-wrap gap-x-3 gap-y-1 text-xs leading-5 text-muted-foreground"
                >
                    <span v-if="item.publisherLabel" class="break-all">{{
                        item.publisherLabel
                    }}</span
                    ><span>{{ dateLabel(item.publishedAt) }}</span>
                    <span
                        v-for="category in item.categories ??
                        (item.category ? [item.category] : [])"
                        :key="category"
                        >{{ t(`newsMediaIntel.filters.${category}`) }}</span
                    >
                    <span>
                        {{ t('newsMediaIntel.sources.searxng')
                        }}<template v-if="item.engines?.length"
                            >: {{ item.engines.join(', ') }}</template
                        >
                    </span>
                </p>
            </article>
        </div>
    </InfoCard>
</template>
