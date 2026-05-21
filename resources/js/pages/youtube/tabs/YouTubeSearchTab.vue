<script setup lang="ts">
import { ExternalLink, LoaderCircle } from 'lucide-vue-next'
import { reactive, ref } from 'vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue'
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { useI18n } from '@/composables/useI18n'
import { apiRequest } from '@/lib/api'
import type { YouTubeSearchPayload, YouTubeVideo } from '../types'

const emit = defineEmits<{
  openAnalytics: [video: YouTubeVideo]
  openParser: [video: YouTubeVideo]
}>()

const { t } = useI18n()
const form = reactive({ q: '', channelId: '', order: 'relevance', limit: 12, pageToken: '' })
const loading = ref(false)
const error = ref<string | null>(null)
const result = ref<YouTubeSearchPayload | null>(null)

const numberFormat = new Intl.NumberFormat()
const fmt = (value: number) => numberFormat.format(value ?? 0)
const formatDate = (value: string) => (value ? new Date(value).toLocaleString() : '-')

const runSearch = async (append = false) => {
  loading.value = true
  error.value = null

  const response = await apiRequest<YouTubeSearchPayload>('/youtube/search/videos', {
    query: {
      q: form.q,
      channelId: form.channelId,
      order: form.order,
      limit: form.limit,
      pageToken: append ? result.value?.pagination.nextPageToken : form.pageToken,
    },
  })

  loading.value = false

  if (!response.ok) {
    error.value = response.message ?? t('youtube.common.requestFailed')
    return
  }

  if (append && result.value) {
    result.value = { ...response.data, items: [...result.value.items, ...response.data.items] }
    return
  }

  result.value = response.data
}
</script>

<template>
  <IntelSearchPanel>
    <div class="grid gap-3 md:grid-cols-5">
      <label class="md:col-span-2">
        <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('youtube.search.query') }}</span>
        <Input v-model="form.q" :placeholder="t('youtube.search.placeholder')" class="h-10" />
      </label>
      <label>
        <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('youtube.search.channelId') }}</span>
        <Input v-model="form.channelId" class="h-10" />
      </label>
      <label>
        <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('youtube.search.order') }}</span>
        <select v-model="form.order" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
          <option value="relevance">{{ t('youtube.options.searchOrder.relevance') }}</option>
          <option value="date">{{ t('youtube.options.searchOrder.date') }}</option>
          <option value="viewCount">{{ t('youtube.options.searchOrder.viewCount') }}</option>
          <option value="rating">{{ t('youtube.options.searchOrder.rating') }}</option>
        </select>
      </label>
      <label>
        <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('youtube.search.limit') }}</span>
        <Input v-model.number="form.limit" type="number" min="1" max="50" class="h-10" />
      </label>
    </div>

    <Button :disabled="loading || form.q.trim() === ''" class="mt-3 h-10" @click="runSearch(false)">
      <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
      {{ loading ? t('youtube.common.loading') : t('youtube.search.submit') }}
    </Button>

    <p v-if="error" class="mt-2 text-sm text-destructive">{{ error }}</p>
  </IntelSearchPanel>

  <IntelResultPanel>
    <div class="telegram-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
      <EmptyState v-if="!result || result.items.length === 0" :text="t('youtube.common.empty')" />

      <article v-for="video in result?.items ?? []" :key="video.id" class="intel-surface">
        <div class="flex gap-3">
          <img v-if="video.thumbnail" :src="video.thumbnail" :alt="video.title" class="h-24 w-40 rounded-md object-cover" loading="lazy" />
          <div class="min-w-0 flex-1">
            <h2 class="line-clamp-2 text-sm font-semibold">{{ video.title }}</h2>
            <p class="mt-1 text-xs text-muted-foreground">{{ video.channelTitle }} | {{ t('youtube.common.published') }}: {{ formatDate(video.publishedAt) }}</p>
            <p class="mt-2 line-clamp-2 text-xs text-muted-foreground">{{ video.description }}</p>
            <div class="mt-2 flex flex-wrap gap-2 text-xs">
              <span>{{ t('youtube.analytics.metrics.views') }}: {{ fmt(video.views) }}</span>
              <span>{{ t('youtube.analytics.metrics.likes') }}: {{ fmt(video.likes) }}</span>
              <span>{{ t('youtube.analytics.metrics.comments') }}: {{ fmt(video.comments) }}</span>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
              <a :href="video.url" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md border border-input px-3 py-1 text-xs hover:bg-accent">
                <ExternalLink class="h-3 w-3" /> {{ t('youtube.common.open') }}
              </a>
              <Button type="button" variant="outline" size="sm" @click="emit('openAnalytics', video)">{{ t('youtube.tabs.analytics') }}</Button>
              <Button type="button" variant="outline" size="sm" @click="emit('openParser', video)">{{ t('youtube.tabs.parser') }}</Button>
            </div>
          </div>
        </div>
      </article>

      <Button v-if="result?.pagination.nextPageToken" :disabled="loading" variant="outline" @click="runSearch(true)">
        {{ t('youtube.common.loadMore') }}
      </Button>
    </div>
  </IntelResultPanel>
</template>
