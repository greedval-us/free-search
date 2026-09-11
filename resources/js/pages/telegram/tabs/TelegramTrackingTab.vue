<script setup lang="ts">
import { Plus, RefreshCw } from 'lucide-vue-next';
import { nextTick, ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import { useI18n } from '@/composables/useI18n';
import TrackingCreateForm from '../tracking/TrackingCreateForm.vue';
import TrackingMessagesPanel from '../tracking/TrackingMessagesPanel.vue';
import TrackingPagination from '../tracking/TrackingPagination.vue';
import TrackingTaskCard from '../tracking/TrackingTaskCard.vue';
import type { TrackingTask } from '../tracking/types';
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
    groupsError,
    validatedGroups,
    validateGroups,
    create,
    change,
    show,
    refresh,
    clearSelection,
} = useTelegramTracking();
const createOpen = ref(false);
const results = ref<InstanceType<typeof TrackingMessagesPanel> | null>(null);

const openTask = async (task: TrackingTask) => {
    await show(task);

    if (selected.value?.id === task.id) {
        await nextTick();
        results.value?.focus();
    }
};
const backToList = async () => {
    const id = selected.value?.id;
    clearSelection();
    await nextTick();
    document.getElementById(`tracking-task-${id}`)?.focus();
};
const createTask = async () => {
    if (await create()) {
        createOpen.value = false;
    }
};
</script>

<template>
    <section
        class="flex min-h-0 min-w-0 flex-1 flex-col gap-3 overflow-hidden p-1"
        :aria-label="t('telegramTracking.title')"
        :aria-busy="busy"
    >
        <header
            class="flex shrink-0 flex-wrap items-center justify-between gap-3"
        >
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-semibold">
                        {{ t('telegramTracking.title') }}
                    </h2>
                    <HelpTooltip
                        v-if="list"
                        :label="t('telegramTracking.help')"
                        :text="`${t('telegramTracking.schedule', { hours: list.interval_hours, max: list.max_interval_hours })} ${t('telegramTracking.retention', { months: list.duration_months, days: list.retention_days })}`"
                    />
                </div>
                <p
                    v-if="list"
                    class="flex flex-wrap gap-x-3 text-xs text-muted-foreground"
                    role="status"
                >
                    <span>{{
                        t('telegramTracking.quota', {
                            used: list.active_count,
                            limit: list.limit,
                        })
                    }}</span>
                    <span class="text-primary">{{
                        t('telegramTracking.remaining', {
                            count: list.remaining,
                        })
                    }}</span>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="intel-button-secondary"
                    :disabled="busy"
                    :aria-label="t('telegramTracking.refresh')"
                    @click="refresh()"
                >
                    <RefreshCw
                        class="size-4"
                        :class="{ 'animate-spin': busy }"
                        aria-hidden="true"
                    />
                    <span class="hidden sm:inline">{{
                        t('telegramTracking.refresh')
                    }}</span>
                </button>
                <Dialog v-if="list" v-model:open="createOpen">
                    <DialogTrigger as-child>
                        <button
                            type="button"
                            class="intel-button-primary"
                            :disabled="busy"
                        >
                            <Plus class="size-4" aria-hidden="true" />{{
                                t('telegramTracking.new')
                            }}
                        </button>
                    </DialogTrigger>
                    <DialogContent class="intel-scroll sm:max-w-2xl">
                        <DialogHeader class="pr-10 text-left">
                            <DialogTitle>{{
                                t('telegramTracking.new')
                            }}</DialogTitle>
                            <DialogDescription>{{
                                t('telegramTracking.quota', {
                                    used: list.active_count,
                                    limit: list.limit,
                                })
                            }}</DialogDescription>
                        </DialogHeader>
                        <p
                            v-if="error"
                            role="alert"
                            class="text-sm text-destructive"
                        >
                            {{ error }}
                        </p>
                        <TrackingCreateForm
                            v-model="form"
                            :limits="list"
                            :busy="busy"
                            :validated-groups="validatedGroups"
                            :groups-error="groupsError"
                            @create="createTask"
                            @validate="validateGroups"
                        />
                    </DialogContent>
                </Dialog>
            </div>
        </header>
        <p
            v-if="error && !createOpen"
            role="alert"
            class="shrink-0 rounded-lg border border-destructive/40 bg-destructive/5 p-3 text-sm text-destructive"
        >
            {{ error }}
        </p>
        <p
            v-if="!list && !error"
            role="status"
            class="p-6 text-muted-foreground"
        >
            {{ t('telegramTracking.loading') }}
        </p>
        <div
            v-if="list"
            class="grid min-h-0 min-w-0 flex-1 gap-3 lg:grid-cols-[minmax(16rem,0.8fr)_minmax(0,1.6fr)]"
        >
            <section
                class="min-h-0 min-w-0 flex-col gap-3"
                :class="selected ? 'hidden lg:flex' : 'flex'"
                :aria-label="t('telegramTracking.tasks')"
            >
                <h3 class="shrink-0 text-sm font-semibold">
                    {{ t('telegramTracking.tasks') }}
                </h3>
                <div
                    class="intel-scroll min-h-0 flex-1 space-y-3 overflow-y-auto overscroll-contain p-1"
                >
                    <div
                        v-if="!list.items.length"
                        class="intel-panel-strong space-y-4 text-sm text-muted-foreground"
                    >
                        <p>{{ t('telegramTracking.empty') }}</p>
                        <button
                            type="button"
                            class="intel-button-secondary"
                            @click="createOpen = true"
                        >
                            {{ t('telegramTracking.new') }}
                        </button>
                    </div>
                    <TrackingTaskCard
                        v-for="task in list.items"
                        :key="task.id"
                        :task="task"
                        :busy="busy"
                        :selected="selected?.id === task.id"
                        @show="openTask"
                    />
                </div>
                <TrackingPagination
                    class="shrink-0"
                    :page="page"
                    :has-more="list.has_more"
                    :busy="busy"
                    @change="refresh"
                />
            </section>
            <TrackingMessagesPanel
                ref="results"
                :class="selected ? 'flex' : 'hidden lg:flex'"
                :selected="selected"
                :messages="messages"
                :page="messagePage"
                :has-more="hasMoreMessages"
                :busy="busy"
                @page="selected && show(selected, $event)"
                @change="change"
                @back="backToList"
            />
        </div>
    </section>
</template>
