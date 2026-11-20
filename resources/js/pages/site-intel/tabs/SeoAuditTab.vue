<script setup lang="ts">
import { LoaderCircle, SearchCheck } from 'lucide-vue-next';
import IntelSearchFormComplex from '@/components/ui/IntelSearchFormComplex.vue';
import IntelSearchPanel from '@/components/ui/IntelSearchPanel.vue';
import { useI18n } from '@/composables/useI18n';
import { useSeoAudit } from '../composables/useSeoAudit';
import {
    SEO_AUDIT_CRAWL_PRESETS,
    SEO_AUDIT_PLATFORMS,
} from '../lib/seo-audit-options';
import SeoAuditHint from './components/SeoAuditHint.vue';
import SeoAuditResults from './components/SeoAuditResults.vue';

const { t } = useI18n();
const {
    form,
    loading,
    error,
    result,
    canAnalyze,
    canUseReportActions,
    analyze,
    openReport,
    downloadReport,
} = useSeoAudit(t);
</script>

<template>
    <IntelSearchPanel>
        <div class="space-y-1">
            <div class="flex items-center gap-2 text-sm font-semibold">
                <SearchCheck class="h-4 w-4 text-cyan-400" />
                <span>{{ t('siteIntel.seoAudit.title') }}</span>
                <SeoAuditHint :text="t('siteIntel.seoAudit.help.overview')" />
            </div>
            <p class="text-xs text-muted-foreground">
                {{ t('siteIntel.seoAudit.description') }}
            </p>
        </div>

        <IntelSearchFormComplex :error="error">
            <label class="block min-w-0 flex-1">
                <span
                    class="mb-1 block text-xs font-medium break-words text-muted-foreground"
                    >{{ t('siteIntel.seoAudit.target') }}</span
                >
                <input
                    v-model="form.target"
                    type="text"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    :placeholder="t('siteIntel.seoAudit.placeholder')"
                    @keydown.enter.prevent="analyze"
                />
            </label>
            <label class="block w-full sm:w-44">
                <span
                    class="mb-1 block text-xs font-medium break-words text-muted-foreground"
                    >{{ t('siteIntel.seoAudit.crawlLimit') }}</span
                >
                <select
                    v-model.number="form.crawlLimit"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option
                        v-for="limit in SEO_AUDIT_CRAWL_PRESETS"
                        :key="limit"
                        :value="limit"
                    >
                        {{ limit }}
                    </option>
                </select>
            </label>
            <label class="block w-full sm:w-52">
                <span
                    class="mb-1 flex min-w-0 items-center gap-1 text-xs font-medium text-muted-foreground"
                >
                    <span>{{ t('siteIntel.seoAudit.platformType') }}</span>
                    <span class="group relative inline-flex">
                        <span
                            class="inline-flex h-4 w-4 cursor-help items-center justify-center rounded-full border border-border text-[10px] font-semibold text-muted-foreground outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            :aria-label="t('siteIntel.help.label')"
                            role="button"
                            tabindex="0"
                            >?</span
                        >
                        <span
                            class="pointer-events-none absolute top-5 right-0 z-20 hidden w-[min(20rem,calc(100vw-2rem))] rounded-md border border-border/70 bg-popover p-2 text-[11px] leading-relaxed break-words text-popover-foreground shadow-xl group-focus-within:block group-hover:block"
                            >{{
                                t('siteIntel.seoAudit.help.platformType')
                            }}</span
                        >
                    </span>
                </span>
                <select
                    v-model="form.platformType"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option
                        v-for="platform in SEO_AUDIT_PLATFORMS"
                        :key="platform.value"
                        :value="platform.value"
                    >
                        {{ t(platform.label) }}
                    </option>
                </select>
            </label>

            <button
                :disabled="loading || !canAnalyze"
                class="inline-flex h-10 cursor-pointer items-center gap-2 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-60"
                @click="analyze"
            >
                <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                <span>{{
                    loading
                        ? t('siteIntel.seoAudit.analyzing')
                        : t('siteIntel.seoAudit.analyze')
                }}</span>
            </button>
            <button
                type="button"
                :disabled="!canUseReportActions"
                class="inline-flex h-10 cursor-pointer items-center rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                @click="openReport"
            >
                {{ t('siteIntel.seoAudit.report') }}
            </button>
            <button
                type="button"
                :disabled="!canUseReportActions"
                class="inline-flex h-10 cursor-pointer items-center rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent disabled:cursor-not-allowed disabled:opacity-60"
                @click="downloadReport"
            >
                {{ t('siteIntel.seoAudit.downloadReport') }}
            </button>
        </IntelSearchFormComplex>
    </IntelSearchPanel>

    <SeoAuditResults :result="result" />
</template>
