<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookmarkPlus, History, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import type { ActivityFeedRow, SavedQueryRow } from './types';
import { useDashboardLabels } from './useDashboardLabels';

const props = defineProps<
    | { mode: 'saved'; items: SavedQueryRow[] }
    | { mode: 'recent'; items: ActivityFeedRow[] }
>();
const emit = defineEmits<{ action: [id: number] }>();
const { t, moduleLabel, formatDateTime } = useDashboardLabels();
const saved = computed(() => props.mode === 'saved');
const rows = computed(() =>
    props.mode === 'saved'
        ? props.items.map((row) => ({
              ...row,
              key: `saved-${row.id}`,
              at: row.last_used_at ?? row.created_at,
          }))
        : props.items.map((row) => ({
              ...row,
              key: `${row.request_log_id}-${row.module_key}-${row.at}`,
              id: row.request_log_id,
          }))
);
</script>

<template>
    <ul
        :class="saved ? 'mt-3' : 'mt-4'"
        class="intel-scroll divide-y divide-border/70 md:max-h-[26rem] md:overflow-y-auto md:pr-1"
    >
        <li v-for="row in rows" :key="row.key" class="py-4">
            <p class="font-semibold">
                {{ moduleLabel(row.module_key) }}
            </p>
            <p class="mt-1 text-sm break-words">
                {{ row.query_preview }}
            </p>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ formatDateTime(row.at) }}
            </p>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <Link
                    v-if="row.run_url"
                    :href="row.run_url"
                    class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-medium text-primary transition-colors hover:bg-primary/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                >
                    {{
                        saved
                            ? t('dashboard.saved.run')
                            : t('dashboard.recent.runAgain')
                    }}
                </Link>
                <button
                    type="button"
                    class="inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    :class="
                        saved
                            ? 'text-destructive hover:bg-destructive/5'
                            : 'text-muted-foreground hover:bg-muted/60 hover:text-foreground'
                    "
                    @click="emit('action', row.id)"
                >
                    <component
                        :is="saved ? Trash2 : BookmarkPlus"
                        class="h-3.5 w-3.5"
                    />
                    {{
                        saved
                            ? t('dashboard.saved.delete')
                            : t('dashboard.recent.save')
                    }}
                </button>
            </div>
        </li>
        <li v-if="rows.length === 0" class="intel-empty-row">
            <History class="h-4 w-4" />
            {{
                saved ? t('dashboard.saved.empty') : t('dashboard.recent.empty')
            }}
        </li>
    </ul>
</template>
