<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Pin } from 'lucide-vue-next';
import type { FavoriteModule, ModuleCard } from './types';
import { useDashboardLabels } from './useDashboardLabels';

defineProps<{ favorite: FavoriteModule | null; modules: ModuleCard[] }>();
const emit = defineEmits<{ togglePin: [moduleKey: string] }>();
const { t, moduleLabel, formatDateTime } = useDashboardLabels();
</script>

<template>
    <article class="intel-panel min-h-0">
        <div class="flex items-center justify-between">
            <h2 class="intel-section-heading">
                {{ t('dashboard.sections.favorite') }}
            </h2>
        </div>
        <div v-if="favorite" class="mt-4 border-l-2 border-primary/35 pl-3">
            <p class="text-base font-semibold">
                {{ moduleLabel(favorite.key) }}
            </p>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ t('dashboard.favorite.usedPrefix') }}
                {{ favorite.total }}
                {{ t('dashboard.favorite.usedSuffix') }}
            </p>
        </div>
        <p v-else class="mt-3 text-sm text-muted-foreground">
            {{ t('dashboard.favorite.empty') }}
        </p>

        <h3 class="intel-kicker mt-4">
            {{ t('dashboard.sections.topModules') }}
        </h3>
        <ul
            class="intel-scroll mt-2 divide-y divide-border/70 md:max-h-72 md:overflow-y-auto md:pr-1"
        >
            <li
                v-for="module in modules"
                :key="`${module.key}-${module.count}`"
                class="py-3"
            >
                <div class="flex items-center justify-between gap-2">
                    <p class="font-semibold">
                        {{ moduleLabel(module.key) }}
                    </p>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-muted/60 hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                            :aria-label="
                                module.is_pinned
                                    ? t('dashboard.modules.unpinAria')
                                    : t('dashboard.modules.pinAria')
                            "
                            @click="emit('togglePin', module.key)"
                        >
                            <Pin
                                class="h-3.5 w-3.5"
                                :class="module.is_pinned ? 'text-primary' : ''"
                            />
                        </button>
                        <span class="intel-badge-count text-sm">
                            {{ module.count }}
                        </span>
                    </div>
                </div>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ t('dashboard.modules.lastUsed') }}:
                    {{ formatDateTime(module.last_at) }}
                </p>
                <Link
                    :href="module.url"
                    class="mt-2 inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-medium text-primary transition-colors hover:bg-primary/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                >
                    {{ t('dashboard.modules.open') }}
                </Link>
            </li>
            <li
                v-if="modules.length === 0"
                class="text-sm text-muted-foreground"
            >
                {{ t('dashboard.modules.empty') }}
            </li>
        </ul>
    </article>
</template>
