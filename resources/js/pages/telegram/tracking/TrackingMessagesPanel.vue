<script setup lang="ts">
import { ArrowLeft } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { useI18n } from '@/composables/useI18n';
import TrackingPagination from './TrackingPagination.vue';
import TrackingTaskDetails from './TrackingTaskDetails.vue';
import type { TrackingAction, TrackingMessage, TrackingTask } from './types';

const props = defineProps<{
    selected: TrackingTask | null;
    messages: TrackingMessage[];
    page: number;
    hasMore: boolean;
    busy: boolean;
}>();
defineEmits<{
    page: [page: number];
    back: [];
    change: [task: TrackingTask, action: TrackingAction];
}>();
const { t, locale } = useI18n();
const date = (value: string) => new Date(value).toLocaleString(locale.value);
const heading = ref<HTMLHeadingElement | null>(null);
const body = ref<HTMLDivElement | null>(null);
watch(
    [() => props.selected?.id, () => props.page],
    () => {
        if (body.value) {
            body.value.scrollTop = 0;
        }
    },
    { flush: 'post' }
);
defineExpose({ focus: () => heading.value?.focus() });
</script>

<template>
    <section
        class="intel-panel-strong min-h-0 min-w-0 flex-col gap-3 overflow-hidden"
        :aria-label="t('telegramTracking.results')"
    >
        <header
            class="flex shrink-0 items-center gap-2 border-b border-border/60 pb-3"
        >
            <button
                v-if="selected"
                type="button"
                class="intel-button-secondary shrink-0 lg:hidden"
                :disabled="busy"
                :aria-label="t('telegramTracking.backToTasks')"
                @click="$emit('back')"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
            </button>
            <h3
                ref="heading"
                tabindex="-1"
                class="min-w-0 font-semibold [overflow-wrap:anywhere] focus:outline-none"
            >
                {{ selected?.name ?? t('telegramTracking.results') }}
            </h3>
        </header>
        <div
            ref="body"
            class="intel-scroll min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain pr-1"
        >
            <p v-if="!selected" class="text-sm text-muted-foreground">
                {{ t('telegramTracking.select') }}
            </p>
            <template v-else>
                <TrackingTaskDetails
                    :key="selected.id"
                    :task="selected"
                    :busy="busy"
                    @change="(task, action) => $emit('change', task, action)"
                />
                <h4
                    class="border-t border-border/60 pt-4 text-sm font-semibold"
                >
                    {{
                        t('telegramTracking.messages', {
                            count: selected.messages_count,
                        })
                    }}
                </h4>
                <p
                    v-if="!messages.length"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('telegramTracking.noMessages') }}
                </p>
                <article
                    v-for="message in messages"
                    :key="message.id"
                    class="min-w-0 space-y-2 rounded-lg border border-border/70 p-3 [overflow-wrap:anywhere]"
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
            </template>
        </div>
        <TrackingPagination
            v-if="selected"
            class="shrink-0"
            :page="page"
            :has-more="hasMore"
            :busy="busy"
            @change="$emit('page', $event)"
        />
    </section>
</template>
