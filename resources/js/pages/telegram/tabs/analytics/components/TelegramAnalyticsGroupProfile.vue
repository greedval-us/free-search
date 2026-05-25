<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import type { TelegramAnalyticsSummary } from '../../../types';

type GroupInfo = NonNullable<TelegramAnalyticsSummary['groupInfo']>;

defineProps<{
    group: GroupInfo;
    chatUsername: string;
}>();

const { t } = useI18n();

const groupTypeLabel = (type: GroupInfo['type']): string => {
    const map: Record<GroupInfo['type'], string> = {
        channel: t('telegram.analytics.group.types.channel'),
        group: t('telegram.analytics.group.types.group'),
        forum: t('telegram.analytics.group.types.forum'),
        gigagroup: t('telegram.analytics.group.types.gigagroup'),
        chat: t('telegram.analytics.group.types.chat'),
    };

    return map[type] ?? type;
};

const formatNumber = (value: number | null | undefined) => {
    const numeric = Number(value);

    return new Intl.NumberFormat().format(
        Number.isFinite(numeric) ? numeric : 0
    );
};

const formatDate = (unix: number) => {
    if (!unix) {
        return '-';
    }

    return new Date(unix * 1000).toLocaleString();
};
</script>

<template>
    <article class="intel-panel-strong">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h3 class="truncate text-sm font-semibold">
                    {{ group.title || chatUsername }}
                </h3>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ t('telegram.analytics.group.type') }}:
                    {{ groupTypeLabel(group.type) }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                <span
                    v-if="group.username"
                    class="rounded-full border border-border px-2 py-1"
                >
                    @{{ group.username }}
                </span>
                <span class="rounded-full border border-border px-2 py-1">
                    {{ t('telegram.analytics.group.participants') }}:
                    {{
                        group.participantsCount === null
                            ? '-'
                            : formatNumber(group.participantsCount)
                    }}
                </span>
                <span
                    v-if="group.onlineCount !== null"
                    class="rounded-full border border-border px-2 py-1"
                >
                    {{ t('telegram.analytics.group.online') }}:
                    {{ formatNumber(group.onlineCount) }}
                </span>
                <span
                    v-if="group.verified"
                    class="rounded-full border border-emerald-500/40 bg-emerald-500/10 px-2 py-1 text-emerald-300"
                >
                    {{ t('telegram.analytics.group.verified') }}
                </span>
            </div>
        </div>

        <p
            v-if="group.description"
            class="mt-3 line-clamp-3 text-sm text-muted-foreground"
        >
            {{ group.description }}
        </p>

        <div class="mt-3 flex flex-wrap gap-2 text-xs text-muted-foreground">
            <span
                v-if="group.createdAt"
                class="rounded-full border border-border px-2 py-1"
            >
                {{ t('telegram.analytics.group.createdAt') }}:
                {{ formatDate(group.createdAt) }}
            </span>
            <span
                v-if="group.canViewStats"
                class="rounded-full border border-border px-2 py-1"
            >
                {{ t('telegram.analytics.group.statsAvailable') }}
            </span>
            <span
                v-if="group.linkedChatId"
                class="rounded-full border border-border px-2 py-1"
            >
                {{ t('telegram.analytics.group.linkedChatId') }}:
                {{ formatNumber(group.linkedChatId) }}
            </span>
            <span
                v-if="group.restricted"
                class="rounded-full border border-amber-500/40 bg-amber-500/10 px-2 py-1 text-amber-300"
            >
                {{ t('telegram.analytics.group.restricted') }}
            </span>
            <span
                v-if="group.scam"
                class="rounded-full border border-red-500/40 bg-red-500/10 px-2 py-1 text-red-300"
            >
                {{ t('telegram.analytics.group.scam') }}
            </span>
        </div>
    </article>
</template>
