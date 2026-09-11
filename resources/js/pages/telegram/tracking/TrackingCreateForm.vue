<script setup lang="ts">
import { computed } from 'vue';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import { useI18n } from '@/composables/useI18n';
import type { TrackingForm, TrackingList } from './types';

const props = defineProps<{
    limits: TrackingList;
    busy: boolean;
    validatedGroups: string[];
    groupsError: string;
}>();
defineEmits<{ create: []; validate: [] }>();
const form = defineModel<TrackingForm>({ required: true });
const { t } = useI18n();
const canCreate = computed(
    () =>
        props.limits.remaining > 0 &&
        form.value.name.trim().length > 0 &&
        form.value.groups.trim().length > 0 &&
        !props.groupsError &&
        (form.value.mode === 'user'
            ? /^[1-9]\d{0,18}$/.test(form.value.query.trim())
            : [...form.value.query.trim()].length >=
              props.limits.keyword_min_length)
);
</script>

<template>
    <form class="min-w-0 space-y-4" @submit.prevent="$emit('create')">
        <p
            v-if="!limits.remaining"
            role="status"
            class="rounded-lg border border-border p-3 text-sm text-muted-foreground"
        >
            {{ t('telegramTracking.limitReached') }}
        </p>
        <div class="intel-field">
            <label for="tracking-name" class="intel-label">{{
                t('telegramTracking.name')
            }}</label>
            <input
                id="tracking-name"
                v-model="form.name"
                class="intel-input"
                maxlength="100"
                required
            />
        </div>
        <div class="grid min-w-0 gap-4 sm:grid-cols-2">
            <div class="intel-field min-w-0">
                <label
                    for="tracking-mode"
                    class="intel-label flex min-h-8 items-center"
                    >{{ t('telegramTracking.mode') }}</label
                >
                <select
                    id="tracking-mode"
                    v-model="form.mode"
                    class="intel-select"
                >
                    <option value="keyword">
                        {{ t('telegramTracking.modes.keyword') }}
                    </option>
                    <option value="user">
                        {{ t('telegramTracking.modes.user') }}
                    </option>
                </select>
            </div>
            <div class="intel-field min-w-0">
                <div class="flex items-center justify-between gap-1">
                    <label for="tracking-query" class="intel-label">{{
                        t(`telegramTracking.modes.${form.mode}`)
                    }}</label>
                    <HelpTooltip
                        :label="t(`telegramTracking.modes.${form.mode}`)"
                        :text="
                            form.mode === 'keyword'
                                ? t('telegramTracking.matchHelp', {
                                      min: limits.keyword_min_length,
                                  })
                                : t('telegramTracking.userHelp')
                        "
                    />
                </div>
                <input
                    id="tracking-query"
                    v-model="form.query"
                    class="intel-input"
                    :maxlength="form.mode === 'user' ? 19 : 120"
                    :minlength="
                        form.mode === 'keyword' ? limits.keyword_min_length : 1
                    "
                    :inputmode="form.mode === 'user' ? 'numeric' : 'text'"
                    required
                />
            </div>
        </div>
        <div class="intel-field">
            <div class="flex items-center justify-between gap-2">
                <label for="tracking-groups" class="intel-label">{{
                    t('telegramTracking.groups', { max: limits.max_sources })
                }}</label>
                <HelpTooltip
                    :label="
                        t('telegramTracking.groups', {
                            max: limits.max_sources,
                        })
                    "
                    :text="t('telegramTracking.noJoin')"
                />
            </div>
            <textarea
                id="tracking-groups"
                v-model="form.groups"
                class="intel-scroll intel-input min-h-24 resize-y"
                :aria-invalid="Boolean(groupsError)"
                :aria-describedby="
                    groupsError ? 'tracking-groups-error' : undefined
                "
                rows="3"
                :placeholder="t('telegramTracking.groupsPlaceholder')"
                @keydown.space.prevent
                required
            />
            <span
                v-if="groupsError"
                id="tracking-groups-error"
                role="alert"
                class="text-xs text-destructive"
                >{{ groupsError }}</span
            >
        </div>
        <p
            v-if="validatedGroups.length"
            role="status"
            class="text-sm [overflow-wrap:anywhere] text-primary"
        >
            {{ t('telegramTracking.validated') }}:
            {{ validatedGroups.join(', ') }}
        </p>
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
            <label
                class="flex min-h-11 cursor-pointer items-center gap-2 text-sm"
                ><input
                    v-model="form.notify_bot"
                    type="checkbox"
                    class="size-4 shrink-0 cursor-pointer accent-primary"
                />{{ t('telegramTracking.notifyBot') }}</label
            >
            <HelpTooltip
                :label="t('telegramTracking.notifyBot')"
                :text="t('telegramTracking.botHelp')"
            />
            <a
                href="/settings/telegram"
                class="inline-flex min-h-9 items-center text-xs text-primary underline"
                >{{ t('telegramTracking.botSettings') }}</a
            >
        </div>
        <div
            class="grid gap-2 border-t border-border/60 pt-4 sm:flex sm:justify-end"
        >
            <button
                type="button"
                class="intel-button-secondary"
                :disabled="busy || !form.groups.trim() || Boolean(groupsError)"
                @click="$emit('validate')"
            >
                {{ t('telegramTracking.validate') }}
            </button>
            <button class="intel-button-primary" :disabled="busy || !canCreate">
                {{ t('telegramTracking.create') }}
            </button>
        </div>
    </form>
</template>
