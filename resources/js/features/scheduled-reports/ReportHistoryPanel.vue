<script setup lang="ts" generic="TReport extends SavedReport">
import { Download, ExternalLink } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';
import { formatReportInstant } from './format';
import type { ReportFormat, ReportHistory, SavedReport } from './types';

defineProps<{
    history: ReportHistory<TReport>;
    namespace: string;
    busy: boolean;
    viewUrl: (id: number) => string;
    downloadUrl: (id: number, format: ReportFormat) => string;
}>();
defineEmits<{ page: [page: number] }>();
defineSlots<{
    target?: (props: { report: TReport }) => unknown;
    metadata?: (props: { report: TReport }) => unknown;
}>();
const { t, locale } = useI18n();
</script>

<template>
    <section
        class="flex min-w-0 flex-col gap-3"
        :aria-label="t(`${namespace}.history`)"
    >
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold">
                {{ t(`${namespace}.history`) }}
            </h3>
            <span class="text-xs text-muted-foreground">{{
                t(`${namespace}.total`, { count: history.total })
            }}</span>
        </div>
        <p
            v-if="!history.data.length"
            class="intel-panel-strong text-sm text-muted-foreground"
        >
            {{ t(`${namespace}.emptyHistory`) }}
        </p>
        <article
            v-for="report in history.data"
            :key="report.id"
            class="intel-panel-strong min-w-0 space-y-3"
        >
            <div class="flex min-w-0 items-start justify-between gap-3">
                <div class="min-w-0 space-y-1">
                    <h4 class="font-semibold [overflow-wrap:anywhere]">
                        {{
                            report.scheduleName ?? t(`${namespace}.savedReport`)
                        }}
                    </h4>
                    <p
                        class="text-sm [overflow-wrap:anywhere] text-muted-foreground"
                    >
                        <slot name="target" :report="report" />
                    </p>
                </div>
                <span
                    class="shrink-0 rounded-md border px-2 py-1 text-xs"
                    :class="{
                        'border-primary/40 text-primary':
                            report.status === 'completed',
                        'border-destructive/40 text-destructive':
                            report.status === 'failed',
                        'text-muted-foreground':
                            report.status === 'pending' ||
                            report.status === 'processing',
                    }"
                    >{{ t(`${namespace}.status.${report.status}`) }}</span
                >
            </div>
            <slot name="metadata" :report="report" />
            <p class="text-xs text-muted-foreground">
                {{ t(`${namespace}.scheduledFor`) }}:
                <time :datetime="report.scheduledFor">{{
                    formatReportInstant(report.scheduledFor, locale)
                }}</time>
            </p>
            <p
                v-if="report.status === 'failed'"
                class="text-sm text-destructive"
            >
                {{ report.errorMessage || t(`${namespace}.reportFailed`) }}
            </p>
            <div
                v-if="report.status === 'completed'"
                class="flex flex-wrap gap-2"
            >
                <a
                    :href="viewUrl(report.id)"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="intel-button-primary"
                >
                    <ExternalLink class="size-4" aria-hidden="true" />{{
                        t(`${namespace}.view`)
                    }}
                </a>
                <a
                    :href="downloadUrl(report.id, 'html')"
                    class="intel-button-secondary"
                >
                    <Download class="size-4" aria-hidden="true" />{{
                        t(`${namespace}.downloadHtml`)
                    }}
                </a>
                <a
                    :href="downloadUrl(report.id, 'json')"
                    class="intel-button-secondary"
                >
                    <Download class="size-4" aria-hidden="true" />{{
                        t(`${namespace}.downloadJson`)
                    }}
                </a>
            </div>
        </article>
        <nav
            v-if="history.lastPage > 1"
            class="flex flex-wrap items-center gap-2"
            :aria-label="t(`${namespace}.historyPages`)"
        >
            <button
                type="button"
                class="intel-button-secondary"
                :disabled="busy || history.currentPage <= 1"
                @click="$emit('page', history.currentPage - 1)"
            >
                {{ t(`${namespace}.previous`) }}
            </button>
            <span class="text-xs text-muted-foreground">{{
                t(`${namespace}.page`, {
                    page: history.currentPage,
                    last: history.lastPage,
                })
            }}</span>
            <button
                type="button"
                class="intel-button-secondary"
                :disabled="busy || history.currentPage >= history.lastPage"
                @click="$emit('page', history.currentPage + 1)"
            >
                {{ t(`${namespace}.next`) }}
            </button>
        </nav>
    </section>
</template>
