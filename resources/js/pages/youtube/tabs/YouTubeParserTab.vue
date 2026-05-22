<script setup lang="ts">
import { ChevronDown, ChevronUp, LoaderCircle, MessageSquareText } from 'lucide-vue-next'
import { onMounted, ref } from 'vue'
import HelpTooltip from '@/components/ui/HelpTooltip.vue'
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { getRepeatQueryParams, isRepeatAutorunEnabled, readRepeatQueryInt, readRepeatQueryParam } from '@/composables/useRepeatQuery'
import { useI18n } from '@/composables/useI18n'
import { apiRequest } from '@/lib/api'
import type { YouTubeCommentsPayload } from '../types'

const { t } = useI18n()
const form = ref({ videoId: '', order: 'relevance', searchTerms: '', limit: 20, pageToken: '' })
const loading = ref(false)
const loadingMore = ref(false)
const error = ref<string | null>(null)
const result = ref<YouTubeCommentsPayload | null>(null)
const panelCollapsed = ref(false)

const numberFormat = new Intl.NumberFormat()
const fmt = (value: number) => numberFormat.format(value ?? 0)
const formatDate = (value: string) => (value ? new Date(value).toLocaleString() : '-')

const runParser = async (append = false) => {
  if (append) {
    loadingMore.value = true
  } else {
    loading.value = true
    error.value = null
  }

  const response = await apiRequest<YouTubeCommentsPayload>('/youtube/parser/comments', {
    query: {
      videoId: form.value.videoId,
      order: form.value.order,
      searchTerms: form.value.searchTerms,
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

onMounted(() => {
  const params = getRepeatQueryParams()

  if (!params) {
    return
  }

  const tab = readRepeatQueryParam(params, ['tab'])
  if (tab !== 'parser') {
    return
  }

  const videoId = readRepeatQueryParam(params, ['videoId'])
  const order = readRepeatQueryParam(params, ['order'])
  const searchTerms = readRepeatQueryParam(params, ['searchTerms'])
  const limit = readRepeatQueryInt(params, 'limit')

  if (videoId !== '') form.value.videoId = videoId
  if (order === 'relevance' || order === 'time') form.value.order = order
  if (searchTerms !== '') form.value.searchTerms = searchTerms
  if (limit !== null) form.value.limit = Math.min(100, Math.max(1, limit))

  if (isRepeatAutorunEnabled(params) && form.value.videoId.trim() !== '') {
    void runParser(false)
  }
})
</script>

<template>
  <section class="sticky top-0 z-10 shrink-0 intel-panel-strong">
    <div class="flex items-center justify-between gap-3">
      <div class="space-y-1">
        <div class="flex items-center gap-2 text-sm font-semibold">
          <MessageSquareText class="h-4 w-4 text-cyan-400" />
          <span>{{ t('youtube.parser.title') }}</span>
          <HelpTooltip :label="t('youtube.help.label')" :text="t('youtube.parser.hint')" />
        </div>
        <p class="text-xs text-muted-foreground">
          {{ panelCollapsed ? t('youtube.parser.collapsed') : t('youtube.parser.hint') }}
        </p>
      </div>

      <button
        type="button"
        class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-input text-sm text-foreground transition hover:bg-accent"
        @click="panelCollapsed = !panelCollapsed"
      >
        <ChevronDown v-if="panelCollapsed" class="h-4 w-4" />
        <ChevronUp v-else class="h-4 w-4" />
      </button>
    </div>

    <div v-if="!panelCollapsed" class="mt-3 space-y-3">
      <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-12">
        <label class="block min-w-0 xl:col-span-4">
          <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('youtube.parser.videoId') }}</span>
          <input v-model="form.videoId" type="text" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
        </label>
        <label class="block min-w-0 xl:col-span-4">
          <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('youtube.parser.query') }}</span>
          <input v-model="form.searchTerms" type="text" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
        </label>
        <label class="block min-w-0 xl:col-span-2">
          <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('youtube.parser.order') }}</span>
          <select v-model="form.order" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
            <option value="relevance">{{ t('youtube.options.parserOrder.relevance') }}</option>
            <option value="time">{{ t('youtube.options.parserOrder.time') }}</option>
          </select>
        </label>
        <label class="block min-w-0 xl:col-span-2">
          <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('youtube.parser.limit') }}</span>
          <input v-model.number="form.limit" type="number" min="1" max="100" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm" />
        </label>
      </div>

      <button
        :disabled="loading || form.videoId.trim() === ''"
        class="h-10 cursor-pointer rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
        @click="runParser(false)"
      >
        <LoaderCircle v-if="loading" class="mr-2 inline h-4 w-4 animate-spin" />
        {{ loading ? t('youtube.common.loading') : t('youtube.parser.submit') }}
      </button>

      <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
    </div>
  </section>

  <IntelResultPanel>
    <div class="telegram-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
      <EmptyState v-if="!result || result.items.length === 0" :text="t('youtube.common.empty')" />

      <article v-for="comment in result?.items ?? []" :key="comment.id" class="rounded-lg border border-border/80 bg-background/70 p-3">
        <div class="mb-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
          <a v-if="comment.authorChannelUrl" :href="comment.authorChannelUrl" target="_blank" rel="noopener noreferrer" class="text-primary">{{ comment.author }}</a>
          <span v-else>{{ comment.author }}</span>
          <span>{{ formatDate(comment.publishedAt) }}</span>
          <span>{{ t('youtube.analytics.metrics.likes') }}: {{ fmt(comment.likeCount) }}</span>
          <span>{{ t('youtube.parser.replies') }}: {{ fmt(comment.replyCount) }}</span>
        </div>
        <p class="text-sm leading-relaxed text-foreground">{{ comment.text }}</p>

        <div v-if="comment.replies.length > 0" class="mt-3 rounded-lg border border-border/70 bg-card/60 p-3">
          <article v-for="reply in comment.replies" :key="reply.id" class="mb-2 last:mb-0 text-xs">
            <p class="font-medium">{{ reply.author }} | {{ formatDate(reply.publishedAt) }}</p>
            <p class="mt-1 whitespace-pre-wrap text-muted-foreground">{{ reply.text }}</p>
          </article>
        </div>
      </article>

      <div v-if="result?.pagination.nextPageToken" class="mt-4 flex justify-center">
        <button
          :disabled="loadingMore"
          class="cursor-pointer rounded-md border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
          @click="runParser(true)"
        >
          {{ loadingMore ? t('youtube.common.loading') : t('youtube.common.loadMore') }}
        </button>
      </div>
    </div>
  </IntelResultPanel>
</template>
