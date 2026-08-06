<script setup lang="ts" generic="TItem extends ParserHistoryItem">
import { Button } from '@/components/ui/button';
import { useI18n } from '@/composables/useI18n';
import type {
    ParserHistoryField,
    ParserHistoryItem,
    ParserHistoryStat,
} from './history';

const props = defineProps<{
    moduleKey: string;
    items: TItem[];
    loading: boolean;
    retentionDays: number;
    title: (item: TItem) => string | null;
    detailFields: ParserHistoryField<TItem>[];
    statFields: ParserHistoryStat<TItem>[];
}>();

const emit = defineEmits<{
    download: [item: TItem];
    downloadJson: [item: TItem];
}>();

const { t, locale } = useI18n();

const formatDateTime = (value: string | null) => {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat(locale.value, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const historyKey = (suffix: string) => `${props.moduleKey}.history.${suffix}`;

const historyStageLabel = (value: string | null) => {
    if (!value) {
        return t(historyKey('unknown'));
    }

    const key = `${props.moduleKey}.progress.stage.${value}`;
    const translated = t(key);

    return translated === key ? value : translated;
};
</script>

<template>
    <section
        class="flex max-h-[72vh] min-h-0 flex-col rounded-xl border border-border/70 bg-background/70 p-4"
    >
        <div
            class="flex flex-col gap-3 border-b border-border/70 pb-4 md:flex-row md:items-start md:justify-between"
        >
            <div class="space-y-1">
                <h3 class="text-base font-semibold">
                    {{ t(historyKey('title')) }}
                </h3>
                <p class="text-sm text-muted-foreground">
                    {{
                        t(historyKey('description'), {
                            days: retentionDays,
                        })
                    }}
                </p>
            </div>

            <div
                class="rounded-lg border border-border/70 bg-background/80 px-3 py-2 text-xs text-muted-foreground"
            >
                {{
                    t(historyKey('retention'), {
                        days: retentionDays,
                    })
                }}
            </div>
        </div>

        <div v-if="loading" class="py-6 text-sm text-muted-foreground">
            {{ t(historyKey('loading')) }}
        </div>

        <div
            v-else-if="items.length === 0"
            class="py-6 text-sm text-muted-foreground"
        >
            {{ t(historyKey('empty')) }}
        </div>

        <div
            v-else
            class="intel-scroll mt-4 min-h-0 flex-1 space-y-3 overflow-y-auto overscroll-contain pr-1 [scrollbar-gutter:stable]"
        >
            <article
                v-for="item in items"
                :key="item.runId"
                class="rounded-xl border border-border/70 bg-background/80 p-4"
            >
                <div
                    class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"
                >
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold">
                                {{ title(item) || t(historyKey('unknown')) }}
                            </span>
                            <span
                                class="rounded-full border border-border/70 bg-background px-2 py-0.5 text-[11px] text-muted-foreground uppercase"
                            >
                                {{ historyStageLabel(item.stage) }}
                            </span>
                        </div>

                        <div
                            class="grid gap-2 text-xs text-muted-foreground md:grid-cols-2 xl:grid-cols-4"
                        >
                            <div>
                                <span class="block">
                                    {{ t(historyKey('created')) }}
                                </span>
                                <span class="text-foreground">
                                    {{ formatDateTime(item.createdAt) }}
                                </span>
                            </div>
                            <div>
                                <span class="block">
                                    {{ t(historyKey('expires')) }}
                                </span>
                                <span class="text-foreground">
                                    {{ formatDateTime(item.expiresAt) }}
                                </span>
                            </div>
                            <div
                                v-for="field in detailFields"
                                :key="field.label"
                            >
                                <span class="block">{{ field.label }}</span>
                                <span class="text-foreground">
                                    {{ field.value(item) || '-' }}
                                </span>
                            </div>
                        </div>

                        <div
                            class="flex flex-wrap gap-2 text-xs text-muted-foreground"
                        >
                            <span
                                v-for="stat in statFields"
                                :key="stat.label"
                                class="rounded-md border border-border/70 bg-background px-2 py-1"
                            >
                                {{
                                    t(stat.label, {
                                        count: stat.value(item),
                                    })
                                }}
                            </span>
                        </div>

                        <p v-if="item.error" class="text-xs text-destructive">
                            {{ item.error }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="!item.downloadable"
                            @click="emit('download', item)"
                        >
                            {{ t(`${moduleKey}.download`) }}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="!item.downloadable"
                            @click="emit('downloadJson', item)"
                        >
                            {{ t(`${moduleKey}.downloadJson`) }}
                        </Button>
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>
