<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Youtube } from 'lucide-vue-next'
import PageHeader from '@/components/ui/PageHeader.vue'
import { useI18n } from '@/composables/useI18n'
import { YOUTUBE_TABS } from './youtube/tabs'
import type { YouTubeTabValue } from './youtube/types'

defineOptions({
  layout: {
    breadcrumbs: [{ title: 'YouTube', href: '/youtube' }],
  },
})

const { t } = useI18n()
const activeTab = ref<YouTubeTabValue>('search')
const activeTabDefinition = computed(() => YOUTUBE_TABS.find((tab) => tab.key === activeTab.value) ?? YOUTUBE_TABS[0])
</script>

<template>
  <Head :title="t('youtube.headTitle')" />

  <div class="flex h-full min-h-0 flex-1 flex-col gap-4 overflow-hidden rounded-xl p-4">
    <PageHeader
      :icon="Youtube"
      :title="t('youtube.headTitle')"
      :help-label="t('youtube.help.label')"
      :help-text="activeTab === 'search' ? t('youtube.search.hint') : activeTab === 'analytics' ? t('youtube.analytics.hint') : t('youtube.parser.hint')"
    />

    <div class="flex items-center justify-center gap-1 rounded-lg bg-slate-800/80 p-1">
      <button
        v-for="tab in YOUTUBE_TABS"
        :key="tab.key"
        type="button"
        @click="activeTab = tab.key"
        :class="[
          'flex items-center rounded-md px-3 py-1.5 text-xs font-medium transition-all',
          activeTab === tab.key
            ? 'bg-slate-700/80 text-cyan-300 shadow-sm'
            : 'text-slate-400 hover:bg-slate-700/50 hover:text-slate-200',
        ]"
      >
        <component :is="tab.icon" class="mr-1.5 h-3.5 w-3.5" />
        {{ t(tab.labelKey) }}
      </button>
    </div>

    <component :is="activeTabDefinition.component" />
  </div>
</template>
