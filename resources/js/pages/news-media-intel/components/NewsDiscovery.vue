<script setup lang="ts">
import { computed } from 'vue';
import InfoCard from '@/components/ui/InfoCard.vue';
import { useI18n } from '@/composables/useI18n';
import { resolveArticleUrl } from '../articleUrl';
import type { NewsInfobox } from '../types';
const props = defineProps<{
    suggestions?: string[];
    corrections?: string[];
    answers?: string[];
    infoboxes?: NewsInfobox[];
}>();
const emit = defineEmits<{ query: [value: string] }>();
const { t } = useI18n();
const boxes = computed(() =>
    (props.infoboxes ?? []).map((box) => ({
        ...box,
        links: (box.urls ?? []).flatMap((item) => {
            const raw = typeof item === 'string' ? item : item.url;
            const href = resolveArticleUrl(raw);

            return href
                ? [
                      {
                          href,
                          title:
                              typeof item === 'string'
                                  ? new URL(href).hostname
                                  : item.title || new URL(href).hostname,
                      },
                  ]
                : [];
        }),
    }))
);
</script>
<template>
    <InfoCard
        v-if="
            suggestions?.length ||
            corrections?.length ||
            answers?.length ||
            boxes.length
        "
        :title="t('newsMediaIntel.discovery.title')"
    >
        <div v-if="corrections?.length" class="mb-3">
            <p class="mb-1 text-xs text-muted-foreground">
                {{ t('newsMediaIntel.discovery.corrections') }}
            </p>
            <button
                v-for="correction in corrections"
                :key="correction"
                type="button"
                class="mr-2 mb-1 rounded-lg border border-border px-2 py-1 text-xs text-primary hover:bg-muted"
                @click="emit('query', correction)"
            >
                {{ correction }}
            </button>
        </div>
        <div v-if="suggestions?.length" class="mb-3">
            <p class="mb-1 text-xs text-muted-foreground">
                {{ t('newsMediaIntel.discovery.suggestions') }}
            </p>
            <button
                v-for="suggestion in suggestions"
                :key="suggestion"
                type="button"
                class="mr-2 mb-1 rounded-lg border border-border px-2 py-1 text-xs text-primary hover:bg-muted"
                @click="emit('query', suggestion)"
            >
                {{ suggestion }}
            </button>
        </div>
        <div v-if="answers?.length" class="mb-3 space-y-2">
            <p class="text-xs text-muted-foreground">
                {{ t('newsMediaIntel.discovery.answers') }}
            </p>
            <p
                v-for="answer in answers"
                :key="answer"
                class="text-sm leading-6 break-words"
            >
                {{ answer }}
            </p>
        </div>
        <article
            v-for="(box, index) in boxes"
            :key="`${box.title}-${index}`"
            class="mt-2 rounded-xl border border-border/70 p-3"
        >
            <p class="text-sm font-medium">{{ box.title }}</p>
            <p class="mt-1 text-xs leading-5 break-words text-muted-foreground">
                {{ box.content }}
            </p>
            <div class="mt-2 flex flex-wrap gap-3 text-xs">
                <a
                    v-for="link in box.links"
                    :key="link.href"
                    :href="link.href"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="break-all text-primary hover:underline"
                    >{{ link.title }}</a
                >
            </div>
        </article>
    </InfoCard>
</template>
