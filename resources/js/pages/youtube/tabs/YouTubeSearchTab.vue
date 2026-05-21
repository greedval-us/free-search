<script setup lang="ts">
import { ChevronDown, ChevronUp, ExternalLink, LoaderCircle, Search } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import HelpTooltip from '@/components/ui/HelpTooltip.vue'
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue'
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue'
import { useI18n } from '@/composables/useI18n'
import { apiRequest } from '@/lib/api'
import type { YouTubeSearchPayload, YouTubeVideo } from '../types'

const emit = defineEmits<{
  openAnalytics: [video: YouTubeVideo]
  openParser: [video: YouTubeVideo]
}>()

const { t } = useI18n()
const form = ref({ q: '', channelId: '', order: 'relevance', limit: 12, pageToken: '' })
const loading = ref(false)
const loadingMore = ref(false)
const error = ref<string | null>(null)
const result = ref<YouTubeSearchPayload | null>(null)
const searchPanelCollapsed = ref(false)

const canSearch = computed(() => form.value.q.trim().length > 0)

const numberFormat = new Intl.NumberFormat()
const fmt = (value: number) => numberFormat.format(value ?? 0)
const formatDate = (value: string) => (value ? new Date(value).toLocaleString() : '-')

const runSearch = async (append = false) => {
  if (append) {
    loadingMore.value = true
  } else {
    loading.value = true
    error.value = null
  }

  const response = await apiRequest<YouTubeSearchPayload>('/youtube/search/videos', {
    query: {
      q: form.value.q,
      channelId: form.value.channelId,
      order: form.value.order,
      limit: form.value.limit,
      pageToken: append ? result.value?.pagination.nextPageToken : form.value.pageToken,
    },
  })

  loading.value = false
  loadingMore.value = false

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
    <div class="flex items-center justify-between gap-3">
      <div class="space-y-1">
        <div class="flex items-center gap-2 text-sm font-semibold">
          <Search class="h-4 w-4 text-cyan-400" />
          <span>{{ t('youtube.search.title') }}</span>
          <HelpTooltip :label="t('youtube.help.label')" :text="t('youtube.search.hint')" />
        </div>
        <p class="text-xs text-muted-foreground">
          {{ searchPanelCollapsed ? t('youtube.search.collapsed') : t('youtube.search.filters') }}
        </p>
      </div>

      <button
        type="button"
        class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-input text-sm text-foreground transition hover:bg-accent"
        @click="searchPanelCollapsed = !searchPanelCollapsed"
      >
        <ChevronDown v-if="searchPanelCollapsed" class="h-4 w-4" />
        <ChevronUp v-else class="h-4 w-4" />
      </button>
    </div>

    <div v-if="!searchPanelCollapsed" class="mt-3 flex flex-wrap items-end gap-3">
      <div class="grid min-w-0 flex-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
        <label class="block min-w-0 xl:col-span-2">
          <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('youtube.search.query') }}</span>
          <input
            v-model="form.q"
            type="text"
            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
            :placeholder="t('youtube.search.placeholder')"
          />
        </label>

        <label class="block min-w-0">
          <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('youtube.search.channelId') }}</span>
          <input
            v-model="form.channelId"
            type="text"
            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
          />
        </label>

        <label class="block min-w-0">
          <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('youtube.search.order') }}</span>
          <select v-model="form.order" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
            <option value="relevance">{{ t('youtube.options.searchOrder.relevance') }}</option>
            <option value="date">{{ t('youtube.options.searchOrder.date') }}</option>
            <option value="viewCount">{{ t('youtube.options.searchOrder.viewCount') }}</option>
            <option value="rating">{{ t('youtube.options.searchOrder.rating') }}</option>
          </select>
        </label>
      </div>

      <div class="flex w-full flex-wrap items-end gap-2 lg:w-auto">
        <label class="block min-w-[120px]">
          <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('youtube.search.limit') }}</span>
          <input
            v-model.number="form.limit"
            type="number"
            min="1"
            max="50"
            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
          />
        </label>

        <button
          :disabled="loading || !canSearch"
          class="h-10 self-end cursor-pointer rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
          @click="runSearch(false)"
        >
          {{ loading ? t('youtube.common.loading') : t('youtube.search.submit') }}
        </button>
      </div>
    </div>

    <p v-if="error" class="mt-3 text-sm text-destructive">{{ error }}</p>
  </IntelSearchPanel>

  <IntelResultPanel>
    <div class="mb-3 flex items-center justify-between">
      <h2 class="text-sm font-semibold">{{ t('youtube.search.resultTitle') }}</h2>
      <p class="text-xs text-muted-foreground">{{ t('youtube.search.shown') }}: {{ result?.items.length ?? 0 }} / {{ result?.pagination.total ?? 0 }}</p>
    </div>

    <div class="telegram-scroll min-h-0 flex-1 overflow-y-auto overscroll-contain pr-1">
      <div v-if="!loading && (!result || result.items.length === 0)" class="intel-empty">
        {{ t('youtube.common.empty') }}
      </div>

      <div v-else class="space-y-3">
        <article v-for="video in result?.items ?? []" :key="video.id" class="relative rounded-lg border border-border/80 bg-background/70 p-3">
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
                <a :href="video.url" target="_blank" rel="noopener noreferrer" class="cursor-pointer rounded-full border border-input px-2 py-1 text-xs text-primary hover:bg-accent">
                  <ExternalLink class="mr-1 inline h-3 w-3" /> {{ t('youtube.common.open') }}
                </a>
                <button type="button" class="cursor-pointer rounded-full border border-input px-3 py-1 text-xs font-medium text-foreground hover:bg-accent" @click="emit('openAnalytics', video)">{{ t('youtube.tabs.analytics') }}</button>
                <button type="button" class="cursor-pointer rounded-full border border-input px-3 py-1 text-xs font-medium text-foreground hover:bg-accent" @click="emit('openParser', video)">{{ t('youtube.tabs.parser') }}</button>
              </div>
            </div>
          </div>
        </article>
      </div>
    </div>

    <div v-if="result?.pagination.nextPageToken" class="mt-4 flex justify-center">
      <button
        :disabled="loadingMore"
        class="cursor-pointer rounded-md border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
        @click="runSearch(true)"
      >
        {{ loadingMore ? t('youtube.common.loading') : t('youtube.common.loadMore') }}
      </button>
    </div>
  </IntelResultPanel>
</template>
