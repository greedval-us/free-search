<script setup lang="ts">
import { computed } from 'vue'
import SectionCard from '@/components/ui/SectionCard.vue'
import HelpTooltip from '@/components/ui/HelpTooltip.vue'
import { useI18n } from '@/composables/useI18n'

type Distribution = {
  duration: Record<'short' | 'medium' | 'long', number>
  definition: Record<string, number>
  captions: { with: number; without: number }
}

const props = defineProps<{
  distribution: Distribution
}>()

const { t } = useI18n()

const maxDuration = computed(() => Math.max(
  props.distribution.duration.short ?? 0,
  props.distribution.duration.medium ?? 0,
  props.distribution.duration.long ?? 0,
  1,
))
const maxDefinition = computed(() => Math.max(1, ...(Object.values(props.distribution.definition ?? {}))))
const maxCaptions = computed(() => Math.max(
  props.distribution.captions.with ?? 0,
  props.distribution.captions.without ?? 0,
  1,
))
const barWidth = (value: number, max: number) => `${Math.max(6, (value / Math.max(1, max)) * 100)}%`
</script>

<template>
  <SectionCard :title="t('youtube.analytics.distribution')">
    <template #actions>
      <HelpTooltip :label="t('youtube.analytics.help.label')" :text="t('youtube.analytics.help.distribution')" width-class="w-72" align="right" />
    </template>

    <div class="grid gap-3 md:grid-cols-3">
      <div class="rounded-lg border border-border/80 bg-background/70 p-3">
        <p class="text-xs font-medium">{{ t('youtube.analytics.duration') }}</p>
        <div class="mt-2 space-y-2 text-xs">
          <div class="space-y-1">
            <div class="flex items-center justify-between"><span>{{ t('youtube.analytics.durationLabels.short') }}</span><span>{{ distribution.duration.short }}</span></div>
            <div class="h-1.5 rounded-full bg-muted"><div class="h-full rounded-full bg-cyan-400" :style="{ width: barWidth(distribution.duration.short, maxDuration) }" /></div>
          </div>
          <div class="space-y-1">
            <div class="flex items-center justify-between"><span>{{ t('youtube.analytics.durationLabels.medium') }}</span><span>{{ distribution.duration.medium }}</span></div>
            <div class="h-1.5 rounded-full bg-muted"><div class="h-full rounded-full bg-cyan-400" :style="{ width: barWidth(distribution.duration.medium, maxDuration) }" /></div>
          </div>
          <div class="space-y-1">
            <div class="flex items-center justify-between"><span>{{ t('youtube.analytics.durationLabels.long') }}</span><span>{{ distribution.duration.long }}</span></div>
            <div class="h-1.5 rounded-full bg-muted"><div class="h-full rounded-full bg-cyan-400" :style="{ width: barWidth(distribution.duration.long, maxDuration) }" /></div>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-border/80 bg-background/70 p-3">
        <p class="text-xs font-medium">{{ t('youtube.analytics.quality') }}</p>
        <div class="mt-2 space-y-2 text-xs">
          <div v-for="(count, key) in distribution.definition" :key="key" class="space-y-1">
            <div class="flex items-center justify-between"><span>{{ key || '-' }}</span><span>{{ count }}</span></div>
            <div class="h-1.5 rounded-full bg-muted"><div class="h-full rounded-full bg-emerald-400" :style="{ width: barWidth(Number(count), maxDefinition) }" /></div>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-border/80 bg-background/70 p-3">
        <p class="text-xs font-medium">{{ t('youtube.analytics.captions') }}</p>
        <div class="mt-2 space-y-2 text-xs">
          <div class="space-y-1">
            <div class="flex items-center justify-between"><span>{{ t('youtube.analytics.withCaptions') }}</span><span>{{ distribution.captions.with }}</span></div>
            <div class="h-1.5 rounded-full bg-muted"><div class="h-full rounded-full bg-amber-400" :style="{ width: barWidth(distribution.captions.with, maxCaptions) }" /></div>
          </div>
          <div class="space-y-1">
            <div class="flex items-center justify-between"><span>{{ t('youtube.analytics.withoutCaptions') }}</span><span>{{ distribution.captions.without }}</span></div>
            <div class="h-1.5 rounded-full bg-muted"><div class="h-full rounded-full bg-amber-400" :style="{ width: barWidth(distribution.captions.without, maxCaptions) }" /></div>
          </div>
        </div>
      </div>
    </div>
  </SectionCard>
</template>
