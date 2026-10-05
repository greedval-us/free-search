<script setup lang="ts" generic="TItem extends ParserHistoryItem">
import { Download, History, LoaderCircle } from 'lucide-vue-next';
import { useId } from 'vue';
import { Button } from '@/components/ui/button';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useI18n } from '@/composables/useI18n';
import type {
    ParserHistoryConfig,
    ParserHistoryField,
    ParserHistoryItem,
} from './history';
import ParserStatusBadge from './ParserStatusBadge.vue';
import {
    boundedProgress,
    formatParserDateTime,
    isPartialParserExport,
} from './presentation';

const props = defineProps<{
    config: ParserHistoryConfig<TItem>;
    items: TItem[];
    loading: boolean;
    retentionDays: number;
}>();

const emit = defineEmits<{
    download: [item: TItem];
    downloadJson: [item: TItem];
}>();

const { t, locale } = useI18n();
const headingId = useId();

const title = (item: TItem) => {
    const value = item[props.config.title.key];

    return value
        ? `${props.config.title.prefix ?? ''}${value}`
        : t('parser.history.unknown');
};

const fieldValue = (item: TItem, field: ParserHistoryField<TItem>) => {
    const value = item[field.key];

    if (!value) {
        return t('parser.history.unavailable');
    }

    if (field.translationPrefix) {
        const key = `${field.translationPrefix}.${value}`;
        const translated = t(key);

        return translated === key ? String(value) : translated;
    }

    return String(value);
};

const stageLabel = (stage: string) => {
    const key = `${props.config.moduleKey}.progress.stage.${stage}`;
    const translated = t(key);

    return translated === key ? stage : translated;
};

const formatDate = (value: string | null) =>
    formatParserDateTime(value, locale.value) ??
    t('parser.history.unavailable');
</script>

<template>
    <section
        class="intel-panel flex min-h-0 flex-col sm:max-h-[72vh]"
        :aria-labelledby="headingId"
        :aria-busy="loading"
    >
        <header
            class="flex flex-wrap items-start justify-between gap-3 border-b border-border/60 pb-3"
        >
            <div class="min-w-0 basis-full space-y-1 sm:flex-1 sm:basis-auto">
                <h3
                    :id="headingId"
                    class="flex items-center gap-2 text-sm font-semibold"
                >
                    <History
                        class="size-4 shrink-0 text-muted-foreground"
                        aria-hidden="true"
                    />
                    {{ t('parser.history.title') }}
                    <span
                        class="rounded-md bg-muted/60 px-1.5 py-0.5 text-xs font-medium text-muted-foreground tabular-nums"
                        :aria-label="
                            t('parser.history.count', { count: items.length })
                        "
                        >{{ items.length }}</span
                    >
                </h3>
                <p class="intel-caption">
                    {{
                        t('parser.history.description', { days: retentionDays })
                    }}
                </p>
            </div>
            <span
                class="intel-caption self-start rounded-md bg-muted/35 px-2 py-1"
            >
                {{ t('parser.history.retention', { days: retentionDays }) }}
            </span>
        </header>

        <div
            v-if="loading"
            class="flex items-center gap-2 py-3 text-xs text-muted-foreground"
            role="status"
        >
            <LoaderCircle
                class="size-3.5 animate-spin motion-reduce:animate-none"
                aria-hidden="true"
            />
            {{ t('parser.history.loading') }}
        </div>

        <EmptyState
            v-if="!loading && items.length === 0"
            class="mt-3"
            :text="t('parser.history.empty')"
        />

        <TransitionGroup
            v-if="items.length > 0"
            tag="ol"
            name="parser-history"
            appear
            class="intel-scroll mt-3 min-h-0 min-w-0 flex-1 space-y-2 overflow-y-visible overscroll-contain sm:overflow-y-auto sm:pr-1 sm:[scrollbar-gutter:stable]"
        >
            <li
                v-for="item in items"
                :key="item.runId"
                class="intel-surface min-w-0"
            >
                <article class="space-y-3">
                    <div
                        class="flex min-w-0 flex-wrap items-start justify-between gap-2"
                    >
                        <div class="min-w-0 basis-full sm:flex-1">
                            <h4
                                class="text-sm leading-5 font-semibold break-words"
                            >
                                {{ title(item) }}
                            </h4>
                            <p
                                v-if="item.stage && item.stage !== item.status"
                                class="intel-caption mt-1 flex flex-wrap items-center gap-x-2 gap-y-1"
                            >
                                <span>{{ stageLabel(item.stage) }}</span>
                                <span
                                    v-if="item.status === 'running'"
                                    class="tabular-nums"
                                    >{{
                                        Math.round(
                                            boundedProgress(item.progress)
                                        )
                                    }}%</span
                                >
                            </p>
                        </div>
                        <div
                            class="flex max-w-full flex-wrap items-center gap-2"
                        >
                            <span
                                v-if="isPartialParserExport(item)"
                                class="text-xs font-medium text-amber-800 dark:text-amber-300"
                                >{{ t('parser.history.partial') }}</span
                            >
                            <ParserStatusBadge :status="item.status" />
                        </div>
                    </div>

                    <dl
                        class="grid min-w-0 gap-x-4 gap-y-2 text-xs sm:grid-cols-2 xl:grid-cols-4"
                    >
                        <div class="min-w-0">
                            <dt class="text-muted-foreground">
                                {{ t('parser.history.created') }}
                            </dt>
                            <dd class="mt-0.5 font-medium break-words">
                                {{ formatDate(item.createdAt) }}
                            </dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-muted-foreground">
                                {{ t('parser.history.expires') }}
                            </dt>
                            <dd class="mt-0.5 font-medium break-words">
                                {{ formatDate(item.expiresAt) }}
                            </dd>
                        </div>
                        <div
                            v-for="field in config.details"
                            :key="field.key"
                            class="min-w-0"
                        >
                            <dt class="text-muted-foreground">
                                {{ t(field.label) }}
                            </dt>
                            <dd class="mt-0.5 font-medium break-words">
                                {{ fieldValue(item, field) }}
                            </dd>
                        </div>
                    </dl>

                    <div
                        class="flex flex-wrap gap-1.5 text-xs text-muted-foreground"
                    >
                        <span
                            v-for="stat in config.stats"
                            :key="stat.key"
                            class="max-w-full rounded-md bg-muted/40 px-2 py-1 break-words tabular-nums"
                            >{{
                                t(stat.label, {
                                    count: item[stat.key] as number | string,
                                })
                            }}</span
                        >
                    </div>

                    <p
                        v-if="item.error"
                        class="rounded-md bg-destructive/5 px-2.5 py-2 text-xs leading-relaxed break-words text-destructive"
                    >
                        {{ item.error }}
                    </p>

                    <div
                        class="grid gap-2 border-t border-border/50 pt-2.5 sm:flex sm:flex-wrap"
                        role="group"
                        :aria-label="t('parser.actions.exports')"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="motion-reduce:transition-none"
                            :disabled="!item.downloadable || !item.downloadUrl"
                            :aria-label="
                                t('parser.actions.downloadExcel', {
                                    title: title(item),
                                })
                            "
                            @click="emit('download', item)"
                        >
                            <Download class="size-3.5" aria-hidden="true" />
                            Excel
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="motion-reduce:transition-none"
                            :disabled="
                                !item.downloadable || !item.downloadJsonUrl
                            "
                            :aria-label="
                                t('parser.actions.downloadJson', {
                                    title: title(item),
                                })
                            "
                            @click="emit('downloadJson', item)"
                        >
                            <Download class="size-3.5" aria-hidden="true" />
                            JSON
                        </Button>
                    </div>
                </article>
            </li>
        </TransitionGroup>
    </section>
</template>

<style scoped>
.parser-history-enter-active,
.parser-history-leave-active,
.parser-history-move {
    transition:
        opacity 180ms ease,
        transform 180ms ease;
}

.parser-history-enter-from,
.parser-history-leave-to {
    opacity: 0;
    transform: translateY(4px);
}

@media (prefers-reduced-motion: reduce) {
    .parser-history-enter-active,
    .parser-history-leave-active,
    .parser-history-move {
        transition: none;
    }

    .parser-history-enter-from,
    .parser-history-leave-to {
        transform: none;
    }
}
</style>
