<script setup lang="ts">
import { reactive } from 'vue';
import IntelResultPanel from '@/components/ui/IntelResultPanel.vue';
import { useI18n } from '@/composables/useI18n';
import type { SiteIntelSeoAuditResult } from '../../types';
import SeoAuditCrawlDetails from './SeoAuditCrawlDetails.vue';
import SeoAuditFindings from './SeoAuditFindings.vue';
import SeoAuditLinkGraph from './SeoAuditLinkGraph.vue';
import SeoAuditPageSummary from './SeoAuditPageSummary.vue';

defineProps<{ result: SiteIntelSeoAuditResult | null }>();
const { t } = useI18n();
const linkGraphFilters = reactive({
    non200Only: false,
    noindexOnly: false,
    orphanRiskOnly: false,
    mode: 'all' as 'any' | 'all',
});
</script>

<template>
    <IntelResultPanel>
        <div v-if="!result" class="intel-empty">
            {{ t('siteIntel.seoAudit.empty') }}
        </div>

        <div
            v-else
            class="intel-scroll min-h-0 flex-1 space-y-3 overflow-y-auto pr-1 text-xs"
        >
            <SeoAuditPageSummary :result="result" />
            <SeoAuditFindings :result="result" />
            <SeoAuditCrawlDetails :result="result" />
            <div class="space-y-2">
                <p class="text-sm font-semibold">
                    {{ t('siteIntel.seoAudit.linkGraphPanelTitle') }}
                </p>
                <div class="flex flex-wrap gap-3 text-xs text-muted-foreground">
                    <label class="inline-flex items-center gap-2">
                        <span>{{ t('siteIntel.seoAudit.filterMode') }}</span>
                        <select
                            v-model="linkGraphFilters.mode"
                            class="h-7 rounded border border-input bg-background px-2 text-xs"
                        >
                            <option value="all">
                                {{ t('siteIntel.seoAudit.filterModeAll') }}
                            </option>
                            <option value="any">
                                {{ t('siteIntel.seoAudit.filterModeAny') }}
                            </option>
                        </select>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input
                            v-model="linkGraphFilters.non200Only"
                            type="checkbox"
                            class="h-4 w-4"
                        />
                        <span>{{
                            t('siteIntel.seoAudit.filterNon200Only')
                        }}</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input
                            v-model="linkGraphFilters.noindexOnly"
                            type="checkbox"
                            class="h-4 w-4"
                        />
                        <span>{{
                            t('siteIntel.seoAudit.filterNoindexOnly')
                        }}</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input
                            v-model="linkGraphFilters.orphanRiskOnly"
                            type="checkbox"
                            class="h-4 w-4"
                        />
                        <span>{{
                            t('siteIntel.seoAudit.filterOrphanOnly')
                        }}</span>
                    </label>
                </div>
                <SeoAuditLinkGraph
                    :nodes="result.crawl.linkGraph?.nodes ?? []"
                    :edges="result.crawl.linkGraph?.edges ?? []"
                    :filters="linkGraphFilters"
                />
                <div
                    class="flex flex-wrap gap-2 text-[11px] text-muted-foreground"
                >
                    <span
                        class="inline-flex items-center gap-1 rounded-full border border-input px-2 py-1"
                    >
                        <span class="h-2 w-2 rounded-full bg-cyan-500" />
                        {{ t('siteIntel.seoAudit.legendIndexable') }}
                    </span>
                    <span
                        class="inline-flex items-center gap-1 rounded-full border border-input px-2 py-1"
                    >
                        <span class="h-2 w-2 rounded-full bg-amber-500" />
                        {{ t('siteIntel.seoAudit.legendNoindex') }}
                    </span>
                    <span
                        class="inline-flex items-center gap-1 rounded-full border border-input px-2 py-1"
                    >
                        <span class="h-2 w-2 rounded-full bg-red-500" />
                        {{ t('siteIntel.seoAudit.legendNon200') }}
                    </span>
                    <span
                        class="inline-flex items-center gap-1 rounded-full border border-input px-2 py-1"
                    >
                        <span class="h-2 w-2 rounded-full bg-violet-500" />
                        {{ t('siteIntel.seoAudit.legendOrphan') }}
                    </span>
                </div>
            </div>
        </div>
    </IntelResultPanel>
</template>
