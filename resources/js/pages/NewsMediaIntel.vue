<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Newspaper } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import IntelSearchForm from '@/components/ui/IntelSearchForm.vue';
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue';
import MetricCard from '@/components/ui/MetricCard.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import SectionCard from '@/components/ui/SectionCard.vue';
import { useI18n } from '@/composables/useI18n';
import { apiRequest } from '@/lib/api';
import type { NewsResult } from './news-media-intel/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'News & Media Intel', href: '/news-media-intel' }],
    },
});

const { t, locale } = useI18n();
const pageTitle = computed(() => t('newsMediaIntel.headTitle'));
const query = ref('');
const loading = ref(false);
const error = ref<string | null>(null);
const result = ref<NewsResult | null>(null);
const canSearch = computed(() => query.value.trim().length >= 2);

const lookup = async () => {
    if (!canSearch.value) {
        error.value = t('newsMediaIntel.errors.queryRequired');
        return;
    }

    loading.value = true;
    error.value = null;
    result.value = null;

    const res = await apiRequest<NewsResult>('/news-media-intel/lookup', {
        method: 'GET',
        query: { query: query.value.trim(), locale: locale.value },
    });

    if (!res.ok) {
        error.value = res.message ?? t('newsMediaIntel.errors.lookupFailed');
        loading.value = false;
        return;
    }

    result.value = res.data;
    loading.value = false;
};
</script>

<template>
    <Head :title="pageTitle" />

    <div class="flex h-full min-h-0 flex-1 flex-col gap-4 overflow-hidden rounded-xl p-4">
        <IntelSearchPanel>
            <PageHeader :icon="Newspaper" :title="t('newsMediaIntel.title')" :description="t('newsMediaIntel.description')" />

            <IntelSearchForm
                v-model="query"
                :label="t('newsMediaIntel.form.query')"
                :placeholder="t('newsMediaIntel.form.placeholder')"
                :button-text="t('newsMediaIntel.form.search')"
                :loading-text="t('newsMediaIntel.form.searching')"
                :loading="loading"
                :disabled="!canSearch"
                :error="error"
                @submit="lookup"
            />
        </IntelSearchPanel>

        <IntelResultPanel>
            <EmptyState v-if="!result" :text="t('newsMediaIntel.empty')" />

            <div v-else class="telegram-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    <MetricCard :title="t('newsMediaIntel.summary.mentions')" :value="result.mentions.length" />
                    <MetricCard :title="t('newsMediaIntel.summary.positive')" :value="result.sentiment.positive" />
                    <MetricCard :title="t('newsMediaIntel.summary.neutral')" :value="result.sentiment.neutral" />
                    <MetricCard :title="t('newsMediaIntel.summary.negative')" :value="result.sentiment.negative" />
                </div>

                <SectionCard :title="t('newsMediaIntel.topics.title')">
                    <div class="flex flex-wrap gap-2">
                        <span v-for="topic in result.topics.slice(0, 18)" :key="topic.topic" class="rounded-full border border-border/80 px-2 py-1 text-xs text-muted-foreground">
                            {{ topic.topic }} ({{ topic.count }})
                        </span>
                    </div>
                </SectionCard>

                <SectionCard :title="t('newsMediaIntel.timeline.title')">
                    <div v-if="result.timeline.length === 0" class="intel-empty">{{ t('newsMediaIntel.timeline.none') }}</div>
                    <div v-else class="space-y-1 text-sm">
                        <p v-for="point in result.timeline" :key="point.date">{{ point.date }}: <span class="text-muted-foreground">{{ point.mentions }}</span></p>
                    </div>
                </SectionCard>

                <SectionCard :title="t('newsMediaIntel.mentions.title')">
                    <div v-if="result.mentions.length === 0" class="intel-empty">{{ t('newsMediaIntel.mentions.none') }}</div>
                    <div v-else class="space-y-2">
                        <article v-for="(item, index) in result.mentions.slice(0, 60)" :key="`${item.link}-${index}`" class="intel-surface">
                            <div class="mb-1 flex items-center justify-between gap-2">
                                <p class="font-medium">{{ item.title }}</p>
                                <span class="text-xs text-cyan-300">{{ item.source }}</span>
                            </div>
                            <p class="mb-1 text-xs text-muted-foreground">{{ item.snippet }}</p>
                            <a :href="item.link" target="_blank" rel="noopener noreferrer" class="text-xs text-cyan-300 hover:underline">{{ item.link }}</a>
                        </article>
                    </div>
                </SectionCard>
            </div>
        </IntelResultPanel>
    </div>
</template>
