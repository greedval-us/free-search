<script setup lang="ts">
import { Download, ExternalLink } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';
import type { ReportHistory } from './types';

defineProps<{
    history: ReportHistory;
    busy: boolean;
    viewUrl: (id: number) => string;
    downloadUrl: (id: number, format: 'html' | 'json') => string;
}>();
defineEmits<{ page: [page: number] }>();
const { t, locale } = useI18n();
const date = (value: string) => new Date(value).toLocaleString(locale.value);
const rangeDate = (value: string) =>
    new Date(`${value.slice(0, 10)}T00:00:00Z`).toLocaleDateString(
        locale.value,
        { timeZone: 'UTC' }
    );
</script>

<template>
    <section
        class="flex min-w-0 flex-col gap-3"
        :aria-label="t('mastodonReports.history')"
    >
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold">
                {{ t('mastodonReports.history') }}
            </h3>
            <span class="text-xs text-muted-foreground">{{
                t('mastodonReports.total', { count: history.total })
            }}</span>
        </div>
        <p
            v-if="!history.data.length"
            class="intel-panel-strong text-sm text-muted-foreground"
        >
            {{ t('mastodonReports.emptyHistory') }}
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
                            report.scheduleName ??
                            t('mastodonReports.savedReport')
                        }}
                    </h4>
                    <p
                        class="text-sm [overflow-wrap:anywhere] text-muted-foreground"
                    >
                        {{ report.accountInput }}
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
                    >{{ t(`mastodonReports.status.${report.status}`) }}</span
                >
            </div>
            <p class="text-xs text-muted-foreground">
                {{ t('mastodonReports.reportPeriod') }}:
                {{ rangeDate(report.dateFrom) }} —
                {{ rangeDate(report.dateTo) }}
            </p>
            <p class="text-xs text-muted-foreground">
                {{ t('mastodonReports.scheduledFor') }}:
                <time :datetime="report.scheduledFor">{{
                    date(report.scheduledFor)
                }}</time>
            </p>
            <p
                v-if="report.status === 'failed'"
                class="text-sm text-destructive"
            >
                {{ report.errorMessage || t('mastodonReports.reportFailed') }}
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
                        t('mastodonReports.view')
                    }}
                </a>
                <a
                    :href="downloadUrl(report.id, 'html')"
                    class="intel-button-secondary"
                >
                    <Download class="size-4" aria-hidden="true" />{{
                        t('mastodonReports.downloadHtml')
                    }}
                </a>
                <a
                    :href="downloadUrl(report.id, 'json')"
                    class="intel-button-secondary"
                >
                    <Download class="size-4" aria-hidden="true" />{{
                        t('mastodonReports.downloadJson')
                    }}
                </a>
            </div>
        </article>
        <nav
            v-if="history.lastPage > 1"
            class="flex flex-wrap items-center gap-2"
            :aria-label="t('mastodonReports.historyPages')"
        >
            <button
                type="button"
                class="intel-button-secondary"
                :disabled="busy || history.currentPage <= 1"
                @click="$emit('page', history.currentPage - 1)"
            >
                {{ t('mastodonReports.previous') }}
            </button>
            <span class="text-xs text-muted-foreground">{{
                t('mastodonReports.page', {
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
                {{ t('mastodonReports.next') }}
            </button>
        </nav>
    </section>
</template>
