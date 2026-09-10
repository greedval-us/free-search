<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import TrackingPagination from './TrackingPagination.vue';
import type { TrackingMessage, TrackingTask } from './types';

defineProps<{
    selected: TrackingTask | null;
    messages: TrackingMessage[];
    page: number;
    hasMore: boolean;
    busy: boolean;
}>();
defineEmits<{ page: [page: number] }>();
const { t, locale } = useI18n();
const date = (value: string) => new Date(value).toLocaleString(locale.value);
</script>

<template>
    <section
        class="intel-panel-strong min-w-0 self-start p-4"
        :aria-label="t('telegramTracking.results')"
    >
        <h3 class="mb-4 font-semibold break-words">
            {{ selected?.name ?? t('telegramTracking.results') }}
        </h3>
        <p v-if="!selected" class="text-sm text-muted-foreground">
            {{ t('telegramTracking.select') }}
        </p>
        <p v-else-if="!messages.length" class="text-sm text-muted-foreground">
            {{ t('telegramTracking.noMessages') }}
        </p>
        <div
            class="max-h-[65dvh] space-y-3 overflow-y-auto overscroll-contain pr-1"
        >
            <article
                v-for="message in messages"
                :key="message.id"
                class="space-y-2 rounded-lg border border-border/70 p-3"
            >
                <div class="text-sm font-medium break-words">
                    {{ message.group }}
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ date(message.sent_at) }} ·
                    {{ t('telegramTracking.sender') }}:
                    {{ message.sender_id ?? t('telegramTracking.unknown') }}
                </p>
                <p
                    class="text-sm [overflow-wrap:anywhere] break-words whitespace-pre-wrap"
                >
                    {{ message.text || t('telegramTracking.mediaOnly') }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ t('telegramTracking.received') }}:
                    {{ date(message.received_at) }}
                </p>
                <a
                    v-if="message.url"
                    :href="message.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex min-h-9 items-center text-sm text-primary underline"
                    >{{ t('telegramTracking.openMessage') }}</a
                >
            </article>
        </div>
        <TrackingPagination
            v-if="selected"
            class="mt-3"
            :page="page"
            :has-more="hasMore"
            :busy="busy"
            @change="$emit('page', $event)"
        />
    </section>
</template>
