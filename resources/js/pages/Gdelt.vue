<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    CalendarDays,
    ChevronDown,
    ChevronUp,
    Filter,
    Globe,
    Search,
    Settings2,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useGdeltSearch } from './gdelt/composables/useGdeltSearch';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'GDELT',
                href: '/gdelt',
            },
        ],
    },
});

const { t } = useI18n();

const pageTitle = computed(() => t('gdelt.headTitle'));

const {
    LIMIT_MAX,
    SORT_OPTIONS,
    COUNTRY_OPTIONS,
    SOURCE_LANG_OPTIONS,
    form,
    loading,
    error,
    total,
    filteredItems,
    canSearch,
    builtQueryPreview,
    searchPanelCollapsed,
    showAdvanced,
    showQueryBuilder,
    lastQuery,
    clampLimit,
    applyPresetRange,
    setDefaultWeekRange,
    formatSeenDate,
    normalizeTone,
    search,
} = useGdeltSearch(t);

const shownCount = computed(() => filteredItems.value.length);
</script>

<template>
    <Head :title="pageTitle" />

    <div class="flex h-full min-h-0 flex-1 flex-col gap-4 overflow-hidden rounded-xl p-4">
        <section class="sticky top-0 z-10 shrink-0 rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
            <div class="flex items-center justify-between gap-3">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-sm font-semibold">
                        <Globe class="h-4 w-4 text-cyan-400" />
                        <span>{{ t('gdelt.title') }}</span>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        {{ searchPanelCollapsed ? t('gdelt.collapsed') : t('gdelt.subtitle') }}
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
                <div class="w-full rounded-md border border-border/60 bg-background/50 px-3 py-2 text-xs text-muted-foreground">
                    <span class="font-medium text-foreground">{{ t('gdelt.required.title') }}:</span>
                    {{ t('gdelt.required.fields') }}
                </div>

                <div class="grid min-w-0 flex-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <label class="block min-w-0">
                        <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('gdelt.filters.keywordsRequired') }}</span>
                        <input
                            v-model="form.keywords"
                            type="text"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            :placeholder="t('gdelt.placeholders.keywords')"
                            required
                        />
                    </label>

                    <label class="block min-w-0">
                        <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('gdelt.filters.exactPhrase') }}</span>
                        <input
                            v-model="form.exactPhrase"
                            type="text"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            :placeholder="t('gdelt.placeholders.exactPhrase')"
                        />
                    </label>

                    <label class="block min-w-0">
                        <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('gdelt.filters.anyWords') }}</span>
                        <input
                            v-model="form.anyWords"
                            type="text"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            :placeholder="t('gdelt.placeholders.anyWords')"
                        />
                    </label>
                </div>

                <div class="flex w-full flex-wrap gap-2 lg:w-auto">
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-md border border-slate-700 bg-slate-900/80 text-slate-200 transition hover:border-cyan-300/40 hover:text-cyan-100"
                        :class="{ 'border-cyan-400/50 bg-cyan-400/20 text-cyan-300': showAdvanced }"
                        @click="showAdvanced = !showAdvanced"
                    >
                        <Settings2 class="h-4 w-4" />
                    </button>

                    <button
                        type="button"
                        class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-md border border-slate-700 bg-slate-900/80 text-slate-200 transition hover:border-cyan-300/40 hover:text-cyan-100"
                        :class="{ 'border-cyan-400/50 bg-cyan-400/20 text-cyan-300': showQueryBuilder }"
                        @click="showQueryBuilder = !showQueryBuilder"
                    >
                        <Filter class="h-4 w-4" />
                    </button>

                    <button
                        :disabled="loading || !canSearch"
                        class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                        @click="search"
                    >
                        <Search class="h-4 w-4" />
                        {{ loading ? t('gdelt.searching') : t('gdelt.find') }}
                    </button>
                </div>
            </div>

            <div
                v-if="!searchPanelCollapsed && showAdvanced"
                class="mt-3 grid gap-3 border-t border-border/60 pt-3 md:grid-cols-2 xl:grid-cols-4"
            >
                <div class="col-span-full rounded-md border border-border/60 bg-background/50 px-3 py-2 text-xs text-muted-foreground">
                    <span class="font-medium text-foreground">{{ t('gdelt.optional.title') }}:</span>
                    {{ t('gdelt.optional.fields') }}
                </div>

                <label class="block min-w-0">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('gdelt.filters.excludeWords') }}</span>
                    <input
                        v-model="form.excludeWords"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('gdelt.placeholders.excludeWords')"
                    />
                </label>

                <label class="block min-w-0">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('gdelt.filters.domain') }}</span>
                    <input
                        v-model="form.domain"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('gdelt.placeholders.domain')"
                    />
                </label>

                <label class="block min-w-0">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('gdelt.filters.domainExact') }}</span>
                    <input
                        v-model="form.domainExact"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('gdelt.placeholders.domainExact')"
                    />
                </label>

                <label class="block min-w-0">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('gdelt.filters.sort') }}</span>
                    <select
                        v-model="form.sort"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option v-for="option in SORT_OPTIONS" :key="option.value" :value="option.value">
                            {{ t(option.labelKey) }}
                        </option>
                    </select>
                </label>

                <label class="block min-w-0">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('gdelt.filters.country') }}</span>
                    <select
                        v-model="form.sourceCountry"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option v-for="option in COUNTRY_OPTIONS" :key="option.value || 'any-country'" :value="option.value">
                            {{ t(option.labelKey) }}
                        </option>
                    </select>
                </label>

                <label class="block min-w-0">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('gdelt.filters.sourceLanguage') }}</span>
                    <select
                        v-model="form.sourceLang"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option v-for="option in SOURCE_LANG_OPTIONS" :key="option.value || 'any-lang'" :value="option.value">
                            {{ t(option.labelKey) }}
                        </option>
                    </select>
                </label>

                <label class="block min-w-0">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('gdelt.filters.maxRecords') }}</span>
                    <input
                        v-model.number="form.maxRecords"
                        type="number"
                        min="1"
                        :max="LIMIT_MAX"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        @input="clampLimit"
                        @blur="clampLimit"
                    />
                </label>

                <div class="grid min-w-0 grid-cols-2 gap-3">
                    <label class="block min-w-0">
                        <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('gdelt.filters.toneMin') }}</span>
                        <input
                            v-model="form.toneMin"
                            type="number"
                            step="0.1"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            :placeholder="t('gdelt.placeholders.toneMin')"
                        />
                    </label>

                    <label class="block min-w-0">
                        <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('gdelt.filters.toneMax') }}</span>
                        <input
                            v-model="form.toneMax"
                            type="number"
                            step="0.1"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            :placeholder="t('gdelt.placeholders.toneMax')"
                        />
                    </label>
                </div>

                <label class="block min-w-0">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('gdelt.filters.theme') }}</span>
                    <input
                        v-model="form.theme"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('gdelt.placeholders.theme')"
                    />
                </label>

                <label class="block min-w-0">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('gdelt.filters.person') }}</span>
                    <input
                        v-model="form.person"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('gdelt.placeholders.person')"
                    />
                </label>

                <label class="block min-w-0">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('gdelt.filters.organization') }}</span>
                    <input
                        v-model="form.organization"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('gdelt.placeholders.organization')"
                    />
                </label>

                <label class="block min-w-0">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('gdelt.filters.location') }}</span>
                    <input
                        v-model="form.location"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('gdelt.placeholders.location')"
                    />
                </label>

                <div class="col-span-full grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <label class="block min-w-0">
                        <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('gdelt.filters.dateFromRequired') }}</span>
                        <input
                            v-model="form.dateFrom"
                            type="date"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            required
                        />
                    </label>

                    <label class="block min-w-0">
                        <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">{{ t('gdelt.filters.dateToRequired') }}</span>
                        <input
                            v-model="form.dateTo"
                            type="date"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            required
                        />
                    </label>

                    <div class="flex flex-wrap items-end gap-2">
                        <button
                            type="button"
                            class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-input px-3 text-xs font-medium hover:bg-accent"
                            @click="applyPresetRange('1d')"
                        >
                            <CalendarDays class="h-3.5 w-3.5" />
                            {{ t('gdelt.presets.day1') }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md border border-input px-3 text-xs font-medium hover:bg-accent"
                            @click="applyPresetRange('7d')"
                        >
                            {{ t('gdelt.presets.day7') }}
                        </button>
                    </div>

                    <div class="flex items-center gap-3 rounded-md border border-input px-3 py-2">
                        <label class="inline-flex cursor-pointer items-center gap-2 text-xs">
                            <input v-model="form.onlyWithImage" type="checkbox" class="h-4 w-4 rounded border-input" />
                            {{ t('gdelt.filters.onlyWithImage') }}
                        </label>

                        <button
                            type="button"
                            class="ml-auto text-xs text-primary hover:underline"
                            @click="setDefaultWeekRange"
                        >
                            {{ t('gdelt.actions.resetWeek') }}
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="!searchPanelCollapsed && showQueryBuilder" class="mt-3 border-t border-border/60 pt-3">
                <label class="block min-w-0">
                    <span class="mb-1 block truncate text-xs font-medium text-muted-foreground">
                        {{ t('gdelt.filters.rawQuery') }}
                    </span>
                    <input
                        v-model="form.queryRaw"
                        type="text"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :placeholder="t('gdelt.placeholders.rawQuery')"
                    />
                </label>

                <div class="mt-2 rounded-md border border-border/70 bg-background/60 p-2 text-xs text-muted-foreground">
                    <p class="font-medium text-foreground">{{ t('gdelt.query.built') }}</p>
                    <p class="mt-1 break-all">{{ builtQueryPreview || '-' }}</p>
                </div>
            </div>

            <p v-if="error" class="mt-3 text-sm text-destructive">{{ error }}</p>
        </section>

        <section class="flex min-h-0 flex-1 flex-col rounded-xl border border-sidebar-border/80 bg-card/70 p-4 shadow-xl backdrop-blur">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-sm font-semibold">{{ t('gdelt.results.title') }}</h2>
                <p class="text-xs text-muted-foreground">
                    {{ t('gdelt.results.shown') }}: {{ shownCount }} / {{ total }}
                </p>
            </div>

            <div class="mb-3 rounded-md border border-border/70 bg-background/60 p-2 text-xs text-muted-foreground">
                <p>{{ t('gdelt.query.last') }}:</p>
                <p class="mt-1 break-all text-foreground">{{ lastQuery || '-' }}</p>
            </div>

            <div class="telegram-scroll min-h-0 flex-1 overflow-y-auto overscroll-contain pr-1">
                <div
                    v-if="!loading && filteredItems.length === 0"
                    class="rounded-md border border-dashed border-border p-6 text-center text-sm text-muted-foreground"
                >
                    {{ t('gdelt.results.empty') }}
                </div>

                <div v-else class="space-y-3">
                    <article
                        v-for="(item, index) in filteredItems"
                        :key="`${item.url}-${index}`"
                        class="rounded-lg border border-border/80 bg-background/70 p-3"
                    >
                        <div class="mb-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                            <span>{{ item.domain || '-' }}</span>
                            <span>{{ item.sourceCommonName || '-' }}</span>
                            <span>{{ t('gdelt.results.country') }}: {{ item.sourceCountry || '-' }}</span>
                            <span>{{ t('gdelt.results.language') }}: {{ item.language || '-' }}</span>
                            <span>{{ t('gdelt.results.tone') }}: {{ normalizeTone(item.tone) ?? '-' }}</span>
                            <span>{{ t('gdelt.results.seen') }}: {{ formatSeenDate(item.seenDate) }}</span>
                        </div>

                        <h3 class="text-sm font-semibold leading-relaxed text-foreground">
                            {{ item.title || t('gdelt.results.noTitle') }}
                        </h3>

                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <a
                                v-if="item.url"
                                :href="item.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="cursor-pointer rounded-full border border-input px-2 py-1 text-xs text-primary hover:bg-accent"
                            >
                                {{ t('gdelt.results.openSource') }}
                            </a>

                            <a
                                v-if="item.domain"
                                :href="`https://${item.domain}`"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="cursor-pointer rounded-full border border-input px-2 py-1 text-xs text-muted-foreground hover:bg-accent"
                            >
                                {{ t('gdelt.results.visitDomain') }}
                            </a>
                        </div>

                        <div
                            v-if="item.socialImage"
                            class="mt-3 overflow-hidden rounded-lg border border-border/70 bg-card/60 p-2"
                        >
                            <img
                                :src="item.socialImage"
                                :alt="item.title || 'social-image'"
                                class="max-h-72 w-full rounded-md object-cover"
                                loading="lazy"
                            />
                        </div>
                    </article>
                </div>
            </div>
        </section>
    </div>
</template>
