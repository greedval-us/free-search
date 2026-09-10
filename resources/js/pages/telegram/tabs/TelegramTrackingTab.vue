<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import TrackingCreateForm from '../tracking/TrackingCreateForm.vue';
import TrackingMessagesPanel from '../tracking/TrackingMessagesPanel.vue';
import TrackingPagination from '../tracking/TrackingPagination.vue';
import TrackingTaskCard from '../tracking/TrackingTaskCard.vue';
import { useTelegramTracking } from '../tracking/useTelegramTracking';

const { t } = useI18n();
const {
    list,
    page,
    selected,
    messages,
    messagePage,
    hasMoreMessages,
    busy,
    error,
    form,
    validatedGroups,
    validateGroups,
    create,
    change,
    show,
    refresh,
} = useTelegramTracking();
</script>

<template>
    <section
        class="min-h-0 min-w-0 flex-1 overflow-y-auto overscroll-contain p-1"
        :aria-label="t('telegramTracking.title')"
        :aria-busy="busy"
    >
        <div class="space-y-5 pb-6">
            <header class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold">
                        {{ t('telegramTracking.title') }}
                    </h2>
                    <p v-if="list" class="mt-1 text-sm text-muted-foreground">
                        {{
                            t('telegramTracking.schedule', {
                                hours: list.interval_hours,
                                max: list.max_interval_hours,
                            })
                        }}
                    </p>
                </div>
                <button
                    class="intel-button-secondary"
                    :disabled="busy"
                    @click="refresh()"
                >
                    {{ t('telegramTracking.refresh') }}
                </button>
            </header>
            <p
                v-if="error"
                role="alert"
                class="rounded-lg border border-destructive/40 bg-destructive/5 p-3 text-sm text-destructive"
            >
                {{ error }}
            </p>
            <div
                v-if="!list && !error"
                role="status"
                class="p-6 text-muted-foreground"
            >
                {{ t('telegramTracking.loading') }}
            </div>
            <template v-if="list">
                <TrackingCreateForm
                    v-model="form"
                    :limits="list"
                    :busy="busy"
                    :validated-groups="validatedGroups"
                    @create="create"
                    @validate="validateGroups"
                />
                <div class="grid min-w-0 gap-5 xl:grid-cols-2">
                    <div class="min-w-0 space-y-4">
                        <h3 class="font-semibold">
                            {{ t('telegramTracking.tasks') }}
                        </h3>
                        <p
                            v-if="!list.items.length"
                            class="intel-panel-strong p-6 text-sm text-muted-foreground"
                        >
                            {{ t('telegramTracking.empty') }}
                        </p>
                        <TrackingTaskCard
                            v-for="task in list.items"
                            :key="task.id"
                            :task="task"
                            :busy="busy"
                            :selected="selected?.id === task.id"
                            @change="change"
                            @show="show"
                        />
                        <TrackingPagination
                            :page="page"
                            :has-more="list.has_more"
                            :busy="busy"
                            @change="refresh"
                        />
                    </div>
                    <TrackingMessagesPanel
                        :selected="selected"
                        :messages="messages"
                        :page="messagePage"
                        :has-more="hasMoreMessages"
                        :busy="busy"
                        @page="selected && show(selected, $event)"
                    />
                </div>
            </template>
        </div>
    </section>
</template>
