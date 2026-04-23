<script setup lang="ts">
import { LoaderCircle, Search } from 'lucide-vue-next';
import { onMounted } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useDorksSearch } from '../composables/useDorksSearch';

const { t } = useI18n();
const { form, loading, error, payload, goals, scopes, canSearch, loadGoals, search } = useDorksSearch(t);

onMounted(() => {
    loadGoals();
});
</script>

<template>
    <section class="sticky top-0 z-10 shrink-0 rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
        <div class="space-y-1">
            <div class="flex items-center gap-2 text-sm font-semibold">
                <Search class="h-4 w-4 text-cyan-400" />
                <span>{{ t('dorks.search.title') }}</span>
                <span class="group relative inline-flex">
                    <span
                        class="inline-flex h-5 w-5 cursor-help items-center justify-center rounded-full border border-border text-[11px] font-semibold text-muted-foreground"
                        :aria-label="t('dorks.help.label')"
                    >
                        ?
                    </span>
                    <span class="pointer-events-none absolute left-0 top-6 z-20 hidden w-80 rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed text-popover-foreground shadow-xl group-hover:block">
                        {{ t('dorks.search.help.overview') }}
                    </span>
                </span>
            </div>
            <p class="text-xs text-muted-foreground">
                {{ t('dorks.search.description') }}
            </p>
        </div>

        <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-[1fr_1fr_220px_220px_auto]">
            <label class="block min-w-0">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('dorks.search.target') }}</span>
                <input
                    v-model="form.target"
                    type="text"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    :placeholder="t('dorks.search.targetPlaceholder')"
                    @keydown.enter.prevent="search"
                />
            </label>

            <label class="block min-w-0">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('dorks.search.site') }}</span>
                <input
                    v-model="form.site"
                    type="text"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    :placeholder="t('dorks.search.sitePlaceholder')"
                    @keydown.enter.prevent="search"
                />
            </label>

            <label class="block min-w-0">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('dorks.search.goal') }}</span>
                <select
                    v-model="form.goal"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option v-for="goal in goals" :key="goal.key" :value="goal.key">
                        {{ goal.label }}
                    </option>
                </select>
            </label>

            <label class="block min-w-0">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('dorks.search.scope') }}</span>
                <select
                    v-model="form.scope"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option v-for="scope in scopes" :key="scope.key" :value="scope.key">
                        {{ scope.label }}
                    </option>
                </select>
            </label>

            <button
                :disabled="loading || !canSearch"
                class="inline-flex h-10 cursor-pointer items-center gap-2 self-end rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                @click="search"
            >
                <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                <span>{{ loading ? t('dorks.search.searching') : t('dorks.search.find') }}</span>
            </button>
        </div>

        <p v-if="error" class="mt-3 text-sm text-destructive">{{ error }}</p>
    </section>

    <section class="flex min-h-0 flex-1 flex-col rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold">{{ t('dorks.results.title') }}</h2>
            <p v-if="payload?.checkedAt" class="text-xs text-muted-foreground">
                {{ t('dorks.results.checkedAt') }}: {{ new Date(payload.checkedAt).toLocaleString() }}
            </p>
        </div>

        <div v-if="!payload" class="rounded-md border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
            {{ t('dorks.results.empty') }}
        </div>

        <div v-else class="telegram-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
            <div class="mb-1 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="text-xs text-muted-foreground">{{ t('dorks.results.total') }}</p>
                    <p class="mt-1 text-xl font-semibold">{{ payload.summary.total }}</p>
                </div>
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="text-xs text-muted-foreground">{{ t('dorks.results.uniqueDomains') }}</p>
                    <p class="mt-1 text-xl font-semibold">{{ payload.summary.uniqueDomains }}</p>
                </div>
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="text-xs text-muted-foreground">{{ t('dorks.results.sources') }}</p>
                    <p class="mt-1 text-xl font-semibold">{{ payload.summary.sources }}</p>
                </div>
                <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                    <p class="text-xs text-muted-foreground">{{ t('dorks.results.goals') }}</p>
                    <p class="mt-1 text-xl font-semibold">{{ payload.summary.goals }}</p>
                </div>
            </div>

            <article
                v-for="(item, index) in payload.items"
                :key="`${item.url}-${index}`"
                class="rounded-lg border border-border/80 bg-background/70 p-3"
            >
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <p class="text-sm font-semibold">{{ item.title }}</p>
                    <span class="rounded-full border border-input px-2 py-1 text-xs text-muted-foreground">
                        {{ item.source }} / {{ item.goal }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">{{ item.snippet }}</p>
                <p class="mt-2 text-xs text-muted-foreground">{{ item.dork }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                    <span class="rounded-full border border-input px-2 py-1 text-muted-foreground">{{ item.domain || '-' }}</span>
                    <a :href="item.url" target="_blank" rel="noopener noreferrer" class="rounded-full border border-input px-2 py-1 text-primary">
                        {{ t('dorks.results.open') }}
                    </a>
                </div>
            </article>
        </div>
    </section>
</template>
