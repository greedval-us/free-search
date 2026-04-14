<script setup lang="ts">
import { LoaderCircle, Search } from 'lucide-vue-next';
import { computed, reactive } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useUsernameSearch } from '../composables/useUsernameSearch';
import type { UsernameSearchStatus } from '../types';

const { t } = useI18n();

const { form, loading, error, items, checkedAt, summary, canSearch, search } = useUsernameSearch(t);

const statusClassMap: Record<UsernameSearchStatus, string> = {
    found: 'border-emerald-500/40 bg-emerald-500/10 text-emerald-300',
    not_found: 'border-slate-500/40 bg-slate-500/10 text-slate-300',
    unknown: 'border-amber-500/40 bg-amber-500/10 text-amber-300',
};

const statusLabel = (status: UsernameSearchStatus): string => {
    if (status === 'found') {
        return t('username.status.found');
    }

    if (status === 'not_found') {
        return t('username.status.notFound');
    }

    return t('username.status.unknown');
};

const checkedAtText = computed(() => {
    if (!checkedAt.value) {
        return null;
    }

    return new Date(checkedAt.value).toLocaleString();
});

const regionOrder = ['cis', 'europe', 'americas', 'global'];

const statusFilters = reactive<Record<UsernameSearchStatus, boolean>>({
    found: true,
    not_found: true,
    unknown: true,
});

const filteredItems = computed(() =>
    items.value.filter((item) => statusFilters[item.status])
);

const groupedItems = computed(() => {
    const groups = new Map<string, typeof filteredItems.value>();

    for (const item of filteredItems.value) {
        const key = item.regionGroup || 'global';

        if (!groups.has(key)) {
            groups.set(key, []);
        }

        groups.get(key)?.push(item);
    }

    return [...groups.entries()]
        .sort((a, b) => regionOrder.indexOf(a[0]) - regionOrder.indexOf(b[0]))
        .map(([regionKey, groupItems]) => ({
            regionKey,
            title: t(`username.regions.${regionKey}`),
            items: groupItems,
        }));
});

const primaryUsersLabel = (region: string) => t(`username.regions.${region}`);
</script>

<template>
    <section class="sticky top-0 z-10 shrink-0 rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
        <div class="flex items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <Search class="h-4 w-4 text-cyan-400" />
                    <span>{{ t('username.search.title') }}</span>
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ t('username.search.description') }}
                </p>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-end gap-3">
            <label class="block min-w-0 flex-1">
                <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('username.search.label') }}</span>
                <input
                    v-model="form.username"
                    type="text"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    :placeholder="t('username.search.placeholder')"
                    @keydown.enter.prevent="search"
                />
            </label>

            <button
                :disabled="loading || !canSearch"
                class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                @click="search"
            >
                <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                <span>{{ loading ? t('username.search.searching') : t('username.search.find') }}</span>
            </button>
        </div>

        <p v-if="error" class="mt-3 text-sm text-destructive">{{ error }}</p>
    </section>

    <section class="flex min-h-0 flex-1 flex-col rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold">{{ t('username.results.title') }}</h2>
            <p v-if="checkedAtText" class="text-xs text-muted-foreground">{{ t('username.results.checkedAt') }}: {{ checkedAtText }}</p>
        </div>

        <div v-if="summary" class="mb-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-border/70 bg-background/60 p-3">
                <p class="text-xs text-muted-foreground">{{ t('username.results.checked') }}</p>
                <p class="mt-1 text-xl font-semibold">{{ summary.checked }}</p>
            </div>
            <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/5 p-3">
                <p class="text-xs text-muted-foreground">{{ t('username.results.found') }}</p>
                <p class="mt-1 text-xl font-semibold text-emerald-300">{{ summary.found }}</p>
            </div>
            <div class="rounded-lg border border-slate-500/30 bg-slate-500/5 p-3">
                <p class="text-xs text-muted-foreground">{{ t('username.results.notFound') }}</p>
                <p class="mt-1 text-xl font-semibold text-slate-300">{{ summary.notFound }}</p>
            </div>
            <div class="rounded-lg border border-amber-500/30 bg-amber-500/5 p-3">
                <p class="text-xs text-muted-foreground">{{ t('username.results.unknown') }}</p>
                <p class="mt-1 text-xl font-semibold text-amber-300">{{ summary.unknown }}</p>
            </div>
        </div>


        <div class="mb-3 flex flex-wrap items-center gap-2">
            <span class="text-xs text-muted-foreground">{{ t('username.filters.status') }}:</span>

            <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs">
                <input v-model="statusFilters.found" type="checkbox" class="h-3.5 w-3.5" />
                <span>{{ t('username.status.found') }}</span>
            </label>

            <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-slate-500/30 bg-slate-500/10 px-3 py-1 text-xs">
                <input v-model="statusFilters.not_found" type="checkbox" class="h-3.5 w-3.5" />
                <span>{{ t('username.status.notFound') }}</span>
            </label>

            <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs">
                <input v-model="statusFilters.unknown" type="checkbox" class="h-3.5 w-3.5" />
                <span>{{ t('username.status.unknown') }}</span>
            </label>
        </div>

        <div class="telegram-scroll min-h-0 flex-1 overflow-y-auto pr-1">
            <div v-if="!loading && filteredItems.length === 0" class="rounded-md border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                {{ t('username.results.empty') }}
            </div>

            <div v-else class="space-y-4">
                <section
                    v-for="group in groupedItems"
                    :key="group.regionKey"
                    class="space-y-2"
                >
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            {{ group.title }}
                        </h3>
                        <span class="text-xs text-muted-foreground">{{ group.items.length }}</span>
                    </div>

                    <article
                        v-for="item in group.items"
                        :key="item.key"
                        class="rounded-lg border border-border/80 bg-background/70 p-3"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold">{{ item.name }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">{{ item.profileUrl }}</p>
                            </div>

                            <span
                                class="rounded-full border px-2.5 py-1 text-xs font-semibold"
                                :class="statusClassMap[item.status]"
                            >
                                {{ statusLabel(item.status) }}
                            </span>
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                            <span>{{ t('username.results.region') }}: {{ t(`username.regions.${item.regionGroup}`) }}</span>
                            <span>{{ t('username.results.primaryUsers') }}: {{ primaryUsersLabel(item.primaryUsersRegion) }}</span>
                            <span>HTTP: {{ item.httpStatus ?? '-' }}</span>
                            <span>{{ t('username.results.confidence') }}: {{ item.confidence }}%</span>
                            <span v-if="item.error">{{ item.error }}</span>
                            <a
                                :href="item.profileUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="cursor-pointer rounded-full border border-input px-2 py-1 text-primary hover:bg-accent"
                            >
                                {{ t('username.results.openProfile') }}
                            </a>
                        </div>
                    </article>
                </section>
            </div>
        </div>
    </section>
</template>
