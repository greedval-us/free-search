<script setup lang="ts">
import { LoaderCircle } from 'lucide-vue-next'
import { reactive, ref } from 'vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue'
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { useI18n } from '@/composables/useI18n'
import { apiRequest } from '@/lib/api'
import type { YouTubeCommentsPayload } from '../types'

const { t } = useI18n()
const form = reactive({ videoId: '', order: 'relevance', searchTerms: '', limit: 20, pageToken: '' })
const loading = ref(false)
const error = ref<string | null>(null)
const result = ref<YouTubeCommentsPayload | null>(null)

const numberFormat = new Intl.NumberFormat()
const fmt = (value: number) => numberFormat.format(value ?? 0)
const formatDate = (value: string) => (value ? new Date(value).toLocaleString() : '-')

const runParser = async (append = false) => {
  loading.value = true
  error.value = null

  const response = await apiRequest<YouTubeCommentsPayload>('/youtube/parser/comments', {
    query: {
      videoId: form.videoId,
      order: form.order,
      searchTerms: form.searchTerms,
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
    <div class="grid gap-3 md:grid-cols-4">
      <label>
        <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('youtube.parser.videoId') }}</span>
        <Input v-model="form.videoId" class="h-10" />
      </label>
      <label>
        <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('youtube.parser.query') }}</span>
        <Input v-model="form.searchTerms" class="h-10" />
      </label>
      <label>
        <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('youtube.parser.order') }}</span>
        <select v-model="form.order" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
          <option value="relevance">{{ t('youtube.options.parserOrder.relevance') }}</option>
          <option value="time">{{ t('youtube.options.parserOrder.time') }}</option>
        </select>
      </label>
      <label>
        <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('youtube.parser.limit') }}</span>
        <Input v-model.number="form.limit" type="number" min="1" max="100" class="h-10" />
      </label>
    </div>

    <Button :disabled="loading || form.videoId.trim() === ''" class="mt-3 h-10" @click="runParser(false)">
      <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
      {{ loading ? t('youtube.common.loading') : t('youtube.parser.submit') }}
    </Button>

    <p v-if="error" class="mt-2 text-sm text-destructive">{{ error }}</p>
  </IntelSearchPanel>

  <IntelResultPanel>
    <div class="telegram-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
      <EmptyState v-if="!result || result.items.length === 0" :text="t('youtube.common.empty')" />

      <article v-for="comment in result?.items ?? []" :key="comment.id" class="intel-surface">
        <div class="mb-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
          <a v-if="comment.authorChannelUrl" :href="comment.authorChannelUrl" target="_blank" rel="noopener noreferrer" class="text-primary">{{ comment.author }}</a>
          <span v-else>{{ comment.author }}</span>
          <span>{{ formatDate(comment.publishedAt) }}</span>
          <span>{{ t('youtube.analytics.metrics.likes') }}: {{ fmt(comment.likeCount) }}</span>
          <span>{{ t('youtube.parser.replies') }}: {{ fmt(comment.replyCount) }}</span>
        </div>
        <p class="whitespace-pre-wrap text-sm">{{ comment.text }}</p>

        <div v-if="comment.replies.length > 0" class="mt-3 space-y-2 border-l border-border pl-3">
          <article v-for="reply in comment.replies" :key="reply.id" class="text-xs">
            <p class="font-medium">{{ reply.author }} | {{ formatDate(reply.publishedAt) }}</p>
            <p class="mt-1 whitespace-pre-wrap text-muted-foreground">{{ reply.text }}</p>
          </article>
        </div>
      </article>

      <Button v-if="result?.pagination.nextPageToken" :disabled="loading" variant="outline" @click="runParser(true)">
        {{ t('youtube.common.loadMore') }}
      </Button>
    </div>
  </IntelResultPanel>
</template>
