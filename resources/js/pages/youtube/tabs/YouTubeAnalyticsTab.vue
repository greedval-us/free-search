<script setup lang="ts">
import { ExternalLink, LoaderCircle } from 'lucide-vue-next'
import { reactive, ref } from 'vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue'
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue'
import MetricCard from '@/components/ui/MetricCard.vue'
import SectionCard from '@/components/ui/SectionCard.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { useI18n } from '@/composables/useI18n'
import { apiRequest } from '@/lib/api'
import type { YouTubeAnalyticsPayload } from '../types'

const { t } = useI18n()
const form = reactive({ mode: 'video' as 'video' | 'channel', videoId: '', channelId: '', limit: 10 })
const loading = ref(false)
const error = ref<string | null>(null)
const result = ref<YouTubeAnalyticsPayload | null>(null)

const numberFormat = new Intl.NumberFormat()
const fmt = (value: number) => numberFormat.format(value ?? 0)

const runAnalytics = async () => {
  loading.value = true
  error.value = null

  const response = await apiRequest<YouTubeAnalyticsPayload>('/youtube/analytics/summary', {
    query: {
      mode: form.mode,
      videoId: form.videoId,
      channelId: form.channelId,
      limit: form.limit,
    },
  })

  loading.value = false

  if (!response.ok) {
    error.value = response.message ?? t('youtube.common.requestFailed')
    return
  }

  result.value = response.data
}
</script>

<template>
  <IntelSearchPanel>
    <div class="grid gap-3 md:grid-cols-4">
      <label>
        <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('youtube.analytics.mode') }}</span>
        <select v-model="form.mode" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
          <option value="video">{{ t('youtube.options.mode.video') }}</option>
          <option value="channel">{{ t('youtube.options.mode.channel') }}</option>
        </select>
      </label>
      <label>
        <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('youtube.analytics.videoId') }}</span>
        <Input v-model="form.videoId" class="h-10" />
      </label>
      <label>
        <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('youtube.analytics.channelId') }}</span>
        <Input v-model="form.channelId" class="h-10" />
      </label>
      <label>
        <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ t('youtube.analytics.limit') }}</span>
        <Input v-model.number="form.limit" type="number" min="1" max="50" class="h-10" />
      </label>
    </div>

    <Button :disabled="loading || (form.videoId.trim() === '' && form.channelId.trim() === '')" class="mt-3 h-10" @click="runAnalytics">
      <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
      {{ loading ? t('youtube.common.loading') : t('youtube.analytics.submit') }}
    </Button>

    <p v-if="error" class="mt-2 text-sm text-destructive">{{ error }}</p>
  </IntelSearchPanel>

  <IntelResultPanel>
    <div class="telegram-scroll min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
      <EmptyState v-if="!result" :text="t('youtube.common.empty')" />

      <template v-else>
        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
          <MetricCard :title="t('youtube.analytics.metrics.videos')" :value="fmt(result.totals.videos)" />
          <MetricCard :title="t('youtube.analytics.metrics.views')" :value="fmt(result.totals.views)" />
          <MetricCard :title="t('youtube.analytics.metrics.likes')" :value="fmt(result.totals.likes)" />
          <MetricCard :title="t('youtube.analytics.metrics.avgViews')" :value="fmt(result.totals.avgViews)" />
          <MetricCard :title="t('youtube.analytics.metrics.engagement')" :value="`${result.totals.engagementRate}%`" />
        </div>

        <SectionCard :title="t('youtube.analytics.topVideos')">
          <div class="space-y-2">
            <article v-for="video in result.topVideos" :key="video.id" class="intel-surface">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <h3 class="text-sm font-medium">{{ video.title }}</h3>
                  <p class="mt-1 text-xs text-muted-foreground">{{ t('youtube.analytics.metrics.views') }}: {{ fmt(video.views) }} | {{ t('youtube.analytics.metrics.likes') }}: {{ fmt(video.likes) }} | {{ t('youtube.analytics.metrics.comments') }}: {{ fmt(video.comments) }}</p>
                </div>
                <a :href="video.url" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md border border-input px-3 py-1 text-xs hover:bg-accent">
                  <ExternalLink class="h-3 w-3" /> {{ t('youtube.common.open') }}
                </a>
              </div>
            </article>
          </div>
        </SectionCard>
      </template>
    </div>
  </IntelResultPanel>
</template>
